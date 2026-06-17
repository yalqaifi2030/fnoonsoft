<?php

namespace App\Filament\Resources\InteractiveLabResource\Pages;

use App\Filament\Resources\InteractiveLabResource;
use App\Models\InteractiveLab;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateInteractiveLab extends CreateRecord
{
    protected static string $resource = InteractiveLabResource::class;

    /**
     * Auto-derive a unique slug + key. The key has no hand-built partial, so the
     * lab is rendered by the generic block engine and is fully admin-managed.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $base = Str::slug($data['title'] ?? '') ?: 'lab-'.Str::lower(Str::random(6));

        $slug = $base;
        $i = 2;
        while (InteractiveLab::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        $data['slug'] = $slug;
        $data['key'] = $slug;

        return $data;
    }

    /** Land on the edit page so the admin can immediately add content blocks. */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
