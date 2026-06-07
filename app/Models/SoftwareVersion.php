<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class SoftwareVersion extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['changelog'];

    protected $fillable = [
        'software_id', 'version', 'changelog', 'released_at', 'is_current', 'is_beta',
    ];

    protected function casts(): array
    {
        return [
            'released_at' => 'date',
            'is_current' => 'boolean',
            'is_beta' => 'boolean',
        ];
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function downloadLinks(): HasMany
    {
        return $this->hasMany(DownloadLink::class);
    }
}
