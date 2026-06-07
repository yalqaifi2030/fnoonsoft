<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class LabItem extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['title', 'description'];

    protected $fillable = ['interactive_lab_id', 'title', 'description', 'data', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(InteractiveLab::class, 'interactive_lab_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** Convenience getter for a data key. */
    public function d(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }
}
