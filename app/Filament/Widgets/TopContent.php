<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SoftwareResource;
use App\Models\Software;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * "Top content" — the most-downloaded published items at a glance, with a jump
 * straight into the editor. A compact, professional leaderboard for the home.
 */
class TopContent extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '360px';

    public function getTableHeading(): string
    {
        return __('dashboard.top.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Software::query()
                    ->where('status', \App\Enums\ContentStatus::Published->value)
                    ->orderByDesc('downloads_count')
                    ->limit(8)
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label('')
                    ->disk('public')
                    ->square()->size(40)
                    ->defaultImageUrl(asset('favicon.ico')),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('dashboard.top.name'))
                    ->weight('bold')->limit(46)->wrap()
                    ->description(fn (Software $r) => $r->category?->name),

                Tables\Columns\TextColumn::make('content_type')
                    ->label(__('dashboard.top.type'))
                    ->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\ContentType ? $state->label() : $state)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('downloads_count')
                    ->label(__('dashboard.top.downloads'))
                    ->badge()->color('success')
                    ->formatStateUsing(fn ($state) => number_format((int) $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating_avg')
                    ->label(__('dashboard.top.rating'))
                    ->formatStateUsing(fn ($state) => $state > 0 ? '★ '.number_format((float) $state, 1) : '—')
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label(__('dashboard.top.views'))
                    ->formatStateUsing(fn ($state) => number_format((int) $state))
                    ->color('gray')->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label(__('dashboard.top.open'))
                    ->icon('heroicon-m-arrow-up-right')
                    ->url(fn (Software $record) => SoftwareResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn (Software $record) => SoftwareResource::getUrl('edit', ['record' => $record]));
    }
}
