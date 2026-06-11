<?php

namespace App\Filament\Member\Resources\SupportTicketResource\Pages;

use App\Filament\Member\Resources\SupportTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('ticket.new_ticket'))
                ->icon('heroicon-m-plus'),
        ];
    }
}
