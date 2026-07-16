<?php

namespace App\Jobs;

use App\Enums\UploadStatus;
use App\Models\UploadSession;
use App\Services\Upload\ChecksumService;
use App\Services\Upload\MalwareScanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Post-upload pipeline for a completed R2 object:
 *   verify size → checksum (streamed) → malware scan → publish | fail.
 *
 * Runs on the queue so a 30GB hash never blocks an HTTP request.
 */
class ProcessUploadedFile implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 3600;   // hashing a huge file can take a while
    public int $tries = 2;

    public function __construct(public int $uploadSessionId)
    {
    }

    public function handle(ChecksumService $checksums, MalwareScanService $scanner): void
    {
        $session = UploadSession::find($this->uploadSessionId);
        if (! $session || $session->status === UploadStatus::Published) {
            return;
        }

        $session->update(['status' => UploadStatus::Scanning]);

        $r2 = app(\App\Services\Upload\R2UploadService::class);
        $local = app(\App\Services\Upload\LocalUploadService::class);

        // The object lives on the local disk for local-mode uploads AND for proxy
        // uploads not yet migrated to S3 (the migration happens at the end of this job).
        $onLocal = $local->exists($session->r2_key);

        try {
            // 1) Confirm the object really landed and matches the declared size.
            $exists = $onLocal ? true : $r2->exists($session->r2_key);
            if (! $exists) {
                throw new \RuntimeException('Uploaded object missing after completion.');
            }
            $actualSize = $onLocal ? $local->size($session->r2_key) : $r2->size($session->r2_key);
            if ($session->size_bytes > 0 && $actualSize !== (int) $session->size_bytes) {
                Log::warning('Upload size mismatch', [
                    'session' => $session->uuid,
                    'declared' => $session->size_bytes,
                    'actual' => $actualSize,
                ]);
            }

            // 2) Checksums (skippable for very large files via env).
            $maxHash = (int) env('CHECKSUM_MAX_BYTES', 42949672960);
            if ($actualSize <= $maxHash) {
                $hashes = $onLocal
                    ? $checksums->forPath($local->path($session->r2_key))
                    : $checksums->forKey($session->r2_key);
                $session->update([
                    'checksum_sha256' => $hashes['sha256'],
                    'checksum_md5' => $hashes['md5'],
                ]);
                \App\Models\Asset::where('upload_session_id', $session->id)
                    ->update(['checksum_sha256' => $hashes['sha256']]);
            }

            // 3) Malware scan.
            $scan = $scanner->scan($session->r2_key, $session->checksum_sha256);
            $session->update([
                'scan_result' => $scan['result'],
                'scan_report' => $scan['report'],
            ]);

            if ($scan['result'] === 'infected') {
                $onLocal ? $local->delete($session->r2_key) : $r2->delete($session->r2_key); // never keep a known-bad file
                $session->update([
                    'status' => UploadStatus::Failed,
                    'error_message' => 'File failed malware scan and was deleted.',
                ]);

                return;
            }

            // 4) Proxy mode: push the verified local buffer up to S3 (may be many GB —
            // fine on the queue), repoint the asset to S3, then drop the local copy.
            if ($session->proxied && $onLocal) {
                $r2->putObjectFromFile($session->r2_key, $local->path($session->r2_key), $session->mime_type);
                \App\Models\Asset::where('upload_session_id', $session->id)->update(['disk' => 'r2']);
                $local->delete($session->r2_key);
            }

            $session->update(['status' => UploadStatus::Published]);
        } catch (\Throwable $e) {
            $session->update([
                'status' => UploadStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
