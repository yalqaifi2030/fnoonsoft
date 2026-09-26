<?php

namespace App\Console\Commands;

use App\Support\FileModeration;
use Illuminate\Console\Command;

/** Deletes files rejected by moderation once their evidence window has passed. */
class PurgeRejectedFiles extends Command
{
    protected $signature = 'moderation:purge-rejected';

    protected $description = 'Delete files rejected more than '.FileModeration::RETENTION_DAYS.' days ago (review history is kept)';

    public function handle(): int
    {
        $n = FileModeration::purgeExpired();
        $this->info("Purged {$n} rejected file(s).");

        return self::SUCCESS;
    }
}
