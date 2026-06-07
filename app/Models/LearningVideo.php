<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class LearningVideo extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'learning_category_id', 'title', 'description', 'url', 'source', 'file_path',
        'duration', 'level', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class, 'learning_category_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    // --- Source helpers --------------------------------------------------

    public function isYoutube(): bool
    {
        return ($this->source ?? 'youtube') === 'youtube';
    }

    public function isUpload(): bool
    {
        return $this->source === 'upload';
    }

    /** Extract the YouTube video id from a full URL or a bare id. */
    public function youtubeId(): ?string
    {
        $url = trim((string) $this->url);
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
        if ($this->isUpload()) {
            return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
        }

        return $this->url ?: null;
    }

    /** 'youtube' → embed iframe, otherwise 'video' → HTML5 player. */
    public function playerType(): string
    {
        return $this->isYoutube() ? 'youtube' : 'video';
    }

    /** The value the front-end player needs: a YouTube id, or a direct URL. */
    public function playerSrc(): ?string
    {
        return $this->isYoutube() ? $this->youtubeId() : $this->videoSrc();
    }

    public function thumbnailUrl(): ?string
    {
        $id = $this->isYoutube() ? $this->youtubeId() : null;

        return $id ? "https://img.youtube.com/vi/{$id}/hqdefault.jpg" : null;
    }

    public function embedUrl(): ?string
    {
        $id = $this->youtubeId();

        return $id ? "https://www.youtube-nocookie.com/embed/{$id}" : null;
    }
}
