<?php

use App\Models\Asset;
use App\Models\DownloadLink;
use Illuminate\Database\Migrations\Migration;

/**
 * Historical fix: a software file and a member share-asset are two records of
 * the SAME physical file (asset.path == download_link.r2_key). Downloads were
 * only ever counted on the software link, so members saw 0 in "My files".
 * Align each asset's count with its link's count (going forward the download
 * controller increments both).
 */
return new class extends Migration
{
    public function up(): void
    {
        DownloadLink::query()
            ->whereNotNull('r2_key')
            ->where('downloads_count', '>', 0)
            ->select('r2_key', 'downloads_count')
            ->orderBy('id')
            ->chunk(200, function ($links) {
                foreach ($links as $link) {
                    Asset::where('path', $link->r2_key)
                        ->where('downloads_count', '<', $link->downloads_count)
                        ->update(['downloads_count' => $link->downloads_count]);
                }
            });
    }

    public function down(): void
    {
        // Non-reversible data backfill.
    }
};
