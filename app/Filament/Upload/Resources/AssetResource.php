<?php

namespace App\Filament\Upload\Resources;

use App\Filament\Upload\Resources\AssetResource\Pages;
use App\Models\Asset;
use App\Services\Upload\AssetService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-2-stack';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('asset_admin.nav');
    }

    public static function getModelLabel(): string
    {
        return __('asset_admin.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('asset_admin.plural');
    }

    /** Assets created by uploading, never by hand. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** Each uploader sees only their own assets; super admins see everything. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->hasRole('super_admin')) {
            $query->forUser($user->id);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (static::getEloquentQuery()->count() ?: '');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('preview')
                    ->label('')
                    ->state(fn (Asset $r) => $r->isImage() ? $r->thumbUrl() : null)
                    ->defaultImageUrl(fn (Asset $r) => null)
                    ->height(44)->width(44)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->visibility('public'),

                Tables\Columns\TextColumn::make('kind')
                    ->label(__('asset_admin.kind'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('asset_admin.kind_'.$state))
                    ->icon(fn ($state) => match ($state) {
                        'image' => 'heroicon-m-photo',
                        'pdf' => 'heroicon-m-document-text',
                        default => 'heroicon-m-archive-box',
                    })
                    ->color(fn ($state) => match ($state) {
                        'image' => 'info', 'pdf' => 'warning', default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('original_name')
                    ->label(__('asset_admin.name'))
                    ->weight('semibold')
                    ->description(fn (Asset $r) => static::humanSize($r->size_bytes))
                    ->searchable()
                    ->limit(38),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('asset_admin.status'))
                    ->badge()
                    ->state(fn (Asset $r) => $r->statusKey())
                    ->formatStateUsing(fn ($state) => __('asset_admin.status_'.$state))
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success', 'expired' => 'danger', default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'active' => 'heroicon-m-check-circle',
                        'expired' => 'heroicon-m-clock',
                        default => 'heroicon-m-pause-circle',
                    }),

                Tables\Columns\TextColumn::make('uploadSession.scan_result')
                    ->label(__('monitor.scan'))
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => $state ? __('monitor.scan_'.$state) : '—')
                    ->icon(fn ($state) => match ($state) {
                        'clean' => 'heroicon-m-shield-check',
                        'infected' => 'heroicon-m-shield-exclamation',
                        default => null,
                    })
                    ->color(fn ($state) => match ($state) {
                        'clean' => 'success', 'infected' => 'danger', 'error' => 'warning', default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\IconColumn::make('password')
                    ->label(__('asset_admin.protected'))
                    ->state(fn (Asset $r) => $r->hasPassword())
                    ->boolean()
                    ->trueIcon('heroicon-s-lock-closed')->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('downloads_count')
                    ->label(__('asset_admin.downloads'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->numeric()->sortable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label(__('asset_admin.views'))
                    ->icon('heroicon-m-eye')
                    ->numeric()->sortable()->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('asset_admin.created'))
                    ->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kind')
                    ->label(__('asset_admin.kind'))
                    ->options([
                        'image' => __('asset_admin.kind_image'),
                        'pdf' => __('asset_admin.kind_pdf'),
                        'file' => __('asset_admin.kind_file'),
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')->label(__('asset_admin.status')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    static::copyLinkAction(),
                    static::shareAction(),

                    Tables\Actions\Action::make('open')
                        ->label(__('asset_admin.action.open'))
                        ->icon('heroicon-m-arrow-top-right-on-square')->color('gray')
                        ->url(fn (Asset $r) => $r->pageUrl())->openUrlInNewTab(),

                    Tables\Actions\ViewAction::make()->icon('heroicon-m-eye'),

                    static::toggleActiveAction(),
                    static::passwordAction(),
                    static::expiryAction(),
                    static::regenerateAction(),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('asset_admin.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()->outlined(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enable')
                        ->label(__('asset_admin.action.enable'))->icon('heroicon-m-check-circle')->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('disable')
                        ->label(__('asset_admin.action.disable'))->icon('heroicon-m-pause-circle')->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('asset_admin.empty'))
            ->emptyStateIcon('heroicon-o-square-2-stack');
    }

    // --- Actions ---------------------------------------------------------

    /** One-click copy of the public download link (copies client-side, confirms server-side). */
    public static function copyLinkAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('copy_link')
            ->label(__('asset_admin.action.copy_link'))
            ->icon('heroicon-m-link')->color('gray')
            ->action(fn () => Notification::make()->success()->title(__('asset_admin.action.copied'))->send())
            ->extraAttributes(fn (Asset $r) => [
                // Defer so the copy runs AFTER the dropdown closes / Livewire settles —
                // otherwise execCommand loses focus inside the panel and copies nothing.
                'x-on:click' => 'setTimeout(() => window.fnoonCopy('.\Illuminate\Support\Js::from($r->downloadUrl()).'), 60)',
            ]);
    }

    public static function shareAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('share')
            ->label(__('asset_admin.action.share'))
            ->icon('heroicon-m-share')->color('primary')
            ->modalHeading(fn (Asset $r) => $r->original_name)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('asset_admin.close'))
            ->modalWidth('2xl')
            ->modalContent(fn (Asset $r) => view('filament.upload.share-kit', [
                'asset' => $r,
                'kit' => app(AssetService::class)->shareKit($r),
            ]));
    }

    public static function toggleActiveAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('toggle_active')
            ->label(fn (Asset $r) => $r->is_active ? __('asset_admin.action.disable') : __('asset_admin.action.enable'))
            ->icon(fn (Asset $r) => $r->is_active ? 'heroicon-m-pause-circle' : 'heroicon-m-check-circle')
            ->color(fn (Asset $r) => $r->is_active ? 'gray' : 'success')
            ->requiresConfirmation()
            ->action(function (Asset $r) {
                $r->update(['is_active' => ! $r->is_active]);
                Notification::make()->success()->title(__('asset_admin.msg.updated'))->send();
            });
    }

    public static function passwordAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('password')
            ->label(__('asset_admin.action.password'))
            ->icon('heroicon-m-lock-closed')->color('warning')
            ->fillForm(fn (Asset $r) => ['remove' => false])
            ->form([
                Forms\Components\TextInput::make('password')
                    ->label(__('asset_admin.password'))
                    ->password()->revealable()
                    ->helperText(__('asset_admin.password_hint'))
                    ->visible(fn (Forms\Get $get) => ! $get('remove')),
                Forms\Components\Toggle::make('remove')
                    ->label(__('asset_admin.password_remove'))
                    ->live(),
            ])
            ->action(function (Asset $r, array $data) {
                if (! empty($data['remove'])) {
                    $r->update(['password' => null]);
                } elseif (! empty($data['password'])) {
                    $r->update(['password' => Hash::make($data['password'])]);
                }
                Notification::make()->success()->title(__('asset_admin.msg.updated'))->send();
            });
    }

    public static function expiryAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('expiry')
            ->label(__('asset_admin.action.expiry'))
            ->icon('heroicon-m-clock')->color('warning')
            ->fillForm(fn (Asset $r) => ['expires_at' => $r->expires_at])
            ->form([
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label(__('asset_admin.expiry'))
                    ->native(false)->seconds(false)
                    ->minDate(now())
                    ->helperText(__('asset_admin.expiry_hint')),
            ])
            ->action(function (Asset $r, array $data) {
                $r->update(['expires_at' => $data['expires_at'] ?: null]);
                Notification::make()->success()->title(__('asset_admin.msg.updated'))->send();
            });
    }

    public static function regenerateAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('regenerate')
            ->label(__('asset_admin.action.regenerate'))
            ->icon('heroicon-m-arrow-path')->color('gray')
            ->requiresConfirmation()
            ->modalDescription(__('asset_admin.regenerate_warn'))
            ->action(function (Asset $r) {
                $r->update(['slug' => app(AssetService::class)->newSlug()]);
                Notification::make()->success()->title(__('asset_admin.msg.link_changed'))->send();
            });
    }

    public static function humanSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));

        return round($bytes / (1024 ** $i), 1).' '.$u[min($i, 4)];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
            'view' => Pages\ViewAsset::route('/{record}'),
        ];
    }
}
