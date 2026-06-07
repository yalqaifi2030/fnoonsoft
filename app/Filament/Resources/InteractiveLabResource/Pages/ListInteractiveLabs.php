<?php

namespace App\Filament\Resources\InteractiveLabResource\Pages;

use App\Filament\Resources\InteractiveLabResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInteractiveLabs extends ListRecords
{
    protected static string $resource = InteractiveLabResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
