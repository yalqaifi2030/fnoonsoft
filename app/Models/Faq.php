<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Faq extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['question', 'answer'];

    protected $fillable = ['question', 'answer', 'group', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
