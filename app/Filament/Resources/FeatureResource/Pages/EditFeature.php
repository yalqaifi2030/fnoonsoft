<?php

namespace App\Filament\Resources\FeatureResource\Pages;

use App\Filament\Resources\FeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFeature extends EditRecord
{
    use \App\Filament\Concerns\TranslatableFormState;

    protected static string $resource = FeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
