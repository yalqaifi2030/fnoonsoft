<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'software_id', 'software_version_id', 'label', 'type', 'os',
        'architecture', 'is_portable', 'r2_key', 'external_url',
        'original_filename', 'size_bytes', 'checksum_sha256', 'checksum_md5',
        'downloads_count', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_portable' => 'boolean',
            'is_active' => 'boolean',
            'size_bytes' => 'integer',
        ];
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(SoftwareVersion::class, 'software_version_id');
    }

    public function isExternal(): bool
    {
        return $this->type === 'external';
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes <= 0) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), 2).' '.$units[$i];
    }
}
