<?php

namespace App\Filament\Upload\Resources\AssetResource\Pages;

use App\Filament\Upload\Resources\AssetResource;
use App\Models\Asset;
use App\Services\Upload\AssetService;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Hash;

class ViewAsset extends ViewRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('share')
                ->label(__('asset_admin.action.share'))
                ->icon('heroicon-m-share')->color('primary')
                ->modalHeading(fn (Asset $record) => $record->original_name)
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('asset_admin.close'))
                ->modalWidth('2xl')
                ->modalContent(fn (Asset $record) => view('filament.upload.share-kit', [
                    'asset' => $record,
                    'kit' => app(AssetService::class)->shareKit($record),
                ])),

            Actions\Action::make('open')
                ->label(__('asset_admin.action.open'))
                ->icon('heroicon-m-arrow-top-right-on-square')->color('gray')
                ->url(fn (Asset $record) => $record->pageUrl())->openUrlInNewTab(),

            Actions\Action::make('toggle_active')
                ->label(fn (Asset $record) => $record->is_active ? __('asset_admin.action.disable') : __('asset_admin.action.enable'))
                ->icon(fn (Asset $record) => $record->is_active ? 'heroicon-m-pause-circle' : 'heroicon-m-check-circle')
                ->color(fn (Asset $record) => $record->is_active ? 'gray' : 'success')
                ->requiresConfirmation()
                ->action(function (Asset $record) {
                    $record->update(['is_active' => ! $record->is_active]);
                    Notification::make()->success()->title(__('asset_admin.msg.updated'))->send();
                }),

            Actions\ActionGroup::make([
                Actions\Action::make('password')
                    ->label(__('asset_admin.action.password'))
                    ->icon('heroicon-m-lock-closed')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label(__('asset_admin.password'))->password()->revealable()
                            ->helperText(__('asset_admin.password_hint'))
                            ->visible(fn (Forms\Get $get) => ! $get('remove')),
                        Forms\Components\Toggle::make('remove')->label(__('asset_admin.password_remove'))->live(),
                    ])
                    ->action(function (Asset $record, array $data) {
                        if (! empty($data['remove'])) {
                            $record->update(['password' => null]);
                        } elseif (! empty($data['password'])) {
                            $record->update(['password' => Hash::make($data['password'])]);
                        }
                        Notification::make()->success()->title(__('asset_admin.msg.updated'))->send();
                    }),

                Actions\Action::make('expiry')
                    ->label(__('asset_admin.action.expiry'))
                    ->icon('heroicon-m-clock')
                    ->fillForm(fn (Asset $record) => ['expires_at' => $record->expires_at])
                    ->form([
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label(__('asset_admin.expiry'))->native(false)->seconds(false)->minDate(now())
                            ->helperText(__('asset_admin.expiry_hint')),
                    ])
                    ->action(function (Asset $record, array $data) {
                        $record->update(['expires_at' => $data['expires_at'] ?: null]);
                        Notification::make()->success()->title(__('asset_admin.msg.updated'))->send();
                    }),

                Actions\Action::make('regenerate')
                    ->label(__('asset_admin.action.regenerate'))
                    ->icon('heroicon-m-arrow-path')
                    ->requiresConfirmation()->modalDescription(__('asset_admin.regenerate_warn'))
                    ->action(function (Asset $record) {
                        $record->update(['slug' => app(AssetService::class)->newSlug()]);
                        Notification::make()->success()->title(__('asset_admin.msg.link_changed'))->send();
                    }),

                Actions\DeleteAction::make(),
            ])
                ->label(__('asset_admin.action.more'))
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')->button(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make()
                ->schema([
                    ImageEntry::make('preview')
                        ->label('')
                        ->state(fn (Asset $record) => $record->isImage() ? $record->variantUrl('medium') : null)
                        ->visible(fn (Asset $record) => $record->isImage())
                        ->visibility('public')
                        ->extraImgAttributes(['class' => 'rounded-xl max-h-80 object-contain'])
                        ->columnSpanFull(),

                    Grid::make(3)->schema([
                        TextEntry::make('original_name')->label(__('asset_admin.name'))->weight('bold')->columnSpan(2),
                        TextEntry::make('kind')->label(__('asset_admin.kind'))->badge()
                            ->formatStateUsing(fn ($state) => __('asset_admin.kind_'.$state)),
                        TextEntry::make('status')->label(__('asset_admin.status'))->badge()
                            ->state(fn (Asset $record) => __('asset_admin.status_'.$record->statusKey()))
                            ->color(fn (Asset $record) => match ($record->statusKey()) {
                                'active' => 'success', 'expired' => 'danger', default => 'gray',
                            }),
                        TextEntry::make('size_bytes')->label(__('asset_admin.size'))
                            ->formatStateUsing(fn ($state) => AssetResource::humanSize((int) $state)),
                        TextEntry::make('downloads_count')->label(__('asset_admin.downloads'))->icon('heroicon-m-arrow-down-tray'),
                        TextEntry::make('views_count')->label(__('asset_admin.views'))->icon('heroicon-m-eye'),
                        TextEntry::make('expires_at')->label(__('asset_admin.expiry'))->dateTime()->placeholder('—'),
                        TextEntry::make('created_at')->label(__('asset_admin.created'))->dateTime(),
                    ]),

                    TextEntry::make('checksum_sha256')->label(__('asset_admin.checksum'))
                        ->copyable()->placeholder('—')->columnSpanFull()
                        ->extraAttributes(['class' => 'font-mono text-xs']),

                    TextEntry::make('page')->label(__('asset_admin.share_link'))
                        ->state(fn (Asset $record) => $record->pageUrl())
                        ->copyable()->columnSpanFull(),
                ]),
        ]);
    }
}
