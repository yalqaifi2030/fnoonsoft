<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Feature extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['title', 'description'];

    protected $fillable = ['icon', 'title', 'description', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
