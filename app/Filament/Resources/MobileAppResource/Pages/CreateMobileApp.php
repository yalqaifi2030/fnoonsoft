<?php

namespace App\Filament\Resources\MobileAppResource\Pages;

use App\Enums\ContentType;
use App\Filament\Resources\MobileAppResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMobileApp extends CreateRecord
{
    use \App\Filament\Concerns\HandlesBigFileUpload;

    protected static string $resource = MobileAppResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['content_type'] = ContentType::MobileApp->value;
        $data['user_id'] ??= auth()->id();
        $data['published_at'] = now();

        return $data;
    }
}
