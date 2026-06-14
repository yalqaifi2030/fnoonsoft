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

    /** Remove the screenshot image from storage when the record is deleted. */
    protected static function booted(): void
    {
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
