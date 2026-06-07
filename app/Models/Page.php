<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['title', 'body', 'meta_title', 'meta_description'];

    protected $fillable = [
        'title', 'slug', 'body', 'is_published', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
