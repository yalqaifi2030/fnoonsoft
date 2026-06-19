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

    /**
     * Is the watermark enabled for a given surface? Surfaces:
     *  - 'media'       — share/media uploads (default on; the original behaviour)
     *  - 'screenshots' — content gallery screenshots (default on)
     *  - 'icon'        — the content icon/logo (default off — usually a brand mark)
     */
    public function appliesTo(string $surface): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        return match ($surface) {
            'media' => (bool) Setting::get('watermark_media', true),
            'screenshots' => (bool) Setting::get('watermark_screenshots', true),
            'icon' => (bool) Setting::get('watermark_icon', false),
            default => true,
        };
    }

    /** Apply the watermark only when the given surface is enabled. */
    public function applyTo(string $surface, string $absolutePath): bool
    {
        return $this->appliesTo($surface) && $this->apply($absolutePath);
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

            $sizePct = max(2.0, min(14.0, (float) (Setting::get('watermark_size', 5) ?: 5)));
            $fontSize = max(12, (int) round($w * $sizePct / 100));

            $opacity = max(8, min(95, (int) (Setting::get('watermark_opacity', 50) ?: 50)));
            $alpha = (int) round(127 - ($opacity / 100 * 127));
            $haloAlpha = (int) round(127 - (min(92, $opacity + 35) / 100 * 127));

            $white = imagecolorallocatealpha($img, 255, 255, 255, $alpha);
            $dark = imagecolorallocatealpha($img, 0, 0, 0, $haloAlpha);
            $off = max(2, (int) round($fontSize / 12));

            // Sharp stamp: a dark outline for contrast on ANY background, then the
            // white text drawn a few times at 1px offsets (faux-bold). The overlapping
            // passes also build up opacity, so it stays crisp even at low settings.
            $stamp = function (int $x, int $y, int $angle) use ($img, $fontSize, $font, $text, $white, $dark, $off) {
                foreach ([[-$off, 0], [$off, 0], [0, -$off], [0, $off], [-$off, -$off], [$off, $off], [-$off, $off], [$off, -$off]] as [$dx, $dy]) {
                    imagettftext($img, $fontSize, $angle, $x + $dx, $y + $dy, $dark, $font, $text);
                }
                foreach ([[0, 0], [1, 0], [0, 1], [1, 1]] as [$bx, $by]) {
                    imagettftext($img, $fontSize, $angle, $x + $bx, $y + $by, $white, $font, $text);
                }
            };

            $angle = 30;
            $box = imagettfbbox($fontSize, $angle, $font, $text);
            $textW = max(1, abs($box[2] - $box[0]));
            $textH = max(1, abs($box[7] - $box[1]));
            $stepX = max(140, (int) ($textW + $fontSize * 3));
            $stepY = max(110, (int) ($textH + $fontSize * 5));

            $tiled = (Setting::get('watermark_position', 'tiled') ?: 'tiled') === 'tiled';

            if ($tiled) {
                // Start the grid INSIDE the image so small/short images always get a
                // visible stamp (the old loop started its first row below the image).
                for ($y = max($textH, $fontSize); $y < $h + $textH; $y += $stepY) {
                    for ($x = -$stepX; $x < $w + $stepX; $x += $stepX) {
                        $stamp((int) $x, (int) $y, $angle);
                    }
                }
            } else {
                $cx = max($fontSize, (int) ($w - $textW - $fontSize));
                $cy = max($textH + $fontSize, (int) ($h - $fontSize));
                $stamp($cx, $cy, 0);
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
            base_path('resources/fonts/watermark.ttf'),
            // Bold variants first — a heavier glyph reads as a sharper stamp.
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/liberation/LiberationSans-Bold.ttf',
            'C:\\Windows\\Fonts\\arialbd.ttf',
            // Regular fallbacks.
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
