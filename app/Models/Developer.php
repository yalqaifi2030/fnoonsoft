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
        'name', 'slug', 'website', 'logo', 'description', 'is_verified',
    ];

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
