<?php

namespace App\Filament\Resources\MobileAppResource\Pages;

use App\Filament\Resources\MobileAppResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMobileApps extends ListRecords
{
    protected static string $resource = MobileAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
