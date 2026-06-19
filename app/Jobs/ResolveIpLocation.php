<?php

namespace App\Jobs;

use App\Models\IpLocation;
use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Resolve a single IP to country/region/city/ISP via the free ip-api.com service
 * and cache it (DB + cache) so each IP is looked up only once. Runs on the queue
 * so it never slows a page request. Also back-fills the city onto visits already
 * logged from this IP.
 */
class ResolveIpLocation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(public string $ip) {}

    public function handle(): void
    {
        if (IpLocation::where('ip_address', $this->ip)->exists()) {
            return;
        }

        try {
            $res = Http::timeout(8)->retry(1, 300)->get('http://ip-api.com/json/'.$this->ip, [
                'fields' => 'status,message,country,countryCode,regionName,city,isp,proxy,query',
            ]);

            $d = $res->json();

            if (! is_array($d) || ($d['status'] ?? '') !== 'success') {
                return; // rate-limited or private/invalid IP — leave for a later visit
            }

            $cc = strtoupper((string) ($d['countryCode'] ?? '')) ?: null;

            IpLocation::updateOrCreate(['ip_address' => $this->ip], [
                'country' => $cc,
                'country_name' => $d['country'] ?? null,
                'region' => $d['regionName'] ?? null,
                'city' => $d['city'] ?? null,
                'isp' => $d['isp'] ?? null,
                'is_proxy' => (bool) ($d['proxy'] ?? false),
                'resolved_at' => now(),
            ]);

            Cache::forever('iploc:'.$this->ip, [
                'country' => $cc,
                'region' => $d['regionName'] ?? null,
                'city' => $d['city'] ?? null,
            ]);

            // Back-fill visits already recorded for this IP before geo was known.
            $base = Visit::where('ip_address', $this->ip);
            (clone $base)->whereNull('city')->update([
                'city' => $d['city'] ?? null,
                'region' => $d['regionName'] ?? null,
            ]);
            if ($cc) {
                (clone $base)->whereNull('country')->update(['country' => $cc]);
            }
        } catch (\Throwable $e) {
            // analytics must never blow up — just skip this IP for now
        }
    }
}
