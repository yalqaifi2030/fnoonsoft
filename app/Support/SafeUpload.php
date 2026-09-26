<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Decides the extension an uploaded file is STORED with. Files on the public
 * disk are served raw by nginx from /storage on our own origin, where the
 * extension picks the Content-Type — so a client-chosen ".html"/".svg" (or a
 * polyglot passing a content check) became stored XSS. Rule: never trust the
 * client's extension unless it is on the inert allowlist; otherwise use the
 * one sniffed from the content, and fall back to ".bin" (served as a download).
 */
class SafeUpload
{
    /** Extensions nginx serves as passive media/data — never rendered as a page. */
    public const INERT = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'ico',
        'pdf', 'mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v', 'mp3', 'wav',
        'glb', 'gltf', 'obj', 'usdz', 'bin', 'zip', 'txt',
    ];

    /** Raster image MIME types members may upload (no SVG). */
    public const RASTER_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public static function extension(UploadedFile $file, bool $allowSvg = false): string
    {
        $client = strtolower((string) $file->getClientOriginalExtension());
        $guessed = strtolower((string) $file->guessExtension());

        if ($allowSvg && $client === 'svg' && $guessed === 'svg') {
            return 'svg';
        }
        if (in_array($client, self::INERT, true)) {
            return $client;
        }
        if (in_array($guessed, self::INERT, true)) {
            return $guessed;
        }

        return 'bin';
    }

    /** SVG (script-capable) is only accepted from staff. */
    public static function staffMayUseSvg(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }
}
