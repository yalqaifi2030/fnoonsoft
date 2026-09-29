<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\RelationManagers;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Support\SpamGuard;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.engagement');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.contacts');
    }

    public static function getModelLabel(): string
    {
        return __('nav.contact_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.contacts');
    }

    /** Unread real messages only — the Spam folder never nags. */
    public static function getNavigationBadge(): ?string
    {
        return (string) (Contact::where('is_read', false)->where('is_spam', false)->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('message.section.message'))
                    ->icon('heroicon-o-envelope-open')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label(__('message.subject'))
                            ->maxLength(180)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('message')
                            ->label(__('message.message'))
                            ->required()
                            ->rows(8)
                            ->columnSpanFull(),
                    ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('message.section.sender'))
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('message.name'))
                            ->required()
                            ->prefixIcon('heroicon-m-user'),

                        Forms\Components\TextInput::make('email')
                            ->label(__('message.email'))
                            ->email()
                            ->required()
                            ->prefixIcon('heroicon-m-envelope'),

                        Forms\Components\Placeholder::make('ip_address')
                            ->label(__('message.ip'))
                            ->content(fn (?Contact $record) => $record?->ip_address ?? '—'),

                        Forms\Components\Placeholder::make('received')
                            ->label(__('message.received'))
                            ->content(fn (?Contact $record) => $record?->created_at?->diffForHumans() ?? '—'),

                        Forms\Components\Placeholder::make('origin')
                            ->label(__('message.spam.origin'))
                            ->content(fn (?Contact $record) => trim(($record?->country ? $record->country.' · ' : '').Str::limit((string) $record?->user_agent, 90)) ?: '—'),
                    ]),

                Forms\Components\Section::make(__('message.spam.section'))
                    ->icon('heroicon-o-shield-exclamation')
                    ->visible(fn (?Contact $record) => $record !== null)
                    ->schema([
                        Forms\Components\Placeholder::make('spam_verdict')
                            ->label(__('message.spam.verdict'))
                            ->content(fn (?Contact $record) => new HtmlString(static::verdictHtml($record))),
                    ]),

                Forms\Components\Section::make(__('message.section.status'))
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Forms\Components\ToggleButtons::make('is_read')
                            ->label(__('message.status'))
                            ->inline()
                            ->boolean(__('message.read'), __('message.unread'))
                            ->colors([true => 'success', false => 'warning'])
                            ->icons([true => 'heroicon-m-check-circle', false => 'heroicon-m-envelope'])
                            ->default(false),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses(fn (Contact $r) => $r->is_read ? null : 'font-semibold')
            ->columns([
                Tables\Columns\IconColumn::make('is_read')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-s-envelope')
                    ->trueColor('gray')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('message.name'))
                    ->weight('semibold')
                    ->description(fn (Contact $r) => $r->email)
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label(__('message.subject'))
                    ->description(fn (Contact $r) => Str::limit($r->message ?? '', 60))
                    ->placeholder('—')
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\TextColumn::make('is_read')
                    ->label(__('message.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('message.read') : __('message.unread'))
                    ->color(fn ($state) => $state ? 'gray' : 'warning'),

                Tables\Columns\TextColumn::make('spam_score')
                    ->label(__('message.spam.score'))
                    ->badge()
                    ->alignCenter()
                    ->color(fn ($state) => $state >= SpamGuard::THRESHOLD ? 'danger' : ($state > 0 ? 'warning' : 'success'))
                    ->icon(fn ($state) => $state >= SpamGuard::THRESHOLD ? 'heroicon-m-shield-exclamation' : 'heroicon-m-shield-check')
                    ->tooltip(fn (Contact $r) => collect($r->spam_reasons ?? [])->map(fn ($k) => __('message.spam.reason.'.$k))->implode(' · ') ?: null)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('country')
                    ->label(__('message.spam.country'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('message.received'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_read')
                    ->label(__('message.status'))
                    ->trueLabel(__('message.read'))
                    ->falseLabel(__('message.unread')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('reply')
                        ->label(__('message.action.reply'))
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('primary')
                        ->url(fn (Contact $r) => 'mailto:'.$r->email.'?subject='.rawurlencode('Re: '.($r->subject ?? ''))),

                    Tables\Actions\Action::make('toggle_read')
                        ->label(fn (Contact $r) => $r->is_read ? __('message.action.mark_unread') : __('message.action.mark_read'))
                        ->icon(fn (Contact $r) => $r->is_read ? 'heroicon-m-envelope' : 'heroicon-m-envelope-open')
                        ->color('gray')
                        ->action(function (Contact $r): void {
                            $r->update(['is_read' => ! $r->is_read]);
                            Notification::make()->success()->title(__('message.action.updated'))->send();
                        }),

                    Tables\Actions\EditAction::make()
                        ->label(__('message.action.open'))
                        ->icon('heroicon-m-eye'),

                    Tables\Actions\Action::make('toggle_spam')
                        ->label(fn (Contact $r) => $r->is_spam ? __('message.spam.not_spam') : __('message.spam.mark'))
                        ->icon(fn (Contact $r) => $r->is_spam ? 'heroicon-m-inbox-arrow-down' : 'heroicon-m-shield-exclamation')
                        ->color(fn (Contact $r) => $r->is_spam ? 'success' : 'warning')
                        ->action(function (Contact $r): void {
                            $r->update(['is_spam' => ! $r->is_spam]);
                            Notification::make()->success()->title(__('message.action.updated'))->send();
                        }),

                    static::blockAction(Tables\Actions\Action::make('block')),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('message.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('message.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_read')
                        ->label(__('message.action.mark_read'))
                        ->icon('heroicon-m-envelope-open')->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_read' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('mark_unread')
                        ->label(__('message.action.mark_unread'))
                        ->icon('heroicon-m-envelope')->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_read' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('mark_spam')
                        ->label(__('message.spam.mark'))
                        ->icon('heroicon-m-shield-exclamation')->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_spam' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('not_spam')
                        ->label(__('message.spam.not_spam'))
                        ->icon('heroicon-m-inbox-arrow-down')->color('success')
                        ->action(fn ($records) => $records->each->update(['is_spam' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('block_senders')
                        ->label(__('message.spam.block_bulk'))
                        ->icon('heroicon-m-no-symbol')->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription(__('message.spam.block_bulk_desc'))
                        ->action(function ($records): void {
                            $records->each(fn (Contact $c) => static::block($c, ['email', 'ip']));
                            Notification::make()->success()->title(__('message.spam.blocked_ok'))->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('message.empty'))
            ->emptyStateIcon('heroicon-o-envelope');
    }

    /** Free mailbox providers — blocking their whole domain would block real people. */
    private const SHARED_DOMAINS = [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'live.com',
        'icloud.com', 'me.com', 'aol.com', 'proton.me', 'protonmail.com', 'yandex.com', 'mail.ru', 'gmx.com',
    ];

    /** Row/page action: block this sender by email, domain and/or IP, and move the message to Spam. */
    public static function blockAction($action)
    {
        return $action
            ->label(__('message.spam.block'))
            ->icon('heroicon-m-no-symbol')
            ->color('danger')
            ->modalHeading(__('message.spam.block'))
            ->modalDescription(__('message.spam.block_desc'))
            ->modalIcon('heroicon-o-no-symbol')
            ->form(fn (Contact $record) => [
                Forms\Components\CheckboxList::make('types')
                    ->label(__('message.spam.block_what'))
                    ->options(array_filter([
                        'email' => __('message.spam.type.email').': '.$record->email,
                        'domain' => static::domainOf($record) && ! in_array(static::domainOf($record), self::SHARED_DOMAINS, true)
                            ? __('message.spam.type.domain').': @'.static::domainOf($record) : null,
                        'ip' => $record->ip_address ? __('message.spam.type.ip').': '.$record->ip_address : null,
                    ]))
                    ->default(['email'])
                    ->required(),
            ])
            ->action(function (Contact $record, array $data): void {
                static::block($record, (array) ($data['types'] ?? []));
                Notification::make()->success()->title(__('message.spam.blocked_ok'))->send();
            });
    }

    public static function block(Contact $c, array $types): void
    {
        $values = [
            'email' => strtolower(trim((string) $c->email)),
            'domain' => static::domainOf($c),
            'ip' => (string) $c->ip_address,
        ];

        foreach ($types as $type) {
            $value = $values[$type] ?? '';
            if ($value === '' || ($type === 'domain' && in_array($value, self::SHARED_DOMAINS, true))) {
                continue;
            }
            \App\Models\ContactBlock::firstOrCreate(
                ['type' => $type, 'value' => $value],
                ['reason' => Str::limit((string) ($c->subject ?: $c->message), 200), 'created_by' => auth()->id()],
            );
        }

        $c->update(['is_spam' => true]);
    }

    private static function domainOf(Contact $c): string
    {
        $email = strtolower(trim((string) $c->email));

        return str_contains($email, '@') ? substr(strrchr($email, '@'), 1) : '';
    }

    /** Colour-coded verdict + reasons for the message page. */
    public static function verdictHtml(?Contact $c): string
    {
        if (! $c) {
            return '—';
        }
        $spam = $c->is_spam;
        $hex = $spam ? '#dc2626' : ($c->spam_score > 0 ? '#d97706' : '#059669');
        $label = $spam ? __('message.spam.is_spam') : __('message.spam.clean');
        $html = '<span style="display:inline-flex;align-items:center;gap:.35rem;border-radius:9999px;padding:.2rem .7rem;font-size:.75rem;font-weight:700;background:'.$hex.'1a;color:'.$hex.'">'
            .e($label).' · '.(int) $c->spam_score.'</span>';

        $reasons = collect($c->spam_reasons ?? [])->map(fn ($k) => '<li>'.e(__('message.spam.reason.'.$k)).'</li>')->implode('');

        return $html.($reasons ? '<ul style="margin-top:.5rem;padding-inline-start:1.1rem;list-style:disc;font-size:.8rem;">'.$reasons.'</ul>' : '');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
