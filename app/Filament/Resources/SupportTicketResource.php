<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?int $navigationSort = 90;

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

    public static function getNavigationBadge(): ?string
    {
        $count = SupportTicket::where('status', 'open')->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_reply_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('ticket.number'))
                    ->formatStateUsing(fn ($state) => '#'.(1000 + (int) $state))
                    ->badge()->color('gray')->sortable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label(__('ticket.subject'))
                    ->weight('semibold')->searchable()->limit(45),

                Tables\Columns\TextColumn::make('reporter')
                    ->label(__('ticket.owner'))
                    ->state(fn (SupportTicket $r) => $r->reporterName())
                    ->description(fn (SupportTicket $r) => $r->reporterEmail())
                    ->icon(fn (SupportTicket $r) => $r->user_id ? null : 'heroicon-m-user')
                    ->toggleable(),

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
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('ticket.status'))->options(SupportTicket::options('status')),
                Tables\Filters\SelectFilter::make('priority')
                    ->label(__('ticket.priority'))->options(SupportTicket::options('priority')),
                Tables\Filters\SelectFilter::make('category')
                    ->label(__('ticket.category'))->options(SupportTicket::options('category')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(__('ticket.single'))->icon('heroicon-m-chat-bubble-left-right'),
            ])
            ->emptyStateHeading(__('ticket.empty'))
            ->emptyStateIcon('heroicon-o-lifebuoy');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }
}
