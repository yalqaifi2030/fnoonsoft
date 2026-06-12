<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;

/**
 * Apply the browser-upload CORS rules to the configured S3/iDrive bucket using
 * the credentials already saved in the admin Storage settings. Re-run it after
 * switching buckets/regions (CORS does not carry over).
 */
class StorageCors extends Command
{
    protected $signature = 'storage:cors {--show : Only print the current CORS, do not change it}';

    protected $description = 'Apply (or show) the browser-upload CORS rules on the storage bucket';

    public function handle(): int
    {
        $disk = config('filesystems.disks.r2');

        if (empty($disk['key']) || empty($disk['bucket']) || empty($disk['endpoint'])) {
            $this->error('Storage (r2) is not configured — set it in /admin → Settings → Storage first.');

            return self::FAILURE;
        }

        $client = new S3Client([
            'version' => 'latest',
            'region' => $disk['region'] ?: 'auto',
            'endpoint' => $disk['endpoint'],
            'use_path_style_endpoint' => (bool) ($disk['use_path_style_endpoint'] ?? true),
            'credentials' => ['key' => $disk['key'], 'secret' => $disk['secret']],
        ]);

        $bucket = $disk['bucket'];

        try {
            if ($this->option('show')) {
                $res = $client->getBucketCors(['Bucket' => $bucket]);
                $this->line(json_encode($res['CORSRules'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return self::SUCCESS;
            }

            $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'finunsoft.com';
            $origins = array_values(array_unique([
                "https://$host",
                "https://www.$host",
                "http://$host",
            ]));

            $client->putBucketCors([
                'Bucket' => $bucket,
                'CORSConfiguration' => [
                    'CORSRules' => [[
                        'AllowedHeaders' => ['*'],
                        'AllowedMethods' => ['GET', 'PUT', 'POST', 'HEAD', 'DELETE'],
                        'AllowedOrigins' => $origins,
                        'ExposeHeaders' => ['ETag'],
                        'MaxAgeSeconds' => 3600,
                    ]],
                ],
            ]);

            $this->info("✓ CORS applied to bucket: {$bucket}");
            $this->line('  Origins: '.implode(', ', $origins));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
