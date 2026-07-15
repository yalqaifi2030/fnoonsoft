<?php

namespace App\Filament\Resources\BlockedIpResource\Pages;

use App\Filament\Resources\BlockedIpResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateBlockedIp extends CreateRecord
{
    protected static string $resource = BlockedIpResource::class;

    /** A hand-added block is manual + permanent, attributed to the current staff user. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['auto'] = false;
        $data['expires_at'] = null;
        $data['hits'] = 1;
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        Cache::forget('sec:blocked');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
