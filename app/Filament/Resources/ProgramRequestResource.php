<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramRequestResource\Pages;
use App\Models\ProgramRequest;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgramRequestResource extends Resource
{
    protected static ?string $model = ProgramRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?int $navigationSort = 28;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.analytics');
    }

    public static function getNavigationLabel(): string
    {
        return __('prog_requests.nav');
    }

    public static function getModelLabel(): string
    {
        return __('prog_requests.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('prog_requests.plural');
    }

    // Badge = pending (new) requests waiting to be worked on.
    public static function getNavigationBadge(): ?string
    {
        $count = ProgramRequest::where('status', 'new')->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    protected static function statusOptions(): array
    {
        return [
            'new' => __('prog_requests.statuses.new'),
            'sourcing' => __('prog_requests.statuses.sourcing'),
            'available' => __('prog_requests.statuses.available'),
            'rejected' => __('prog_requests.statuses.rejected'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_requested_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('term')
                    ->label(__('prog_requests.term'))
                    ->searchable()->weight('bold')->wrap(),

                Tables\Columns\TextColumn::make('votes')
                    ->label(__('prog_requests.votes'))
                    ->badge()->color('info')->sortable(),

                Tables\Columns\SelectColumn::make('status')
                    ->label(__('prog_requests.status'))
                    ->options(static::statusOptions())
                    ->selectablePlaceholder(false)
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact')
                    ->label(__('prog_requests.contact'))
                    ->copyable()->placeholder('—')->toggleable(),

                Tables\Columns\TextColumn::make('note')
                    ->label(__('prog_requests.note'))
                    ->wrap()->limit(80)->placeholder('—')->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('prog_requests.requested_by'))
                    ->placeholder(__('prog_requests.guest'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_requested_at')
                    ->label(__('prog_requests.last_requested'))
                    ->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('prog_requests.filter_status'))
                    ->options(static::statusOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('try')
                    ->label(__('prog_requests.open_search'))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (ProgramRequest $r) => route('search', ['q' => $r->term]), shouldOpenInNewTab: true),

                Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash')->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('prog_requests.empty'))
            ->emptyStateDescription(__('prog_requests.empty_sub'))
            ->emptyStateIcon('heroicon-o-inbox');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProgramRequests::route('/'),
        ];
    }
}
