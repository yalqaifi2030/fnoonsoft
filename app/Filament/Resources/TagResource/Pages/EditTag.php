<?php

namespace App\Filament\Resources\TagResource\Pages;

use App\Filament\Resources\TagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTag extends EditRecord
{
    use \App\Filament\Concerns\TranslatableFormState;

    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
