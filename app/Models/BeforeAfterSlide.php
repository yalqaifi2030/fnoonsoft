<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A single "before / after" comparison (two images OR two videos) attached to a
 * software product and shown as an interactive drag-to-reveal slider.
 */
class BeforeAfterSlide extends Model
{
    protected $fillable = [
        'software_id', 'media_type', 'before_path', 'after_path',
        'before_label', 'after_label', 'caption', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }

    public function beforeUrl(): ?string
    {
        return $this->before_path ? Storage::disk('public')->url($this->before_path) : null;
    }

    public function afterUrl(): ?string
    {
        return $this->after_path ? Storage::disk('public')->url($this->after_path) : null;
    }

    protected static function booted(): void
    {
        // Watermark the before/after IMAGES (videos are left untouched). Honours
        // the admin's 'screenshots' scope toggle and never breaks a save.
        static::saved(function (BeforeAfterSlide $slide) {
            if ($slide->media_type !== 'image') {
                return;
            }

            $changed = $slide->wasRecentlyCreated
                || $slide->wasChanged('before_path')
                || $slide->wasChanged('after_path');

            if (! $changed) {
                return;
            }

            foreach ([$slide->before_path, $slide->after_path] as $path) {
                if ($path) {
                    try {
                        app(\App\Services\WatermarkService::class)
                            ->applyTo('screenshots', Storage::disk('public')->path($path));
                    } catch (\Throwable $e) {
                        // a watermark must never break a save
                    }
                }
            }
        });

        // Clean the stored files when a slide is removed.
        static::deleting(function (BeforeAfterSlide $slide) {
            foreach ([$slide->before_path, $slide->after_path] as $path) {
                if ($path) {
                    try {
                        Storage::disk('public')->delete($path);
                    } catch (\Throwable $e) {
                        // never block the record delete
                    }
                }
            }
        });
    }
}
