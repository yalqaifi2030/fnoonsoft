<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'commentable_id', 'commentable_type', 'user_id',
        'author_name', 'author_email', 'parent_id', 'body', 'status',
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Display name: the registered user's name, or the guest-supplied name. */
    public function displayName(): string
    {
        return $this->user?->name ?: ($this->author_name ?: 'Guest');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
