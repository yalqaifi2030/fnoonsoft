<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'locale', 'is_confirmed', 'token', 'confirmed_at'];

    protected function casts(): array
    {
        return [
            'is_confirmed' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }

    /** Active (subscribed) recipients only. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_confirmed', true);
    }

    /** Personal one-click unsubscribe link (token auto-generated if missing). */
    public function unsubscribeUrl(): string
    {
        if (empty($this->token)) {
            $this->forceFill(['token' => Str::random(40)])->saveQuietly();
        }

        return route('newsletter.unsubscribe', $this->token);
    }
}
