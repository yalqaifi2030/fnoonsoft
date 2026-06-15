<?php

namespace App\Filament\Resources\SoftwareResource\Pages;

use App\Filament\Resources\SoftwareResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSoftware extends CreateRecord
{
    use \App\Filament\Concerns\HandlesBigFileUpload;

    protected static string $resource = SoftwareResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] ??= auth()->id();
        $data['published_at'] = now(); // publish date = save time

        return $data;
    }
}
