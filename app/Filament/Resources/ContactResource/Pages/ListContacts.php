<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactBlockResource;
use App\Filament\Resources\ContactResource;
use App\Models\Contact;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('blocked')
                ->label(__('message.spam.blocked_list'))
                ->icon('heroicon-m-no-symbol')
                ->color('gray')
                ->url(ContactBlockResource::getUrl()),
        ];
    }

    /** Inbox (real messages) vs Spam folder (auto-filtered, emptied after 30 days). */
    public function getTabs(): array
    {
        // Filament injects closure args by NAME — must be $query.
        $inbox = fn (Builder $query) => $query->where('is_spam', false);
        $spam = fn (Builder $query) => $query->where('is_spam', true);

        return [
            'inbox' => Tab::make(__('message.spam.inbox'))
                ->icon('heroicon-m-inbox')
                ->badge(Contact::where('is_spam', false)->where('is_read', false)->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing($inbox),
            'spam' => Tab::make(__('message.spam.folder'))
                ->icon('heroicon-m-shield-exclamation')
                ->badge(Contact::where('is_spam', true)->count() ?: null)
                ->badgeColor('gray')
                ->modifyQueryUsing($spam),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'inbox';
    }
}
