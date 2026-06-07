<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'software_id', 'download_link_id', 'user_id', 'ip_address',
        'country', 'user_agent', 'referer', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function downloadLink(): BelongsTo
    {
        return $this->belongsTo(DownloadLink::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
