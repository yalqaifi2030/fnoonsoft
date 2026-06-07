<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class LearningCategory extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = ['name', 'slug', 'description', 'icon', 'color', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function videos(): HasMany
    {
        return $this->hasMany(LearningVideo::class)->orderBy('sort_order');
    }

    public function activeVideos(): HasMany
    {
        return $this->videos()->where('is_active', true);
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
