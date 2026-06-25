<?php

namespace App\Filament\Resources\MobileAppResource\Pages;

use App\Filament\Resources\MobileAppResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMobileApp extends EditRecord
{
    use \App\Filament\Concerns\TranslatableFormState;
    use \App\Filament\Concerns\HandlesBigFileUpload;

    protected static string $resource = MobileAppResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['published_at'] = now();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->url(fn () => MobileAppResource::getUrl('view', ['record' => $this->record])),
            Actions\DeleteAction::make(),
        ];
    }
}
