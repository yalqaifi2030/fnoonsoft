<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Developer extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['description'];

    protected $fillable = [
        'name', 'slug', 'website', 'email', 'phone', 'twitter', 'logo', 'description', 'is_verified',
    ];

    /** Whether any contact channel is available. */
    public function hasContact(): bool
    {
        return filled($this->website) || filled($this->email) || filled($this->phone) || filled($this->twitter);
    }

    /** Normalised WhatsApp link from the phone (digits only). */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        return $digits ? 'https://wa.me/'.$digits : null;
    }

    /** Normalised X/Twitter URL from a handle or full URL. */
    public function twitterUrl(): ?string
    {
        $t = trim((string) $this->twitter);
        if ($t === '') {
            return null;
        }
        if (str_starts_with($t, 'http')) {
            return $t;
        }

        return 'https://x.com/'.ltrim($t, '@');
    }

    protected function casts(): array
    {
        return ['is_verified' => 'boolean'];
    }

    public function software(): HasMany
    {
        return $this->hasMany(Software::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
