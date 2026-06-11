<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Notification;
use Livewire\WithFileUploads;

/**
 * Inline reply box for a ticket conversation page (ViewRecord). Plain Livewire
 * state (textarea + optional file + staff "internal" flag) instead of a modal.
 */
trait HasTicketReply
{
    use WithFileUploads;

    public string $replyBody = '';

    public bool $replyInternal = false;

    public $replyFile = null;

    public function submitReply(): void
    {
        $this->validate(
            [
                'replyBody' => 'required|string|max:5000',
                'replyFile' => 'nullable|file|max:5120',
            ],
            ['replyBody.required' => __('ticket.reply_required')],
        );

        $isStaff = (bool) auth()->user()?->isStaff();
        $internal = $isStaff && $this->replyInternal;

        $path = $this->replyFile
            ? $this->replyFile->store('ticket-attachments', 'public')
            : null;

        $this->record->messages()->create([
            'user_id' => auth()->id(),
            'body' => $this->replyBody,
            'attachment' => $path,
            'is_staff' => $isStaff,
            'is_internal' => $internal,
        ]);

        if ($isStaff) {
            if (! $internal) {
                $this->record->update(['status' => 'answered', 'last_reply_at' => now()]);
                $this->record->notifyOwner('ticket.notify.reply_staff');
            }
        } else {
            $this->record->update(['status' => 'open', 'last_reply_at' => now(), 'closed_at' => null]);
            $this->record->notifyStaff('ticket.notify.reply_member');
        }

        $this->reset(['replyBody', 'replyInternal', 'replyFile']);
        $this->record->refresh();

        Notification::make()->success()->title(__('ticket.replied_ok'))->send();
    }
}
