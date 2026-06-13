<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\UploadSession;
use App\Services\Upload\R2UploadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Report the active storage mode + the latest upload session, to explain why a
 * file landed on the local disk instead of S3/iDrive.
 */
class StorageDiag extends Command
{
    protected $signature = 'storage:diag';

    protected $description = 'Diagnose storage mode (S3/iDrive vs local/proxy) and the last upload';

    public function handle(R2UploadService $r2): int
    {
        $configured = $r2->isConfigured();
        $proxy = (bool) Setting::get('storage_proxy');
        $disk = config('filesystems.disks.r2');

        $this->line('────────── STORAGE ──────────');
        $this->line('R2/iDrive configured : '.($configured ? 'YES ✓' : 'NO ✗  ← uploads fall back to LOCAL'));
        $this->line('storage_proxy        : '.($proxy ? 'ON ✗  ← buffers LOCAL first, then queue-migrates' : 'OFF ✓ (direct browser→S3)'));
        $this->line('effective mode       : '.($configured ? ($proxy ? 'r2 (PROXY → local then migrate)' : 'r2 (DIRECT to iDrive)') : 'LOCAL only'));
        $this->line('r2 endpoint          : '.($disk['endpoint'] ?: '(empty)'));
        $this->line('r2 bucket            : '.($disk['bucket'] ?: '(empty)'));
        $this->line('r2 region            : '.($disk['region'] ?: '(empty)'));

        $this->line('────────── LAST UPLOAD ──────────');
        $s = UploadSession::latest('id')->first();
        if ($s) {
            $this->line('name     : '.$s->original_name);
            $this->line('disk     : '.$s->storage_disk.($s->proxied ? ' (proxied)' : ''));
            $this->line('status   : '.(is_object($s->status) ? $s->status->value : $s->status));
            $this->line('key      : '.$s->r2_key);
            $this->line('size     : '.round(((int) $s->size_bytes) / 1073741824, 2).' GB');
        } else {
            $this->line('(no upload sessions)');
        }

        $this->line('────────── QUEUE ──────────');
        $this->line('pending jobs : '.DB::table('jobs')->count());
        $this->line('failed jobs  : '.DB::table('failed_jobs')->count());

        return self::SUCCESS;
    }
}
