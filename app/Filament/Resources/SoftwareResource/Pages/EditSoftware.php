<?php

namespace App\Filament\Resources\SoftwareResource\Pages;

use App\Filament\Resources\SoftwareResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoftware extends EditRecord
{
    use \App\Filament\Concerns\TranslatableFormState;
    use \App\Filament\Concerns\HandlesBigFileUpload;

    protected static string $resource = SoftwareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
