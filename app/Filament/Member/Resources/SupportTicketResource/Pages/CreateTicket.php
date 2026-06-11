<?php

namespace App\Filament\Member\Resources\SupportTicketResource\Pages;

use App\Filament\Member\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $ticket = SupportTicket::create([
            'user_id' => auth()->id(),
            'subject' => $data['subject'],
            'category' => $data['category'],
            'priority' => $data['priority'],
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'body' => $data['body'],
            'attachment' => $data['attachment'] ?? null,
            'is_staff' => false,
        ]);

        $ticket->notifyStaff('ticket.notify.new');

        Notification::make()->success()->title(__('ticket.created_ok'))->send();

        return $ticket;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null; // we send our own above
    }

    protected function getRedirectUrl(): string
    {
        return SupportTicketResource::getUrl('view', ['record' => $this->record]);
    }
}
