<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'software_id', 'os', 'tier', 'processor', 'memory',
        'storage', 'graphics', 'os_version', 'notes',
    ];

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }
}
