<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEvent extends Model
{
    use MassPrunable;
    use HasFactory;

    protected $fillable = [
        'ip', 'type', 'severity', 'method', 'path', 'detail',
        'user_id', 'country', 'user_agent', 'blocked',
    ];

    protected function casts(): array
    {
        return ['blocked' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Security console history: kept six months. Pruned daily by model:prune. */
    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays(180));
    }
}
