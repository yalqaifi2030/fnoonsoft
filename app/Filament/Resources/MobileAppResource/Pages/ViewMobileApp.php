<?php

namespace App\Filament\Resources\MobileAppResource\Pages;

use App\Filament\Resources\MobileAppResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMobileApp extends ViewRecord
{
    use \App\Filament\Concerns\TranslatableFormState;

    protected static string $resource = MobileAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
