<?php

namespace App\Services\Upload;

use App\Enums\UploadStatus;
use App\Models\UploadSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Removes incomplete / abandoned uploads completely — aborts the R2 multipart
 * (or drops the local buffer), deletes any stored bytes, and removes the
 * tracking row. Used by the hourly prune and by the manual "clean incomplete"
 * action in the upload panel.
 */
class UploadCleanup
{
    public function __construct(
        private readonly R2UploadService $r2,
        private readonly LocalUploadService $local,
    ) {
    }

    /** Uploads that never finished (still in flight or failed before assembling). */
    public function incomplete(?int $userId = null, bool $expiredOnly = false): Collection
    {
        return UploadSession::query()
            ->whereIn('status', [UploadStatus::Pending->value, UploadStatus::Failed->value])
            ->whereNull('completed_at')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($expiredOnly, fn ($q) => $q->where('expires_at', '<', now()))
            ->get();
    }

    /** Abort + delete bytes + remove the row for each session. Returns the count. */
    public function purge(Collection $sessions): int
    {
        $count = 0;

        foreach ($sessions as $session) {
            try {
                if ($session->storage_disk === 'local' || $session->proxied) {
                    $this->local->abort($session); // deletes the local tmp chunk dir
                } elseif ($session->r2_upload_id) {
                    try {
                        $this->r2->abortMultipartUpload($session->r2_key, $session->r2_upload_id);
                    } catch (\Throwable $e) {
                        // already gone on R2 — nothing to free
                    }
                }
            } catch (\Throwable $e) {
                // never let one bad session block the rest
            }

            try {
                $session->delete(); // model hook removes any minted asset + final object
                $count++;
            } catch (\Throwable $e) {
                // skip
            }
        }

        return $count;
    }

    public function purgeIncomplete(?int $userId = null, bool $expiredOnly = false): int
    {
        return $this->purge($this->incomplete($userId, $expiredOnly));
    }

    /** Delete leftover local chunk directories that no longer have a live session. */
    public function purgeOrphanTmp(): int
    {
        $count = 0;

        foreach (Storage::disk('local')->directories('uploads/tmp') as $dir) {
            $uuid = basename($dir);

            $live = UploadSession::where('uuid', $uuid)
                ->whereNull('completed_at')
                ->where('expires_at', '>=', now())
                ->exists();

            if (! $live) {
                try {
                    Storage::disk('local')->deleteDirectory($dir);
                    $count++;
                } catch (\Throwable $e) {
                    // skip
                }
            }
        }

        return $count;
    }
}
