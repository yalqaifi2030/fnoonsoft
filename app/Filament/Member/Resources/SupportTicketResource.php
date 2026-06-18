<?php

namespace App\Filament\Member\Resources;

use App\Filament\Member\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('ticket.nav');
    }

    public static function getModelLabel(): string
    {
        return __('ticket.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ticket.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\TextInput::make('subject')
                        ->label(__('ticket.subject'))
                        ->required()->maxLength(120)
                        ->placeholder(__('ticket.subject_ph'))
                        ->columnSpanFull(),

                    Forms\Components\Select::make('category')
                        ->label(__('ticket.category'))
                        ->options(SupportTicket::options('category'))
                        ->default('technical')->required()->native(false),

                    Forms\Components\Select::make('priority')
                        ->label(__('ticket.priority'))
                        ->options(SupportTicket::options('priority'))
                        ->default('normal')->required()->native(false),

                    Forms\Components\Textarea::make('body')
                        ->label(__('ticket.first_message'))
                        ->required()->rows(5)->columnSpanFull(),

                    Forms\Components\FileUpload::make('attachment')
                        ->label(__('ticket.attachment'))
                        ->disk('public')->directory('ticket-attachments')
                        // Restrict to safe types — no HTML/SVG (same-origin stored XSS).
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'])
                        ->maxSize(5120)->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_reply_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('ticket.number'))
                    ->formatStateUsing(fn ($state) => '#'.(1000 + (int) $state))
                    ->badge()->color('gray'),

                Tables\Columns\TextColumn::make('subject')
                    ->label(__('ticket.subject'))
                    ->weight('semibold')->searchable()->limit(45),

                Tables\Columns\TextColumn::make('category')
                    ->label(__('ticket.category'))
                    ->formatStateUsing(fn ($state) => SupportTicket::label('category', $state))
                    ->badge()->color('gray')->toggleable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label(__('ticket.priority'))
                    ->formatStateUsing(fn ($state) => SupportTicket::label('priority', $state))
                    ->badge()->color(fn ($state) => SupportTicket::priorityColor($state)),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('ticket.status'))
                    ->formatStateUsing(fn ($state) => SupportTicket::label('status', $state))
                    ->badge()
                    ->color(fn ($state) => SupportTicket::statusColor($state))
                    ->icon(fn ($state) => SupportTicket::statusIcon($state)),

                Tables\Columns\TextColumn::make('last_reply_at')
                    ->label(__('ticket.last_reply'))
                    ->since()->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(__('ticket.single'))->icon('heroicon-m-chat-bubble-left-right'),
            ])
            ->emptyStateHeading(__('ticket.empty'))
            ->emptyStateDescription(__('ticket.empty_hint'))
            ->emptyStateIcon('heroicon-o-lifebuoy');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }
}
