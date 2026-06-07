<?php

namespace App\Filament\Resources\UploadSessionResource\Pages;

use App\Filament\Resources\UploadSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUploadSessions extends ListRecords
{
    protected static string $resource = UploadSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
