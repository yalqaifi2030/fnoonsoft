<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A sender the contact form silently ignores: an email, a whole domain, or an IP. */
class ContactBlock extends Model
{
    protected $fillable = ['type', 'value', 'reason', 'hits', 'last_hit_at', 'created_by'];

    protected function casts(): array
    {
        return ['last_hit_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
