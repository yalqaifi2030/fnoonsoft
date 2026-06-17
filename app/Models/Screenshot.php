<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Screenshot extends Model
{
    use HasFactory;

    protected $fillable = ['software_id', 'path', 'caption', 'sort_order'];

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    protected static function booted(): void
    {
        // Stamp the watermark on newly uploaded / replaced gallery images (the
        // service is a no-op when the 'screenshots' surface is off).
        static::saved(function (Screenshot $shot) {
            if (($shot->wasRecentlyCreated || $shot->wasChanged('path')) && $shot->path) {
                try {
                    app(\App\Services\WatermarkService::class)
                        ->applyTo('screenshots', Storage::disk('public')->path($shot->path));
                } catch (\Throwable $e) {
                    // a watermark must never break a save
                }
            }
        });

        // Remove the screenshot image from storage when the record is deleted.
        static::deleting(function (Screenshot $shot) {
            if ($shot->path) {
                try {
                    Storage::disk('public')->delete($shot->path);
                } catch (\Throwable $e) {
                    // never block the record delete
                }
            }
        });
    }
}
