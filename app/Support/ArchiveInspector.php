<?php

namespace App\Support;

use App\Models\Asset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Lists what is inside an uploaded ZIP (names, sizes) so a moderator can judge
 * it without downloading — and flags executables/scripts hidden inside.
 * Only for files on the server's own disk; object-storage files would need a
 * full download first. Results are cached per checksum.
 */
class ArchiveInspector
{
    private const MAX_ENTRIES = 300;

    private const RISKY = [
        'exe', 'msi', 'bat', 'cmd', 'com', 'scr', 'pif', 'vbs', 'vbe', 'js', 'jse', 'wsf',
        'ps1', 'psm1', 'jar', 'dll', 'sys', 'lnk', 'hta', 'reg', 'sh', 'apk', 'app', 'dmg',
    ];

    /**
     * @return array{entries: array<int, array{name:string,size:int,dir:bool,risky:bool}>, total:int, flagged:int, uncompressed:int}|null
     */
    /** Largest object-storage ZIP we'll pull to the server just to list it. */
    public const REMOTE_MAX_BYTES = 1073741824; // 1 GB

    public static function isZip(Asset $asset): bool
    {
        return strtolower(pathinfo((string) $asset->original_name, PATHINFO_EXTENSION)) === 'zip'
            && class_exists(\ZipArchive::class);
    }

    /** A ZIP on object storage that can be fetched on demand (see inspectRemote). */
    public static function canInspectRemote(Asset $asset): bool
    {
        return self::isZip($asset) && $asset->disk === 'r2' && (int) $asset->size_bytes <= self::REMOTE_MAX_BYTES;
    }

    /** Cached result of an earlier inspection (local or remote), without doing any work. */
    public static function cached(Asset $asset): ?array
    {
        return $asset->checksum_sha256 ? Cache::get('archive:'.$asset->checksum_sha256) : null;
    }

    /** Download an object-storage ZIP to a temp file, list it, delete the copy. */
    public static function inspectRemote(Asset $asset): ?array
    {
        if (! self::canInspectRemote($asset)) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'fnzip');
        try {
            $r2 = app(\App\Services\Upload\R2UploadService::class);
            $r2->client()->getObject(['Bucket' => $r2->bucket(), 'Key' => $asset->path, 'SaveAs' => $tmp]);

            return self::list($tmp, 'archive:'.($asset->checksum_sha256 ?: 'r2:'.md5($asset->path)));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[fnoon] remote archive inspect failed', ['msg' => $e->getMessage()]);

            return null;
        } finally {
            @unlink($tmp);
        }
    }

    public static function inspect(Asset $asset): ?array
    {
        if (! self::isZip($asset)) {
            return null;
        }
        if (! in_array($asset->disk, ['local', 'public'], true)) {
            return self::cached($asset); // object storage: only if inspected before
        }

        $path = Storage::disk($asset->disk)->path($asset->path);
        if (! is_file($path)) {
            return null;
        }

        return self::list($path, 'archive:'.($asset->checksum_sha256 ?: md5($path.filemtime($path))));
    }

    private static function list(string $path, string $cacheKey): ?array
    {
        return Cache::remember($cacheKey, now()->addDay(), function () use ($path) {
            $zip = new \ZipArchive;
            if ($zip->open($path, \ZipArchive::RDONLY) !== true) {
                return null;
            }

            $entries = [];
            $flagged = 0;
            $uncompressed = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (! $stat) {
                    continue;
                }
                $name = (string) $stat['name'];
                $dir = str_ends_with($name, '/');
                $risky = ! $dir && in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::RISKY, true);
                $flagged += $risky ? 1 : 0;
                $uncompressed += (int) $stat['size'];

                if (count($entries) < self::MAX_ENTRIES) {
                    $entries[] = ['name' => $name, 'size' => (int) $stat['size'], 'dir' => $dir, 'risky' => $risky];
                }
            }
            $total = $zip->numFiles;
            $zip->close();

            // Risky entries first so they are never hidden below the cut-off.
            usort($entries, fn ($a, $b) => $b['risky'] <=> $a['risky']);

            return ['entries' => $entries, 'total' => $total, 'flagged' => $flagged, 'uncompressed' => $uncompressed];
        });
    }
}
