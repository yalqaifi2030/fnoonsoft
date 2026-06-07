<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Translatable\HasTranslations;

class Article extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['title', 'excerpt', 'body', 'meta_title', 'meta_description'];

    protected $fillable = [
        'article_category_id', 'user_id', 'title', 'slug', 'excerpt', 'body',
        'cover_image', 'status', 'views_count', 'published_at',
        'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
