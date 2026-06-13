<?php

namespace App\Filament\Resources;

use App\Enums\UploadStatus;
use App\Filament\Resources\UploadSessionResource\Pages;
use App\Jobs\ProcessUploadedFile;
use App\Models\UploadSession;
use App\Services\Upload\AssetService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UploadSessionResource extends Resource
{
    protected static ?string $model = UploadSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.uploads');
    }

    public static function getModelLabel(): string
    {
        return __('nav.upload_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.uploads');
    }

    /** Uploads are created by the upload engine, never by hand. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** Failed uploads need attention → red badge. */
    public static function getNavigationBadge(): ?string
    {
        return (string) (UploadSession::where('status', UploadStatus::Failed->value)->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function statuses(): array
    {
        return collect(UploadStatus::cases())
            ->mapWithKeys(fn (UploadStatus $s) => [$s->value => $s->label()])
            ->all();
    }

    public static function humanBytes(?int $b): string
    {
        $b = (int) $b;
        if ($b <= 0) {
            return '0 B';
        }
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($b, 1024));

        return round($b / (1024 ** min($i, 4)), 1).' '.$u[min($i, 4)];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('monitor.section.file'))
                    ->icon('heroicon-o-document')
                    ->schema([
                        Forms\Components\TextInput::make('original_name')->label(__('monitor.file'))->disabled(),
                        Forms\Components\TextInput::make('mime_type')->label(__('monitor.mime'))->disabled(),
                        Forms\Components\Placeholder::make('size_human')
                            ->label(__('monitor.size'))
                            ->content(fn (?UploadSession $record) => self::humanBytes($record?->size_bytes)),
                        Forms\Components\TextInput::make('checksum_sha256')->label('SHA-256')->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make(__('monitor.section.storage'))
                    ->icon('heroicon-o-circle-stack')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('r2_key')->label(__('monitor.r2_key'))->disabled()->columnSpanFull(),
                        Forms\Components\TextInput::make('parts_completed')->label(__('monitor.parts_done'))->disabled(),
                        Forms\Components\TextInput::make('parts_total')->label(__('monitor.parts_total'))->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make(__('monitor.section.scan'))
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\TextInput::make('scan_result')->label(__('monitor.scan'))->disabled(),
                        Forms\Components\Textarea::make('scan_report')->label(__('monitor.scan_report'))->disabled()->rows(2)->columnSpanFull(),
                        Forms\Components\Textarea::make('error_message')->label(__('monitor.error'))->disabled()->rows(2)->columnSpanFull()
                            ->visible(fn (?UploadSession $record) => filled($record?->error_message)),
                    ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('monitor.section.status'))
                    ->icon('heroicon-o-signal')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('monitor.status'))
                            ->options(self::statuses())
                            ->helperText(__('monitor.status_hint')),

                        Forms\Components\Placeholder::make('progress')
                            ->label(__('monitor.progress'))
                            ->content(fn (?UploadSession $record) => $record ? $record->progressPercent().'%' : '—'),

                        Forms\Components\Placeholder::make('uploader')
                            ->label(__('monitor.uploader'))
                            ->content(fn (?UploadSession $record) => $record?->user?->name ?? '—'),

                        Forms\Components\Placeholder::make('created')
                            ->label(__('monitor.received'))
                            ->content(fn (?UploadSession $record) => $record?->created_at?->diffForHumans() ?? '—'),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([
                Tables\Columns\TextColumn::make('original_name')
                    ->label(__('monitor.file'))
                    ->weight('semibold')
                    ->icon('heroicon-m-document')
                    ->description(fn (UploadSession $r) => $r->checksum_sha256 ? substr($r->checksum_sha256, 0, 20).'…' : null)
                    ->limit(34)
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('monitor.uploader'))
                    ->icon('heroicon-m-user')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label(__('monitor.size'))
                    ->formatStateUsing(fn ($state) => self::humanBytes((int) $state))
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('monitor.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof UploadStatus ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof UploadStatus ? $state->color() : 'gray')
                    ->icon(fn ($state) => match ($state) {
                        UploadStatus::Failed => 'heroicon-m-x-circle',
                        UploadStatus::Published => 'heroicon-m-check-circle',
                        default => null,
                    })
                    ->tooltip(fn (UploadSession $r) => $r->status === UploadStatus::Failed ? $r->error_message : null),

                Tables\Columns\TextColumn::make('parts_completed')
                    ->label(__('monitor.progress'))
                    ->formatStateUsing(fn ($state, UploadSession $r) => $r->progressPercent().'%')
                    ->badge()
                    ->color(fn (UploadSession $r) => $r->status === UploadStatus::Failed
                        ? 'danger'
                        : ($r->progressPercent() >= 100 ? 'success' : 'gray')),

                Tables\Columns\TextColumn::make('scan_result')
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
                        'clean' => 'success',
                        'infected' => 'danger',
                        'error' => 'warning',
                        default => 'gray',
                    })
                    ->tooltip(fn (UploadSession $r) => $r->scan_report)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('monitor.received'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('monitor.status'))->options(self::statuses()),
                Tables\Filters\SelectFilter::make('scan_result')
                    ->label(__('monitor.scan'))
                    ->options(['clean' => 'clean', 'infected' => 'infected', 'skipped' => 'skipped', 'error' => 'error']),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label(__('monitor.uploader'))
                    ->relationship('user', 'name')->searchable()->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('reprocess')
                        ->label(__('monitor.action.reprocess'))
                        ->icon('heroicon-m-arrow-path')->color('primary')
                        ->requiresConfirmation()
                        ->visible(fn (UploadSession $r) => in_array($r->status->value, ['uploaded', 'scanning', 'failed']))
                        ->action(function (UploadSession $r): void {
                            ProcessUploadedFile::dispatch($r->id);
                            Notification::make()->success()->title(__('monitor.action.reprocessing'))->send();
                        }),

                    Tables\Actions\Action::make('download')
                        ->label(__('monitor.action.download'))
                        ->icon('heroicon-m-arrow-down-tray')->color('gray')
                        ->visible(fn (UploadSession $r) => filled($r->r2_key) && filled(config('filesystems.disks.r2.key')))
                        ->url(fn (UploadSession $r) => app(\App\Services\Upload\R2UploadService::class)
                            ->temporaryDownloadUrl($r->r2_key, $r->original_name))
                        ->openUrlInNewTab(),

                    // ---- Shareable-asset actions (mirror the uploader's "My uploads") ----
                    Tables\Actions\Action::make('share')
                        ->label(__('asset_admin.action.share'))
                        ->icon('heroicon-m-share')->color('primary')
                        ->visible(fn (UploadSession $r) => $r->asset !== null)
                        ->modalHeading(fn (UploadSession $r) => $r->original_name)
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel(__('asset_admin.close'))
                        ->modalWidth('2xl')
                        ->modalContent(fn (UploadSession $r) => view('filament.upload.share-kit', [
                            'asset' => $r->asset,
                            'kit' => app(AssetService::class)->shareKit($r->asset),
                        ])),

                    Tables\Actions\Action::make('copy_link')
                        ->label(__('asset_admin.action.copy_link'))
                        ->icon('heroicon-m-link')->color('gray')
                        ->visible(fn (UploadSession $r) => $r->asset !== null)
                        ->action(fn () => Notification::make()->success()->title(__('asset_admin.action.copied'))->send())
                        ->extraAttributes(fn (UploadSession $r) => [
                            'x-on:click' => 'setTimeout(() => window.fnoonCopy('.\Illuminate\Support\Js::from($r->asset?->downloadUrl() ?? '').'), 60)',
                        ]),

                    Tables\Actions\Action::make('open_page')
                        ->label(__('asset_admin.action.open'))
                        ->icon('heroicon-m-arrow-top-right-on-square')->color('gray')
                        ->visible(fn (UploadSession $r) => $r->asset !== null)
                        ->url(fn (UploadSession $r) => $r->asset?->pageUrl())
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (UploadSession $r) => $r->asset?->is_active ? __('asset_admin.action.disable') : __('asset_admin.action.enable'))
                        ->icon(fn (UploadSession $r) => $r->asset?->is_active ? 'heroicon-m-pause-circle' : 'heroicon-m-check-circle')
                        ->color(fn (UploadSession $r) => $r->asset?->is_active ? 'gray' : 'success')
                        ->visible(fn (UploadSession $r) => $r->asset !== null)
                        ->requiresConfirmation()
                        ->action(function (UploadSession $r): void {
                            $r->asset?->update(['is_active' => ! $r->asset->is_active]);
                            Notification::make()->success()->title(__('asset_admin.msg.updated'))->send();
                        }),

                    Tables\Actions\Action::make('password')
                        ->label(__('asset_admin.action.password'))
                        ->icon('heroicon-m-lock-closed')->color('warning')
                        ->visible(fn (UploadSession $r) => $r->asset !== null)
                        ->form([
                            Forms\Components\TextInput::make('password')
                                ->label(__('asset_admin.password'))->password()->revealable()
                                ->helperText(__('asset_admin.password_hint'))
                                ->visible(fn (Forms\Get $get) => ! $get('remove')),
                            Forms\Components\Toggle::make('remove')->label(__('asset_admin.password_remove'))->live(),
                        ])
                        ->action(function (UploadSession $r, array $data): void {
                            if (! empty($data['remove'])) {
                                $r->asset?->update(['password' => null]);
                            } elseif (! empty($data['password'])) {
                                $r->asset?->update(['password' => Hash::make($data['password'])]);
                            }
                            Notification::make()->success()->title(__('asset_admin.msg.updated'))->send();
                        }),

                    Tables\Actions\Action::make('expiry')
                        ->label(__('asset_admin.action.expiry'))
                        ->icon('heroicon-m-clock')->color('warning')
                        ->visible(fn (UploadSession $r) => $r->asset !== null)
                        ->fillForm(fn (UploadSession $r) => ['expires_at' => $r->asset?->expires_at])
                        ->form([
                            Forms\Components\DateTimePicker::make('expires_at')
                                ->label(__('asset_admin.expiry'))->native(false)->seconds(false)->minDate(now())
                                ->helperText(__('asset_admin.expiry_hint')),
                        ])
                        ->action(function (UploadSession $r, array $data): void {
                            $r->asset?->update(['expires_at' => $data['expires_at'] ?: null]);
                            Notification::make()->success()->title(__('asset_admin.msg.updated'))->send();
                        }),

                    Tables\Actions\Action::make('regenerate')
                        ->label(__('asset_admin.action.regenerate'))
                        ->icon('heroicon-m-arrow-path')->color('gray')
                        ->visible(fn (UploadSession $r) => $r->asset !== null)
                        ->requiresConfirmation()->modalDescription(__('asset_admin.regenerate_warn'))
                        ->action(function (UploadSession $r): void {
                            $r->asset?->update(['slug' => app(AssetService::class)->newSlug()]);
                            Notification::make()->success()->title(__('asset_admin.msg.link_changed'))->send();
                        }),

                    Tables\Actions\EditAction::make()
                        ->label(__('monitor.action.open'))
                        ->icon('heroicon-m-eye'),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('monitor.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('monitor.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('monitor.empty'))
            ->emptyStateIcon('heroicon-o-cloud-arrow-up');
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
            'index' => Pages\ListUploadSessions::route('/'),
            'edit' => Pages\EditUploadSession::route('/{record}/edit'),
        ];
    }
}
