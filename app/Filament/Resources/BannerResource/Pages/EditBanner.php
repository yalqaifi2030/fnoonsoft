<?php

namespace App\Filament\Resources\BannerResource\Pages;

use App\Filament\Resources\BannerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBanner extends EditRecord
{
    use \App\Filament\Concerns\TranslatableFormState;

    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
