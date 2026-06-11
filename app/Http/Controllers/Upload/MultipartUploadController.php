<?php

namespace App\Http\Controllers\Upload;

use App\Enums\UploadStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessUploadedFile;
use App\Models\Asset;
use App\Models\Setting;
use App\Models\UploadSession;
use App\Services\Upload\AssetService;
use App\Services\Upload\LocalUploadService;
use App\Services\Upload\R2UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

/**
 * Endpoints consumed by the Uppy AWS-S3 multipart plugin in the upload panel.
 *
 * Flow: create → sign (per part, repeatable for resume) → complete | abort.
 * Auth is the panel session; only uploader roles reach these routes.
 */
class MultipartUploadController extends Controller
{
    /** Extensions we accept for download artefacts. */
    private const ALLOWED_EXT = [
        'zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'xz',
        'exe', 'msi', 'dmg', 'pkg', 'deb', 'rpm', 'appimage', 'apk', 'aab', 'ipa',
        'iso', 'img', 'bin',
        'php', 'js', 'ts', 'py', 'rb', 'go', 'rs', 'java', 'jar',
        'sql', 'json', 'xml', 'yml', 'yaml', 'env',
    ];

    public function __construct(
        private readonly R2UploadService $r2,
        private readonly LocalUploadService $local,
        private readonly AssetService $assets,
    ) {
    }

