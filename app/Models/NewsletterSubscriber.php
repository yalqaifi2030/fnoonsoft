<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'locale', 'is_confirmed', 'token', 'confirmed_at'];

    protected function casts(): array
    {
        return [
            'is_confirmed' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }
}
