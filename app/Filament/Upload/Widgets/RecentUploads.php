<?php

namespace App\Filament\Upload\Widgets;

use App\Models\Asset;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentUploads extends BaseWidget
{
    protected static ?int $sort = -1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '10s';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('upload.center.recent'))
            ->query(
                Asset::query()
                    ->where('user_id', auth()->id())
                    ->latest()
            )
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\ImageColumn::make('preview')
                    ->label('')
                    ->state(fn (Asset $r) => $r->isImage() ? $r->thumbUrl() : null)
                    ->height(38)->width(38)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->visibility('public'),

                Tables\Columns\TextColumn::make('original_name')
                    ->label(__('upload.table.file'))
                    ->weight('semibold')
                    ->description(fn (Asset $r) => '/d/'.$r->slug)
                    ->limit(36)
                    ->searchable(),

                Tables\Columns\TextColumn::make('kind')
                    ->label(__('asset_admin.kind'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('asset_admin.kind_'.$state))
                    ->color(fn ($state) => match ($state) {
                        'image' => 'info', 'pdf' => 'warning', default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label(__('upload.table.size'))
                    ->formatStateUsing(fn ($state) => $this->humanBytes((int) $state))
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('upload.table.status'))
                    ->badge()
                    ->state(fn (Asset $r) => __('asset_admin.status_'.$r->statusKey()))
                    ->color(fn (Asset $r) => match ($r->statusKey()) {
                        'active' => 'success', 'expired' => 'danger', default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('downloads_count')
                    ->label(__('asset_admin.downloads'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->numeric(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('upload.table.when'))
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('copy_link')
                    ->label(__('asset_admin.action.copy_link'))
                    ->icon('heroicon-m-link')->color('gray')
                    ->action(fn () => \Filament\Notifications\Notification::make()->success()->title(__('asset_admin.action.copied'))->send())
                    ->extraAttributes(fn (Asset $r) => [
                        'x-on:click' => 'setTimeout(() => window.fnoonCopy('.\Illuminate\Support\Js::from($r->downloadUrl()).'), 60)',
                    ]),
                Tables\Actions\Action::make('open')
                    ->label(__('upload.zones.open_page'))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Asset $r) => $r->pageUrl())
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading(__('upload.center.empty'))
            ->emptyStateIcon('heroicon-o-cloud-arrow-up');
    }

    private function humanBytes(int $b): string
    {
        if ($b <= 0) {
            return '0 B';
        }
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($b, 1024));

        return round($b / (1024 ** min($i, 4)), 1).' '.$u[min($i, 4)];
    }
}
