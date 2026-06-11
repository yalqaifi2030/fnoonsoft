<?php

namespace App\Filament\Member\Resources\SupportTicketResource\Pages;

use App\Filament\Member\Resources\SupportTicketResource;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected static string $view = 'filament.tickets.conversation';

    public function getTitle(): string
    {
        return $this->record->number().' · '.$this->record->subject;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label(__('ticket.reply'))
                ->icon('heroicon-m-paper-airplane')
                ->color('primary')
                ->form([
                    Textarea::make('body')
                        ->label(__('ticket.message'))
                        ->required()->rows(4)
                        ->placeholder(__('ticket.reply_hint')),
                    FileUpload::make('attachment')
                        ->label(__('ticket.attachment'))
                        ->disk('public')->directory('ticket-attachments')
                        ->maxSize(5120),
                ])
                ->action(function (array $data): void {
                    $this->record->messages()->create([
                        'user_id' => auth()->id(),
                        'body' => $data['body'],
                        'attachment' => $data['attachment'] ?? null,
                        'is_staff' => false,
                    ]);

                    $this->record->update([
                        'status' => 'open',
                        'last_reply_at' => now(),
                        'closed_at' => null,
                    ]);

                    $this->record->notifyStaff('ticket.notify.reply_member');

                    Notification::make()->success()->title(__('ticket.replied_ok'))->send();
                }),
        ];
    }
}
