<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Tag extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = ['name', 'slug'];

    public function software(): BelongsToMany
    {
        return $this->belongsToMany(Software::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
