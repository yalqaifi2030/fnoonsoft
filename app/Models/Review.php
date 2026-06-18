<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'software_id', 'user_id', 'author_name', 'rating', 'title', 'body', 'status',
    ];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    /** Keep the parent software's star average + count in sync on any change. */
    protected static function booted(): void
    {
        static::saved(fn (Review $r) => $r->software?->recomputeRating());
        static::deleted(fn (Review $r) => $r->software?->recomputeRating());
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved');
    }

    /** Display name: the linked user, else the guest-entered name, else a fallback. */
    public function authorName(): string
    {
        return $this->user?->displayName()
            ?: ($this->author_name ?: __('review.guest'));
    }
}
