<?php

namespace App\Models;

use App\Enums\UploadStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class UploadSession extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'uuid', 'user_id', 'software_id', 'original_name', 'mime_type', 'size_bytes',
        'r2_key', 'r2_upload_id', 'storage_disk', 'proxied', 'part_size', 'parts_total', 'parts_completed',
        'parts', 'status', 'checksum_sha256', 'checksum_md5', 'scan_result',
        'scan_report', 'error_message', 'completed_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'parts' => 'array',
            'size_bytes' => 'integer',
            'proxied' => 'boolean',
            'status' => UploadStatus::class,
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** Use a separate uuid column, keep the default auto-increment id. */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    /** The shareable asset minted when this upload completed (if any). */
    public function asset(): HasOne
    {
        return $this->hasOne(Asset::class);
    }

    public function progressPercent(): int
    {
        if ($this->parts_total <= 0) {
            return 0;
        }

        return (int) floor(($this->parts_completed / $this->parts_total) * 100);
    }

    /** Remove the stored object (and the minted asset) when a session is deleted. */
    protected static function booted(): void
    {
        static::deleting(function (UploadSession $session) {
            // The asset shares the same object — its own hook cleans it + variants.
            $session->asset?->delete();

            if ($session->r2_key) {
                try {
                    Storage::disk($session->storage_disk ?: 'r2')->delete($session->r2_key);
                } catch (\Throwable $e) {
                    // disk offline — never block the record delete
                }
            }
        });
    }
}