    /** Begin a multipart upload; create the tracking session. */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:150'],
            'size' => ['required', 'integer', 'min:1', 'max:'.$this->maxBytes()],
        ]);

        $ext = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED_EXT, true)) {
            return response()->json([
                'message' => __('upload.errors.extension', ['ext' => $ext]),
            ], 422);
        }

        // Per-member limits: staff are unlimited (User::storageQuotaBytes), members
        // are capped by the configured quota + an optional per-file ceiling.
        $user = $request->user();
        if ($user && ! $user->isStaff()) {
            $maxFileBytes = $user->maxFileBytes(); // tier → global setting → unlimited
            if ($data['size'] > $maxFileBytes) {
                return response()->json([
                    'message' => __('member.errors.too_large', ['max' => round($maxFileBytes / 1024 ** 3, 1)]),
                ], 422);
            }

            if ($data['size'] > $user->storageRemainingBytes()) {
                return response()->json([
                    'message' => __('member.errors.quota', [
                        'used' => round($user->storageUsedBytes() / 1024 ** 3, 2),
                        'quota' => round($user->storageQuotaBytes() / 1024 ** 3, 2),
                    ]),
                ], 422);
            }
        }

        // Use R2/S3 when configured; otherwise transparently fall back to local disk.
        $disk = $this->r2->isConfigured() ? 'r2' : 'local';

        // Proxy mode: buffer chunks on the server, then push the whole object to
        // S3 on completion (no browser CORS, no provider multipart API needed).
        $proxied = $disk === 'r2' && (bool) Setting::get('storage_proxy');

        $key = $this->r2->buildKey($data['filename']);
        // Direct R2 uses a real multipart upload; local & proxy buffer locally first.
        $uploadId = ($disk === 'r2' && ! $proxied)
            ? $this->r2->createMultipartUpload($key, $data['type'] ?? null)
            : $this->local->createMultipart();

        $partSize = (int) env('UPLOAD_PART_SIZE', 16 * 1024 * 1024);
        $partsTotal = (int) ceil($data['size'] / $partSize);

        $session = UploadSession::create([
            'user_id' => $request->user()?->id,
            'original_name' => $data['filename'],
            'mime_type' => $data['type'] ?? null,
            'size_bytes' => $data['size'],
            'r2_key' => $key,
            'r2_upload_id' => $uploadId,
            'storage_disk' => $disk,
            'proxied' => $proxied,
            'part_size' => $partSize,
            'parts_total' => $partsTotal,
            'parts_completed' => 0,
            'status' => UploadStatus::Pending,
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json([
            'sessionUuid' => $session->uuid,
            'key' => $key,
            'uploadId' => $uploadId,
            'partSize' => $partSize,
            'storage' => $disk,
        ]);
    }

    /** Presign one part (or a batch of part numbers). */
    public function sign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
            'uploadId' => ['required', 'string'],
            'partNumber' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'partNumbers' => ['nullable', 'array'],
            'partNumbers.*' => ['integer', 'min:1', 'max:10000'],
        ]);

        $session = UploadSession::where('r2_key', $data['key'])->firstOrFail();
        abort_unless($this->owns($request, $session), 403);

        // Local fallback OR proxy mode: hand back a signed same-origin URL the
        // browser PUTs to (the server then stores it / relays it to S3).
        if ($session->storage_disk === 'local' || $session->proxied) {
            $ttl = now()->addMinutes($this->r2->ttlMinutes());

            if (! empty($data['partNumbers'])) {
                $urls = [];
                foreach ($data['partNumbers'] as $n) {
                    $urls[$n] = $this->localPartUrl($session, (int) $n, $ttl);
                }

                return response()->json(['urls' => $urls]);
            }

            return response()->json([
                'url' => $this->localPartUrl($session, (int) $data['partNumber'], $ttl),
            ]);
        }

        // R2: genuine presigned PUT URLs straight to Cloudflare.
        if (! empty($data['partNumbers'])) {
            return response()->json([
                'urls' => $this->r2->presignParts($data['key'], $data['uploadId'], $data['partNumbers']),
            ]);
        }

        return response()->json([
            'url' => $this->r2->presignPart($data['key'], $data['uploadId'], (int) $data['partNumber']),
        ]);
    }

    /** A short-lived, tamper-proof URL for PUTting one local part. */
    private function localPartUrl(UploadSession $session, int $partNumber, \DateTimeInterface $ttl): string
    {
        return URL::temporarySignedRoute('upload.multipart.put-part', $ttl, [
            'session' => $session->uuid,
            'partNumber' => $partNumber,
        ]);
    }

    /** Receive one raw chunk (signed URL, no CSRF token) — store locally or relay to S3. */
    public function putPart(Request $request, string $session): Response
    {
        $model = UploadSession::where('uuid', $session)->firstOrFail();
        abort_unless($model->storage_disk === 'local' || $model->proxied, 404);

        $partNumber = (int) $request->query('partNumber');
        abort_if($partNumber < 1, 422, 'Invalid part number.');

        $contents = $request->getContent();
        abort_if($contents === '', 422, 'Empty part body.');

        // Both local and proxy mode buffer the chunk on the server's local disk.
        $etag = $this->local->storePart($model, $partNumber, $contents);

        $model->increment('parts_completed');

        return response('', 200)
            ->header('ETag', $etag)
            ->header('Access-Control-Expose-Headers', 'ETag');
    }

    /** Finalise: assemble the object, mark uploaded, dispatch processing. */
    public function complete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sessionUuid' => ['required', 'string'],
            'key' => ['required', 'string'],
            'uploadId' => ['required', 'string'],
            'parts' => ['required', 'array', 'min:1'],
            'parts.*.PartNumber' => ['required', 'integer', 'min:1'],
            'parts.*.ETag' => ['required', 'string'],
        ]);

        $session = $this->session($request, $data['sessionUuid']);

        try {
            if ($session->proxied || $session->storage_disk === 'local') {
                // Assemble the buffered parts on the LOCAL disk (streamed, fast).
                // In proxy mode the heavy push to S3 is deferred to the queued
                // ProcessUploadedFile job — pushing a multi-GB object inline would
                // exceed PHP/Nginx timeouts and fail the request. The asset serves
                // from local until the job migrates it to S3.
                $this->local->completeMultipart($session, $data['parts']);
                $assetDisk = 'local';
            } else {
                $this->r2->completeMultipartUpload($data['key'], $data['uploadId'], $data['parts']);
                $assetDisk = 'r2';
            }
        } catch (\Throwable $e) {
            $session->update([
                'status' => UploadStatus::Failed,
                'error_message' => 'Finalisation failed: '.$e->getMessage(),
            ]);

            return response()->json(['message' => __('upload.errors.complete')], 500);
        }

        $session->update([
            'status' => UploadStatus::Uploaded,
            'parts' => $data['parts'],
            'parts_completed' => count($data['parts']),
            'completed_at' => now(),
        ]);

        // Mint a shareable asset right away so the panel can show the share kit.
        $asset = Asset::create([
            'slug' => $this->assets->newSlug(),
            'user_id' => $session->user_id,
            'upload_session_id' => $session->id,
            'kind' => 'file',
            'disk' => $assetDisk,
            'path' => $session->r2_key,
            'original_name' => $session->original_name,
            'mime_type' => $session->mime_type,
            'size_bytes' => $session->size_bytes,
            'checksum_sha256' => $session->checksum_sha256,
            'is_active' => true,
        ]);

        // checksum + malware scan run in the background, never blocking the request
        ProcessUploadedFile::dispatch($session->id);

        return response()->json([
            'location' => $data['key'],
            'sessionUuid' => $session->uuid,
            'asset' => [
                'slug' => $asset->slug,
                'kind' => 'file',
                'name' => $asset->original_name,
                'size' => $asset->size_bytes,
                'preview' => null,
                'page' => $asset->pageUrl(),
                'download' => $asset->downloadUrl(),
                'kit' => $this->assets->shareKit($asset),
            ],
        ]);
    }

    /** Abort an in-flight upload and free its parts. */
    public function abort(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sessionUuid' => ['required', 'string'],
            'key' => ['required', 'string'],
            'uploadId' => ['required', 'string'],
        ]);

        $session = $this->session($request, $data['sessionUuid']);

        // Proxy & local sessions only ever have a local buffer — there is no real
        // S3 multipart to abort (the uploadId is a synthetic "local-…"), so aborting
        // it on S3 would throw NoSuchUpload. Just drop the local parts.
        if ($session->storage_disk === 'local' || $session->proxied) {
            $this->local->abort($session);
        } else {
            $this->r2->abortMultipartUpload($data['key'], $data['uploadId']);
        }

        $session->update(['status' => UploadStatus::Failed, 'error_message' => 'Aborted by user']);

        return response()->json(['ok' => true]);
    }

    private function maxBytes(): int
    {
        return (int) env('UPLOAD_MAX_BYTES', 32212254720); // 30 GB
    }

    private function session(Request $request, string $uuid): UploadSession
    {
        $session = UploadSession::where('uuid', $uuid)->firstOrFail();
        abort_unless($this->owns($request, $session), 403);

        return $session;
    }

    private function owns(Request $request, UploadSession $session): bool
    {
        $user = $request->user();

        return $user && ($session->user_id === $user->id || $user->hasRole('super_admin'));
    }
}
