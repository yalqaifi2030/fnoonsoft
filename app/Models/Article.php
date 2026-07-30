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

    /** Schema.org BlogPosting structured data (JSON-LD) for rich results. */
    public function structuredData(): array
    {
        $seoName = \App\Models\Setting::text('site_name', config('app.name'));
        $image = $this->cover_image
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->cover_image)
            : (\App\Support\SiteBranding::logo() ?: null);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => \Illuminate\Support\Str::limit((string) $this->title, 110, ''),
            'description' => $this->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $this->body), 200),
            'image' => $image,
            'datePublished' => $this->published_at?->toAtomString(),
            'dateModified' => ($this->updated_at ?? $this->published_at)?->toAtomString(),
            'author' => [
                '@type' => 'Person',
                'name' => $this->author?->name ?: $seoName,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $seoName,
                'logo' => ['@type' => 'ImageObject', 'url' => \App\Support\SiteBranding::logo() ?: asset('favicon.ico')],
            ],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => route('blog.show', $this)],
            'articleSection' => $this->category?->name,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }
}
