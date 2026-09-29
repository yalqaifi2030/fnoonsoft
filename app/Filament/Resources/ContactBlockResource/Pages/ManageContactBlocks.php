<?php

namespace App\Filament\Resources\ContactBlockResource\Pages;

use App\Filament\Resources\ContactBlockResource;
use App\Filament\Resources\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageContactBlocks extends ManageRecords
{
    protected static string $resource = ContactBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label(__('nav.contacts'))
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('gray')
                ->url(ContactResource::getUrl()),
            Actions\CreateAction::make()
                ->icon('heroicon-m-no-symbol')
                ->mutateFormDataUsing(fn (array $data) => $data + ['created_by' => auth()->id()]),
        ];
    }
}
