<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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
                    Toggle::make('is_internal')
                        ->label(__('ticket.internal_note')),
                ])
                ->action(function (array $data): void {
                    $internal = (bool) ($data['is_internal'] ?? false);

                    $this->record->messages()->create([
                        'user_id' => auth()->id(),
                        'body' => $data['body'],
                        'attachment' => $data['attachment'] ?? null,
                        'is_staff' => true,
                        'is_internal' => $internal,
                    ]);

                    if (! $internal) {
                        $this->record->update(['status' => 'answered', 'last_reply_at' => now()]);
                        $this->record->notifyOwner('ticket.notify.reply_staff');
                    }

                    Notification::make()->success()->title(__('ticket.replied_ok'))->send();
                }),

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
