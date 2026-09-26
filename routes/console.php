<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Free orphaned multipart parts from abandoned uploads.
Schedule::command('uploads:prune')->hourly();

// Housekeeping so log tables and the DB cache don't grow forever:
// visits (1y), security events (6m), download logs (2y) — see each model's prunable().
Schedule::command('model:prune')->dailyAt('03:30');
// Files rejected by moderation are kept 30 days as evidence, then deleted.
Schedule::command('moderation:purge-rejected')->dailyAt('03:50');
Schedule::command('queue:prune-failed', ['--hours' => 24 * 30])->dailyAt('03:40');
Schedule::command('queue:prune-batches', ['--hours' => 24 * 7])->dailyAt('03:45');