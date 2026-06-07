<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class Software extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $table = 'software';

    public array $translatable = [
        'name', 'short_description', 'description', 'meta_title', 'meta_description',
    ];

    protected $fillable = [
        'content_type', 'name', 'slug', 'short_description', 'description',
        'category_id', 'developer_id', 'user_id', 'icon', 'current_version',
        'os_support', 'license_type', 'price', 'languages', 'meta', 'features',
        'code', 'code_language', 'video_source', 'video_url', 'video_path',
        'status', 'is_featured', 'is_editor_choice', 'is_malware_free',
        'downloads_count', 'reviews_count', 'rating_avg', 'views_count',
        'meta_title', 'meta_description', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'content_type' => ContentType::class,
            'status' => ContentStatus::class,
            'os_support' => 'array',
            'languages' => 'array',
            'meta' => 'array',
            'features' => 'array',
            'price' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_editor_choice' => 'boolean',
            'is_malware_free' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    // --- Relationships ---------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SoftwareVersion::class);
    }

    public function currentVersion(): HasMany
    {
        return $this->versions()->where('is_current', true);
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(Screenshot::class)->orderBy('sort_order');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(SystemRequirement::class);
    }

    public function downloadLinks(): HasMany
    {
        return $this->hasMany(DownloadLink::class)->where('is_active', true)->orderBy('sort_order');
    }

    /** True when any of this software's files was scanned clean by VirusTotal. */
    public function virusScanClean(): bool
    {
        try {
            $keys = $this->downloadLinks->pluck('r2_key')->filter()->all();

            return $keys
                && \App\Models\UploadSession::whereIn('r2_key', $keys)->where('scan_result', 'clean')->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Schema.org SoftwareApplication structured data (JSON-LD) for rich results. */
    public function structuredData(): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => $this->name,
            'description' => $this->short_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $this->description), 300),
            'url' => route('software.show', $this),
            'applicationCategory' => $this->content_type?->label() ?? 'Application',
            'softwareVersion' => $this->current_version ?: null,
            'operatingSystem' => (is_array($this->os_support) && $this->os_support) ? implode(', ', $this->os_support) : null,
            'datePublished' => $this->published_at?->toDateString(),
            'image' => $this->icon ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->icon) : null,
            'offers' => [
                '@type' => 'Offer',
                'price' => $this->isPaid() ? (string) $this->price : '0',
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock',
            ],
        ];

        if ($this->reviews_count > 0 && (float) $this->rating_avg > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $this->rating_avg,
                'reviewCount' => (string) $this->reviews_count,
                'bestRating' => '5',
            ];
        }

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'approved');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function approvedComments(): MorphMany
    {
        return $this->comments()->where('status', 'approved')->latest();
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function downloadLogs(): HasMany
    {
        return $this->hasMany(DownloadLog::class);
    }

    public function uploadSessions(): HasMany
    {
        return $this->hasMany(UploadSession::class);
    }

    // --- Scopes ----------------------------------------------------------

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', ContentStatus::Published->value);
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('content_type', $type);
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }

    // --- Helpers ---------------------------------------------------------

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPaid(): bool
    {
        return $this->license_type === 'paid';
    }

    // --- Code viewer -----------------------------------------------------

    public function hasCode(): bool
    {
        return filled($this->code);
    }

    /** Prism.js language token (defaults to plain text). */
    public function codeLang(): string
    {
        return $this->code_language ?: 'none';
    }

    // --- Explanation video ----------------------------------------------

    public function hasVideo(): bool
    {
        return $this->isYoutube()
            ? filled($this->video_url)
            : filled($this->video_url) || filled($this->video_path);
    }

    public function isYoutube(): bool
    {
        return ($this->video_source ?? 'youtube') === 'youtube';
    }

    public function isUploadedVideo(): bool
    {
        return $this->video_source === 'upload';
    }

    /** Extract the YouTube video id from a full URL or a bare id. */
    public function youtubeId(): ?string
    {
        $url = trim((string) $this->video_url);
        if ($url === '') {
            return null;
        }
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
            return $url;
        }
        if (preg_match('%(?:youtu\.be/|v=|/embed/|/shorts/)([A-Za-z0-9_-]{11})%', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    /** A direct, playable URL for non-YouTube sources (external link or upload). */
    public function videoSrc(): ?string
    {
        if ($this->isUploadedVideo()) {
            return $this->video_path ? Storage::disk('public')->url($this->video_path) : null;
        }

        return $this->video_url ?: null;
    }

    /** 'youtube' → embed iframe, otherwise 'video' → HTML5 player. */
    public function videoPlayerType(): string
    {
        return $this->isYoutube() ? 'youtube' : 'video';
    }

    public function youtubeEmbedUrl(): ?string
    {
        $id = $this->youtubeId();

        return $id ? "https://www.youtube-nocookie.com/embed/{$id}" : null;
    }
}
