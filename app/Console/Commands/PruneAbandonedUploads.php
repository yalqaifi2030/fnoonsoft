<?php

namespace App\Console\Commands;

use App\Enums\UploadStatus;
use App\Models\UploadSession;
use App\Services\Upload\R2UploadService;
use Illuminate\Console\Command;

/**
 * Aborts multipart uploads that were started but never completed, so orphaned
 * parts don't accumulate (and bill) in R2. Schedule hourly.
 */
class PruneAbandonedUploads extends Command
{
    protected $signature = 'uploads:prune';

    protected $description = 'Abort and remove expired/abandoned upload sessions from R2';

    public function handle(R2UploadService $r2): int
    {
        $expired = UploadSession::where('status', UploadStatus::Pending->value)
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $session) {
            try {
                if ($session->r2_upload_id) {
                    $r2->abortMultipartUpload($session->r2_key, $session->r2_upload_id);
                }
                $session->update([
                    'status' => UploadStatus::Failed,
                    'error_message' => 'Abandoned (expired before completion).',
                ]);
                $count++;
            } catch (\Throwable $e) {
                $this->warn("Failed to prune {$session->uuid}: {$e->getMessage()}");
            }
        }

        $this->info("Pruned {$count} abandoned upload(s).");

        return self::SUCCESS;
    }
}
