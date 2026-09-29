<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;

    /** Opening a message marks it read. */
    protected function afterFill(): void
    {
        if (! $this->record->is_read) {
            $this->record->forceFill(['is_read' => true])->saveQuietly();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ContactResource::blockAction(Actions\Action::make('block')),
            Actions\DeleteAction::make(),
        ];
    }
}
