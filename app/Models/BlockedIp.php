<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip', 'reason', 'type', 'auto', 'hits', 'expires_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'auto' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /** Active = not expired (null expiry means a permanent block). */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where(function ($w) {
            $w->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
