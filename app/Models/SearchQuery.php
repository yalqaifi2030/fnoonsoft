<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    use HasFactory;

    protected $fillable = ['term', 'results_count', 'hits', 'request_count', 'last_searched_at'];

    protected function casts(): array
    {
        return ['last_searched_at' => 'datetime'];
    }
}

