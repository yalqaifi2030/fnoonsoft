<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'icon',
        'content_type', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function software(): HasMany
    {
        return $this->hasMany(Software::class);
    }

    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
