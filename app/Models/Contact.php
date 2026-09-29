<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;
    use MassPrunable;

    protected $fillable = [
        'name', 'email', 'subject', 'message', 'ip_address', 'user_agent', 'country',
        'is_read', 'is_spam', 'spam_score', 'spam_reasons',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'is_spam' => 'boolean',
            'spam_reasons' => 'array',
        ];
    }

    /** Spam folder is emptied after 30 days (model:prune, daily). */
    public function prunable()
    {
        return static::where('is_spam', true)->where('created_at', '<', now()->subDays(30));
    }
}
