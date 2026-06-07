<?php

namespace App\Services\Upload;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds shareable assets: generates short slugs, derives image variants
 * (thumb / medium / WebP, EXIF stripped) with GD, and produces the ready-made
 * "share kit" of copy-paste codes (direct link, HTML, Markdown, BBCode, …).
 */
class AssetService
{
    private string $disk = 'public';

    /** A unique short id used for /d/{slug}. */
    public function newSlug(int $len = 8): string
    {
        do {
            $slug = Str::lower(Str::random($len));
        } while (Asset::where('slug', $slug)->exists());

        return $slug;
    }

    /**
     * Generate downscaled variants + a WebP copy for an image already stored on
     * the public disk. Re-encoding via GD strips EXIF automatically.
     *
     * @return array{width:?int,height:?int,variants:array}
     */
    public function processImage(string $publicRelativePath): array
    {
        $abs = Storage::disk($this->disk)->path($publicRelativePath);
        $info = @getimagesize($abs);

        if ($info === false) {
            return ['width' => null, 'height' => null, 'variants' => []];
        }

        [$w, $h] = $info;
        $mime = $info['mime'] ?? '';

        // Vector/animated formats: keep the original, no raster variants.
        if (in_array($mime, ['image/svg+xml', 'image/gif'], true)) {
            return ['width' => $w, 'height' => $h, 'variants' => []];
        }

        $src = $this->loadGd($abs, $mime);
        if (! $src) {
            return ['width' => $w, 'height' => $h, 'variants' => []];
        }

        $dir = dirname($publicRelativePath);
        $base = pathinfo($publicRelativePath, PATHINFO_FILENAME);
        $variants = [];

        // thumb (max 400w) + medium (max 1280w) as JPEG
        foreach (['thumb' => 400, 'medium' => 1280] as $name => $maxW) {
            if ($w <= $maxW && $name === 'medium') {
                continue; // no point upscaling the medium
            }
            $rel = $dir.'/'.$base.'_'.$name.'.jpg';
            $dims = $this->resample($src, $w, $h, $maxW, Storage::disk($this->disk)->path($rel), 'jpeg');
            if ($dims) {
                $variants[$name] = ['path' => $rel, 'w' => $dims[0], 'h' => $dims[1]];
            }
        }

        // WebP copy of the (capped) original for lightweight hotlinking
        if (function_exists('imagewebp')) {
            $rel = $dir.'/'.$base.'.webp';
            $dims = $this->resample($src, $w, $h, min($w, 1920), Storage::disk($this->disk)->path($rel), 'webp');
            if ($dims) {
                $variants['webp'] = ['path' => $rel, 'w' => $dims[0], 'h' => $dims[1]];
            }
        }

        imagedestroy($src);

        return ['width' => $w, 'height' => $h, 'variants' => $variants];
    }

    /** @return \GdImage|false */
    private function loadGd(string $abs, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($abs),
            'image/png' => @imagecreatefrompng($abs),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($abs) : false,
            default => false,
        };
    }

    /**
     * Resample $src down to $maxW (preserving aspect) and write it.
     *
     * @param  \GdImage  $src
     * @return array{0:int,1:int}|null  final [w,h]
     */
    private function resample($src, int $w, int $h, int $maxW, string $destAbs, string $format): ?array
    {
        $ratio = $maxW / $w;
        $nw = min($w, $maxW);
        $nh = (int) round($h * ($nw / $w));

        $dst = imagecreatetruecolor($nw, $nh);

        if ($format === 'png' || $format === 'webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        } else {
            // flatten transparency onto white for JPEG
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        if (! is_dir(dirname($destAbs))) {
            mkdir(dirname($destAbs), 0775, true);
        }

        $ok = match ($format) {
            'webp' => function_exists('imagewebp') && imagewebp($dst, $destAbs, 82),
            'png' => imagepng($dst, $destAbs, 6),
            default => imagejpeg($dst, $destAbs, 85),
        };

        imagedestroy($dst);

        return $ok ? [$nw, $nh] : null;
    }

    /** Best-effort PDF page count (cheap regex; good enough for a badge). */
    public function pdfPageCount(string $absPath): ?int
    {
        $content = @file_get_contents($absPath, false, null, 0, 2_000_000);
        if ($content === false) {
            return null;
        }
        if (preg_match_all('/\/Type\s*\/Page\b/', $content, $m)) {
            return max(1, count($m[0]));
        }

        return null;
    }

    // --- Share kit -------------------------------------------------------

    /**
     * Build the copy-paste code blocks for an asset.
     *
     * @return array<int,array{key:string,label:string,lang:string,code:string}>
     */
    public function shareKit(Asset $asset): array
    {
        $name = $asset->original_name;
        $alt = pathinfo($name, PATHINFO_FILENAME);

        if ($asset->isImage()) {
            $direct = $asset->directUrl();
            $thumb = $asset->thumbUrl() ?: $direct;

            $kit = [
                ['key' => 'direct', 'label' => 'Direct link', 'lang' => 'text', 'code' => $direct],
                ['key' => 'html', 'label' => 'HTML', 'lang' => 'html', 'code' => '<img src="'.$direct.'" alt="'.$alt.'">'],
                ['key' => 'markdown', 'label' => 'Markdown', 'lang' => 'markdown', 'code' => '!['.$alt.']('.$direct.')'],
                ['key' => 'bbcode', 'label' => 'BBCode', 'lang' => 'text', 'code' => '[img]'.$direct.'[/img]'],
                ['key' => 'thumb', 'label' => 'Thumbnail → full', 'lang' => 'html', 'code' => '<a href="'.$direct.'"><img src="'.$thumb.'" alt="'.$alt.'"></a>'],
                ['key' => 'forum', 'label' => 'Forum (BBCode thumb)', 'lang' => 'text', 'code' => '[url='.$direct.'][img]'.$thumb.'[/img][/url]'],
            ];

            return $kit;
        }

        // file / pdf → point at the landing+download
        $page = $asset->pageUrl();
        $dl = $asset->downloadUrl();

        return [
            ['key' => 'direct', 'label' => 'Share link', 'lang' => 'text', 'code' => $page],
            ['key' => 'html', 'label' => 'HTML download button', 'lang' => 'html', 'code' => $this->downloadButtonHtml($dl, $name)],
            ['key' => 'markdown', 'label' => 'Markdown', 'lang' => 'markdown', 'code' => '['.$name.']('.$page.')'],
            ['key' => 'bbcode', 'label' => 'BBCode', 'lang' => 'text', 'code' => '[url='.$page.']'.$name.'[/url]'],
        ];
    }

    /** A self-contained, paste-anywhere download button with an icon. */
    public function downloadButtonHtml(string $url, string $name): string
    {
        $icon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';

        return '<a href="'.$url.'" '
            .'style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;'
            .'background:#006C35;color:#fff;border-radius:10px;text-decoration:none;'
            .'font-family:sans-serif;font-weight:700">'
            .$icon.' Download '.e($name).'</a>';
    }
}
