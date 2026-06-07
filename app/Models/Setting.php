<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.all'));
        static::deleted(fn () => Cache::forget('settings.all'));
    }

    /** @return array<string,mixed> */
    public static function allCached(): array
    {
        return Cache::rememberForever('settings.all', function () {
            return static::all()->mapWithKeys(fn (self $s) => [
                $s->key => static::castValue($s->value, $s->type),
            ])->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allCached()[$key] ?? $default;
    }

    /**
     * Locale-aware text setting. Stored as JSON {en,ar}; falls back to the
     * given default (usually the lang() string) when unset/empty.
     */
    public static function text(string $key, string $default = ''): string
    {
        $value = static::get($key);

        if (is_array($value)) {
            $value = $value[app()->getLocale()] ?? ($value['en'] ?? '');
        }

        return filled($value) ? (string) $value : $default;
    }

    public static function put(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
                'group' => $group,
            ],
        );
    }

    /** Store a secret encrypted at rest (read back transparently via get()). */
    public static function putSecret(string $key, ?string $plain, string $group = 'general'): void
    {
        static::put($key, filled($plain) ? Crypt::encryptString($plain) : '', 'encrypted', $group);
    }

    protected static function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value ?? '[]', true),
            'encrypted' => filled($value) ? rescue(fn () => Crypt::decryptString($value), '', false) : '',
            default => $value,
        };
    }
}
