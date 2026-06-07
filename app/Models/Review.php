<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'software_id', 'user_id', 'rating', 'title', 'body', 'status',
    ];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved');
    }
}
