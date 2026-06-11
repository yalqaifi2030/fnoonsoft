<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Concerns\HasTicketReply;
use App\Filament\Resources\SupportTicketResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('close')
                ->label(__('ticket.close'))
                ->icon('heroicon-m-check-circle')->color('gray')
                ->visible(fn () => $this->record->status !== 'closed')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['status' => 'closed', 'closed_at' => now()]);
                    Notification::make()->success()->title(__('ticket.closed_ok'))->send();
                }),

            Action::make('reopen')
                ->label(__('ticket.reopen'))
                ->icon('heroicon-m-arrow-path')->color('warning')
                ->visible(fn () => $this->record->status === 'closed')
                ->action(function (): void {
                    $this->record->update(['status' => 'open', 'closed_at' => null]);
                    Notification::make()->success()->title(__('ticket.reopened_ok'))->send();
                }),
        ];
    }
}
