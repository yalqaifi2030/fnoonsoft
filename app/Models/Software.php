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
        'name', 'short_description', 'description', 'meta_title', 'meta_description', 'notice_text',
    ];

    protected $fillable = [
        'content_type', 'name', 'slug', 'short_description', 'description',
        'category_id', 'developer_id', 'user_id', 'icon', 'current_version',
        'os_support', 'license_type', 'price', 'languages', 'meta', 'features',
        'code', 'code_language', 'video_source', 'video_url', 'video_path',
        'status', 'is_featured', 'is_editor_choice', 'is_malware_free',
        'downloads_count', 'reviews_count', 'rating_avg', 'views_count',
        'meta_title', 'meta_description', 'published_at',
        'notice_enabled', 'notice_type', 'notice_text', 'notice_url',
        'model_glb', 'model_usdz', 'model_poster',
        'live_preview_url', 'appetize_public_key', 'preview_username', 'preview_password',
        'play_url', 'appstore_url', 'qr_enabled',
        'download_requires_login',
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
            'download_requires_login' => 'boolean',
            'qr_enabled' => 'boolean',
            'notice_enabled' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * On delete, remove every stored file so nothing is orphaned on iDrive:
     * download-link files, upload sessions, screenshots, icon and video. Children
     * are deleted via Eloquent so each cleans its own object (soft-delete means
     * DB cascade won't fire here).
     */
    protected static function booted(): void
    {
        // Optionally watermark the content icon when it's set/replaced (off by
        // default — the service skips it unless the 'icon' surface is enabled).
        static::saved(function (Software $software) {
            if ($software->wasChanged('icon') && $software->icon) {
                try {
                    app(\App\Services\WatermarkService::class)
                        ->applyTo('icon', Storage::disk('public')->path($software->icon));
                } catch (\Throwable $e) {
                    // never break a save over a watermark
                }
            }
        });

        // Newsletter: email subscribers ONCE when an item is first published
        // (new item, or draft → published). Editing a live item never re-sends.
        static::saved(function (Software $software) {
            if ($software->status !== ContentStatus::Published) {
                return;
            }
            if (! ($software->wasRecentlyCreated || $software->wasChanged('status'))) {
                return;
            }

            $meta = $software->meta ?? [];
            if (! empty($meta['newsletter_sent']) || ! Setting::get('newsletter_auto_enabled', true)) {
                return;
            }

            try {
                $summary = trim(strip_tags((string) $software->short_description));
                \App\Jobs\SendNewsletter::dispatch(
                    __('newsletter.release_subject', ['name' => $software->name]),
                    __('newsletter.release_heading', ['name' => $software->name]),
                    nl2br(e(\Illuminate\Support\Str::limit($summary, 300))) ?: e((string) $software->name),
                    route('software.show', $software),
                    __('newsletter.release_cta'),
                );

                $meta['newsletter_sent'] = true;
                $software->forceFill(['meta' => $meta])->saveQuietly();
            } catch (\Throwable $e) {
                // a newsletter must never break publishing
            }
        });

        static::deleting(function (Software $software) {
            $software->uploadSessions()->get()->each->delete();
            DownloadLink::where('software_id', $software->id)->get()->each->delete();
            $software->screenshots()->get()->each->delete();
            $software->beforeAfterSlides()->get()->each->delete();

            foreach ([$software->icon, $software->video_path] as $path) {
                if ($path) {
                    try {
                        Storage::disk('public')->delete($path);
                    } catch (\Throwable $e) {
                        // never block the delete
                    }
                }
            }
        });
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

    public function beforeAfterSlides(): HasMany
    {
        return $this->hasMany(BeforeAfterSlide::class)->orderBy('sort_order');
    }

    public function activeBeforeAfterSlides(): HasMany
    {
        return $this->beforeAfterSlides()->where('is_active', true);
    }

    public function fileFormats(): BelongsToMany
    {
        return $this->belongsToMany(FileFormat::class)->orderBy('sort_order');
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

    /** Recompute the cached star average + count from APPROVED reviews only. */
    public function recomputeRating(): void
    {
        $approved = $this->reviews()->where('status', 'approved');
        $count = (clone $approved)->count();
        $avg = $count ? round((float) (clone $approved)->avg('rating'), 2) : 0;

        $this->forceFill(['reviews_count' => $count, 'rating_avg' => $avg])->saveQuietly();
    }

    // --- Code viewer -----------------------------------------------------

    public function hasCode(): bool
    {
        return filled($this->code);
    }

    // --- Live web preview (interactive app in a phone frame) -------------

    public function hasLivePreview(): bool
    {
        return filled($this->live_preview_url) || filled($this->appetize_public_key);
    }

    /** True when the preview is a real-device cloud emulator (Appetize), not a web build. */
    public function isEmulatorPreview(): bool
    {
        return blank($this->live_preview_url) && filled($this->appetize_public_key);
    }

    /** The iframe src for the live preview — a web build URL, or an Appetize embed. */
    public function livePreviewSrc(): ?string
    {
        if (filled($this->live_preview_url)) {
            return $this->live_preview_url;
        }

        $key = trim((string) $this->appetize_public_key);
        if ($key === '') {
            return null;
        }
        if (str_starts_with($key, 'http')) {
            return $key; // a full embed URL was pasted
        }

        return 'https://appetize.io/embed/'.$key
            .'?device=pixel7&osVersion=13.0&scale=auto&autoplay=true&screenOnly=true&deviceColor=black';
    }

    public function hasStoreLinks(): bool
    {
        return filled($this->play_url) || filled($this->appstore_url);
    }

    /** Demo login shown next to the live preview (not real secrets). */
    public function hasPreviewCredentials(): bool
    {
        return filled($this->preview_username) || filled($this->preview_password);
    }

    // --- 3D model preview (model-viewer) ---------------------------------

    public function has3dModel(): bool
    {
        return filled($this->model_glb);
    }

    /** Lower-cased extension of the preview model (glb | gltf | obj). */
    public function modelExt(): string
    {
        return strtolower(pathinfo((string) $this->model_glb, PATHINFO_EXTENSION));
    }

    public function is3dObj(): bool
    {
        return $this->modelExt() === 'obj';
    }

    public function modelGlbUrl(): ?string
    {
        return $this->model_glb ? Storage::disk('public')->url($this->model_glb) : null;
    }

    public function modelUsdzUrl(): ?string
    {
        return $this->model_usdz ? Storage::disk('public')->url($this->model_usdz) : null;
    }

    public function modelPosterUrl(): ?string
    {
        return $this->model_poster ? Storage::disk('public')->url($this->model_poster) : null;
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
