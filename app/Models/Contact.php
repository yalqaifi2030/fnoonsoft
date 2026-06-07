<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'subject', 'message', 'ip_address', 'is_read'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }
}
