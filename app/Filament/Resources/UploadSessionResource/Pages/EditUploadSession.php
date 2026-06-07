<?php

namespace App\Filament\Resources\UploadSessionResource\Pages;

use App\Filament\Resources\UploadSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUploadSession extends EditRecord
{
    protected static string $resource = UploadSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
