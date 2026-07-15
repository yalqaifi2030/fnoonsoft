<?php

namespace App\Console\Commands;

use App\Models\BlockedIp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Escape hatch: lift IP blocks from the CLI (SSH). Use when a block needs
 * clearing without the admin panel — e.g. if an admin locked themselves out.
 *   php artisan security:unblock 1.2.3.4
 *   php artisan security:unblock --all
 */
class SecurityUnblock extends Command
{
    protected $signature = 'security:unblock {ip? : The IP to unblock} {--all : Remove ALL blocks}';

    protected $description = 'Remove IP block(s) created by the security guard';

    public function handle(): int
    {
        if ($this->option('all')) {
            $n = BlockedIp::query()->delete();
            Cache::forget('sec:blocked');
            $this->info("Removed {$n} block(s).");

            return self::SUCCESS;
        }

        $ip = $this->argument('ip');
        if (! $ip) {
            $this->error('Provide an IP, or use --all.');

            return self::INVALID;
        }

        $n = BlockedIp::where('ip', $ip)->delete();
        Cache::forget('sec:blocked');

        $this->info($n ? "Unblocked {$ip}." : "{$ip} was not blocked.");

        return self::SUCCESS;
    }
}
