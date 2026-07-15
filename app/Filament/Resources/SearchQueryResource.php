<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SearchQueryResource\Pages;
use App\Models\SearchQuery;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SearchQueryResource extends Resource
{
    protected static ?string $model = SearchQuery::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.analytics');
    }

    public static function getNavigationLabel(): string
    {
        return __('search_admin.nav');
    }

    public static function getModelLabel(): string
    {
        return __('search_admin.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('search_admin.plural');
    }

    // Badge = number of unfulfilled searches (zero-result demand) — the thing to act on.
    public static function getNavigationBadge(): ?string
    {
        $count = SearchQuery::where('results_count', 0)->count();

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

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('term')
                    ->label(__('search_admin.term'))
                    ->searchable()->weight('bold')->wrap()
                    ->description(fn (SearchQuery $r) => $r->results_count === 0 ? __('search_admin.unfulfilled') : null)
                    ->color(fn (SearchQuery $r) => $r->results_count === 0 ? 'warning' : null),

                Tables\Columns\TextColumn::make('hits')
                    ->label(__('search_admin.hits'))
                    ->badge()->color('info')->sortable(),

                Tables\Columns\TextColumn::make('results_count')
                    ->label(__('search_admin.results'))
                    ->badge()
                    ->color(fn (int $state) => $state === 0 ? 'warning' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('request_count')
                    ->label(__('search_admin.requests'))
                    ->badge()->color(fn (int $state) => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_searched_at')
                    ->label(__('search_admin.last_searched'))
                    ->since()->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('results_count')
                    ->label(__('search_admin.status'))
                    ->placeholder(__('search_admin.all'))
                    ->trueLabel(__('search_admin.fulfilled'))
                    ->falseLabel(__('search_admin.unfulfilled'))
                    ->queries(
                        true: fn (Builder $q) => $q->where('results_count', '>', 0),
                        false: fn (Builder $q) => $q->where('results_count', 0),
                        blank: fn (Builder $q) => $q,
                    ),

                Tables\Filters\Filter::make('requested')
                    ->label(__('search_admin.filter_requested'))
                    ->query(fn (Builder $q) => $q->where('request_count', '>', 0))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('try')
                    ->label(__('search_admin.open_search'))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (SearchQuery $r) => route('search', ['q' => $r->term]), shouldOpenInNewTab: true),

                Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash')->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('search_admin.empty'))
            ->emptyStateIcon('heroicon-o-magnifying-glass');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSearchQueries::route('/'),
        ];
    }
}
