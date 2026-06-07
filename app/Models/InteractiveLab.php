<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class InteractiveLab extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['title', 'description'];

    protected $fillable = ['key', 'slug', 'title', 'description', 'icon', 'color', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabItem::class)->orderBy('sort_order');
    }

    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
