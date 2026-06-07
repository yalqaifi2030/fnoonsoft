<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Screenshot extends Model
{
    use HasFactory;

    protected $fillable = ['software_id', 'path', 'caption', 'sort_order'];

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }
}
