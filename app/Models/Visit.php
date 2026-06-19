<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    public const UPDATED_AT = null; // only created_at is tracked

    protected $fillable = [
        'visitor_id', 'ip_address', 'country', 'region', 'city',
        'browser', 'browser_version', 'os', 'device', 'is_bot',
        'path', 'referer_host', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
