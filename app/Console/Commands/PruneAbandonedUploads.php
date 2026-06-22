<?php

namespace App\Console\Commands;

use App\Services\Upload\UploadCleanup;
use Illuminate\Console\Command;

/**
 * Fully removes uploads that were started but never completed: aborts the R2
 * multipart (frees the billed parts), drops local temp chunks, and deletes the
 * tracking row + any minted asset. Also sweeps orphaned local chunk dirs.
 * Scheduled hourly.
 */
class PruneAbandonedUploads extends Command
{
    protected $signature = 'uploads:prune';

    protected $description = 'Remove expired/abandoned upload sessions (R2 parts, local chunks, rows)';

    public function handle(UploadCleanup $cleanup): int
    {
        $pruned = $cleanup->purgeIncomplete(expiredOnly: true);
        $orphans = $cleanup->purgeOrphanTmp();

        $this->info("Pruned {$pruned} abandoned upload(s); cleaned {$orphans} orphan chunk dir(s).");

        return self::SUCCESS;
    }
}
