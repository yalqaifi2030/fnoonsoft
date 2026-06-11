<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Stamps a tiled, diagonal, semi-transparent text watermark over an image to
 * protect the site's content. Pure GD (no external binaries). It is designed to
 * NEVER throw into the upload flow — any problem just skips the watermark.
 */
class WatermarkService
{
    public function enabled(): bool
    {
        return (bool) Setting::get('watermark_enabled', false);
    }

    /** Watermark the image at $absolutePath in place. Returns true if applied. */
    public function apply(string $absolutePath): bool
    {
        try {
            if (! $this->enabled()
                || ! function_exists('imagettftext')
                || ! is_file($absolutePath)) {
                return false;
            }

            $font = $this->findFont();
            $text = $this->text();

            if (! $font || $text === '') {
                return false;
            }

            $img = $this->load($absolutePath);
            if (! $img) {
                return false;
            }

            imagealphablending($img, true);

            $w = imagesx($img);
            $h = imagesy($img);

            $sizePct = max(2.0, min(12.0, (float) (Setting::get('watermark_size', 4) ?: 4)));
            $fontSize = max(11, (int) round($w * $sizePct / 100));

            $opacity = max(3, min(80, (int) (Setting::get('watermark_opacity', 25) ?: 25)));
            $alpha = (int) round(127 - ($opacity / 100 * 127));
            $haloAlpha = (int) round(127 - (min(85, $opacity + 15) / 100 * 127));

            $white = imagecolorallocatealpha($img, 255, 255, 255, $alpha);
            $dark = imagecolorallocatealpha($img, 0, 0, 0, $haloAlpha);
            $off = max(1, (int) round($fontSize / 16));

            // Draw the text with a dark halo so it stays visible on light, dark AND
            // transparent images (a plain white stamp vanishes on a white photo).
            $stamp = function (int $x, int $y, int $angle) use ($img, $fontSize, $font, $text, $white, $dark, $off) {
                foreach ([[-$off, 0], [$off, 0], [0, -$off], [0, $off], [-$off, -$off], [$off, $off], [-$off, $off], [$off, -$off]] as [$dx, $dy]) {
                    imagettftext($img, $fontSize, $angle, $x + $dx, $y + $dy, $dark, $font, $text);
                }
                imagettftext($img, $fontSize, $angle, $x, $y, $white, $font, $text);
            };

            $angle = 30;
            $box = imagettfbbox($fontSize, $angle, $font, $text);
            $textW = abs($box[2] - $box[0]);
            $textH = abs($box[7] - $box[1]);
            $stepX = max($textW + $fontSize * 3, $fontSize * 8);
            $stepY = max($textH + $fontSize * 4, $fontSize * 7);

            $tiled = (Setting::get('watermark_position', 'tiled') ?: 'tiled') === 'tiled';

            if ($tiled) {
                for ($y = (int) $stepY; $y < $h + $stepY; $y += (int) $stepY) {
                    for ($x = -(int) $stepX; $x < $w; $x += (int) $stepX) {
                        $stamp($x, $y, $angle);
                    }
                }
            } else {
                $stamp((int) ($w - $textW - $fontSize), (int) ($h - $fontSize), 0);
            }

            $this->save($img, $absolutePath);
            imagedestroy($img);

            return true;
        } catch (\Throwable $e) {
            return false; // a watermark must never break an upload
        }
    }

    private function text(): string
    {
        $text = trim((string) Setting::get('watermark_text'));

        if ($text !== '') {
            return $text;
        }

        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'finunsoft.com';
    }

    /** First available TrueType font (custom setting, then common system paths). */
    private function findFont(): ?string
    {
        foreach (array_filter([
            Setting::get('watermark_font_path'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/liberation/LiberationSans-Regular.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\tahoma.ttf',
        ]) as $candidate) {
            if ($candidate && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function load(string $path)
    {
        $info = @getimagesize($path);

        if (! $info) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        } ?: null;
    }

    private function save($img, string $path): void
    {
        $type = @getimagesize($path)[2] ?? IMAGETYPE_JPEG;

        match ($type) {
            IMAGETYPE_PNG => (function () use ($img, $path) {
                imagesavealpha($img, true);
                imagepng($img, $path);
            })(),
            IMAGETYPE_GIF => imagegif($img, $path),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($img, $path, 90) : imagejpeg($img, $path, 90),
            default => imagejpeg($img, $path, 90),
        };
    }
}
