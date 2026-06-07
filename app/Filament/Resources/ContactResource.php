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

    public static function getNavigationBadge(): ?string
    {
        return (string) (Contact::where('is_read', false)->count() ?: '');
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('message.empty'))
            ->emptyStateIcon('heroicon-o-envelope');
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
