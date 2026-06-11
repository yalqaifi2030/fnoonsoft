<?php

namespace App\Filament\Member\Resources\SupportTicketResource\Pages;

use App\Filament\Concerns\HasTicketReply;
use App\Filament\Member\Resources\SupportTicketResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    use HasTicketReply;

    protected static string $resource = SupportTicketResource::class;

    protected static string $view = 'filament.tickets.conversation';

    public function getTitle(): string
    {
        return $this->record->number().' · '.$this->record->subject;
    }
}
