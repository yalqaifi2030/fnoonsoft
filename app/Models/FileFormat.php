<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * A design/3D file format (e.g. .3dm, .dwg, .psd) — the central library that is
 * linked to the software that opens/produces it, and shown as a professional
 * badge on the product page + the /formats reference guide.
 */
class FileFormat extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'extension', 'name', 'description', 'family', 'color', 'is_active', 'sort_order',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public const FAMILIES = ['autodesk', 'adobe', 'rhino', 'lumion', 'other'];

    public function software(): BelongsToMany
    {
        return $this->belongsToMany(Software::class);
    }

    /** ".3DM" — the dotted, upper-cased extension for display. */
    public function ext(): string
    {
        return '.'.strtoupper((string) $this->extension);
    }

    public function badgeColor(): string
    {
        return $this->color ?: '#006C35';
    }
}
