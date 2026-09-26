<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FileModerationResource\Pages;
use App\Filament\Upload\Resources\AssetResource as StaffAssets;
use App\Models\Asset;
use App\Models\User;
use App\Policies\StaffPermissionPolicy;
use App\Support\FileModeration;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * File review — every file a MEMBER uploads (plus anything visitors reported)
 * lands here for a legality check: preview/download it, then approve or
 * reject with a reason. Post-moderation: files are live while queued.
 */
class FileModerationResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $slug = 'file-moderation';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'original_name';

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.engagement');
    }

    public static function getNavigationLabel(): string
    {
        return __('moderation.nav');
    }

    public static function getModelLabel(): string
    {
        return __('moderation.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('moderation.plural');
    }

    // --- Access: the dedicated "moderate files" permission -----------------

    public static function canViewAny(): bool
    {
        return StaffPermissionPolicy::allows(auth()->user(), 'moderate files');
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    /** Members' files (uploader has no staff role), plus anything with open reports. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user.roles', 'uploadSession', 'reviewer'])
            ->where(function (Builder $q) {
                $q->whereHas('user', fn (Builder $u) => $u->doesntHave('roles'))
                    ->orWhere('open_reports', '>', 0);
            });
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canViewAny()) {
            return null;
        }

        $n = static::getEloquentQuery()
            ->where(fn (Builder $q) => $q->where('moderation_status', FileModeration::PENDING)->orWhere('open_reports', '>', 0))
            ->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getEloquentQuery()->where('open_reports', '>', 0)->exists() ? 'danger' : 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('moderation.badge_tooltip');
    }

    // --- Table ------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Asset $r) => static::getUrl('view', ['record' => $r]))
            ->columns([
                Tables\Columns\ViewColumn::make('preview')
                    ->label('')
                    ->view('filament.moderation.cells.preview'),

                Tables\Columns\TextColumn::make('original_name')
                    ->label(__('moderation.col.file'))
                    ->weight('semibold')
                    ->limit(42)
                    ->tooltip(fn (Asset $r) => $r->original_name)
                    ->description(fn (Asset $r) => StaffAssets::humanSize((int) $r->size_bytes)
                        .' · '.strtoupper(pathinfo((string) $r->original_name, PATHINFO_EXTENSION) ?: $r->kind))
                    ->searchable(),

                Tables\Columns\ViewColumn::make('owner')
                    ->label(__('moderation.col.owner'))
                    ->view('filament.moderation.cells.owner'),

                Tables\Columns\TextColumn::make('moderation_status')
                    ->label(__('moderation.col.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __('moderation.status.'.$state))
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success', 'rejected' => 'danger', default => 'warning',
                    })
                    ->icon(fn (string $state) => match ($state) {
                        'approved' => 'heroicon-m-check-badge',
                        'rejected' => 'heroicon-m-no-symbol',
                        default => 'heroicon-m-clock',
                    })
                    ->description(fn (Asset $r) => $r->isRejected() && $r->rejection_reason
                        ? __('moderation.reason.'.$r->rejection_reason) : null),

                Tables\Columns\TextColumn::make('uploadSession.scan_result')
                    ->label(__('moderation.col.scan'))
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => $state ? __('monitor.scan_'.$state) : '—')
                    ->icon(fn ($state) => match ($state) {
                        'clean' => 'heroicon-m-shield-check',
                        'infected' => 'heroicon-m-shield-exclamation',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->color(fn ($state) => match ($state) {
                        'clean' => 'success', 'infected' => 'danger', 'error' => 'warning', default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('open_reports')
                    ->label(__('moderation.col.reports'))
                    ->badge()
                    ->alignCenter()
                    ->icon(fn ($state) => $state > 0 ? 'heroicon-m-flag' : null)
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->formatStateUsing(fn ($state, Asset $r) => $state > 0 ? $state : ($r->reports_count ?: '—'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('downloads_count')
                    ->label(__('moderation.col.downloads'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->numeric()->sortable()->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('moderation.col.uploaded'))
                    ->since()
                    ->dateTimeTooltip('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kind')
                    ->label(__('asset_admin.kind'))
                    ->options([
                        'file' => __('asset_admin.kind_file'),
                        'image' => __('asset_admin.kind_image'),
                        'pdf' => __('asset_admin.kind_pdf'),
                    ]),
                Tables\Filters\SelectFilter::make('scan')
                    ->label(__('moderation.col.scan'))
                    ->options([
                        'clean' => __('monitor.scan_clean'),
                        'infected' => __('monitor.scan_infected'),
                        'skipped' => __('monitor.scan_skipped'),
                        'error' => __('monitor.scan_error'),
                    ])
                    // Filament injects closure args BY NAME — it must be $query / $data.
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->whereHas('uploadSession', fn (Builder $s) => $s->where('scan_result', $data['value']))
                        : $query),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label(__('moderation.col.owner'))
                    ->relationship('user', 'name', fn (Builder $query) => $query->doesntHave('roles'))
                    ->searchable()->preload(),
            ])
            ->actions([
                static::approveAction(Tables\Actions\Action::make('approve'))
                    ->iconButton()->tooltip(__('moderation.action.approve')),
                static::rejectAction(Tables\Actions\Action::make('reject'))
                    ->iconButton()->tooltip(__('moderation.action.reject')),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label(__('moderation.action.review'))
                        ->icon('heroicon-m-magnifying-glass')
                        ->url(fn (Asset $r) => static::getUrl('view', ['record' => $r])),
                    static::downloadAction(Tables\Actions\Action::make('download')),
                    static::openPageAction(Tables\Actions\Action::make('open_page')),
                    static::restoreAction(Tables\Actions\Action::make('restore')),
                    static::trustAction(Tables\Actions\Action::make('trust')),
                    static::banAction(Tables\Actions\Action::make('ban')),
                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('moderation.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('moderation.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label(__('moderation.action.bulk_approve'))
                        ->icon('heroicon-m-check-badge')->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn (Asset $a) => FileModeration::approve($a, auth()->user()));
                            Notification::make()->success()->title(__('moderation.msg.bulk_done', ['count' => $records->count()]))->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('bulk_reject')
                        ->label(__('moderation.action.bulk_reject'))
                        ->icon('heroicon-m-no-symbol')->color('danger')
                        ->modalHeading(__('moderation.form.reject_heading'))
                        ->modalDescription(__('moderation.form.reject_desc', ['days' => FileModeration::RETENTION_DAYS]))
                        ->modalIcon('heroicon-o-no-symbol')
                        ->form(static::rejectForm())
                        ->action(function (Collection $records, array $data) {
                            $records->each(fn (Asset $a) => FileModeration::reject(
                                $a, auth()->user(), $data['reason'], $data['note'] ?? null, (bool) ($data['notify'] ?? true)
                            ));
                            Notification::make()->success()->title(__('moderation.msg.bulk_done', ['count' => $records->count()]))->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('moderation.empty.default'))
            ->emptyStateIcon('heroicon-o-shield-check')
            ->poll('60s');
    }

    // --- Shared actions (work for table rows AND the review page header) ---

    /** @template T of \Filament\Actions\Action|\Filament\Tables\Actions\Action  @param T $action  @return T */
    public static function approveAction($action)
    {
        return $action
            ->label(__('moderation.action.approve'))
            ->icon('heroicon-m-check-badge')
            ->color('success')
            ->visible(fn (Asset $record) => $record->moderation_status !== FileModeration::APPROVED)
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-check-badge')
            ->modalHeading(__('moderation.confirm.approve'))
            ->modalDescription(fn (Asset $record) => $record->isRejected()
                ? __('moderation.confirm.restore_desc') : __('moderation.confirm.approve_desc'))
            ->action(function (Asset $record) {
                $wasRejected = $record->isRejected();
                FileModeration::approve($record, auth()->user());
                Notification::make()->success()
                    ->title(__($wasRejected ? 'moderation.msg.restored' : 'moderation.msg.approved'))->send();
            });
    }

    public static function rejectAction($action)
    {
        return $action
            ->label(__('moderation.action.reject'))
            ->icon('heroicon-m-no-symbol')
            ->color('danger')
            ->visible(fn (Asset $record) => ! $record->isRejected())
            ->modalIcon('heroicon-o-no-symbol')
            ->modalHeading(__('moderation.form.reject_heading'))
            ->modalDescription(__('moderation.form.reject_desc', ['days' => FileModeration::RETENTION_DAYS]))
            ->modalSubmitActionLabel(__('moderation.action.reject'))
            ->form(static::rejectForm())
            ->action(function (Asset $record, array $data) {
                FileModeration::reject($record, auth()->user(), $data['reason'], $data['note'] ?? null, (bool) ($data['notify'] ?? true));
                Notification::make()->success()->title(__('moderation.msg.rejected'))->send();
            });
    }

    public static function restoreAction($action)
    {
        return static::approveAction($action)
            ->label(__('moderation.action.restore'))
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('warning')
            ->visible(fn (Asset $record) => $record->isRejected());
    }

    public static function downloadAction($action)
    {
        return $action
            ->label(__('moderation.action.download'))
            ->icon('heroicon-m-arrow-down-tray')
            ->color('gray')
            ->url(fn (Asset $record) => route('moderation.download', $record))
            ->openUrlInNewTab();
    }

    public static function openPageAction($action)
    {
        return $action
            ->label(__('moderation.action.open_page'))
            ->icon('heroicon-m-arrow-top-right-on-square')
            ->color('gray')
            ->url(fn (Asset $record) => $record->pageUrl())
            ->openUrlInNewTab();
    }

    public static function trustAction($action)
    {
        return $action
            ->label(fn (Asset $record) => $record->user?->uploads_trusted ? __('moderation.action.untrust') : __('moderation.action.trust'))
            ->icon(fn (Asset $record) => $record->user?->uploads_trusted ? 'heroicon-m-user-minus' : 'heroicon-m-check-badge')
            ->color(fn (Asset $record) => $record->user?->uploads_trusted ? 'gray' : 'info')
            ->visible(fn (Asset $record) => $record->user && ! $record->user->isStaff() && ! $record->user->isUploadBanned())
            ->requiresConfirmation()
            ->modalHeading(__('moderation.confirm.trust'))
            ->modalDescription(fn (Asset $record) => $record->user?->uploads_trusted
                ? __('moderation.confirm.untrust_desc') : __('moderation.confirm.trust_desc'))
            ->action(function (Asset $record) {
                $trusted = ! $record->user->uploads_trusted;
                FileModeration::setTrusted($record->user, $trusted, auth()->user());
                Notification::make()->success()->title(__($trusted ? 'moderation.msg.trusted' : 'moderation.msg.untrusted'))->send();
            });
    }

    public static function banAction($action)
    {
        return $action
            ->label(fn (Asset $record) => $record->user?->isUploadBanned() ? __('moderation.action.unban') : __('moderation.action.ban'))
            ->icon(fn (Asset $record) => $record->user?->isUploadBanned() ? 'heroicon-m-lock-open' : 'heroicon-m-user-minus')
            ->color(fn (Asset $record) => $record->user?->isUploadBanned() ? 'gray' : 'danger')
            ->visible(fn (Asset $record) => $record->user && ! $record->user->isStaff())
            ->modalIcon('heroicon-o-no-symbol')
            ->modalHeading(fn (Asset $record) => $record->user?->isUploadBanned() ? __('moderation.confirm.unban') : __('moderation.form.ban_heading'))
            ->modalDescription(fn (Asset $record) => $record->user?->isUploadBanned() ? __('moderation.confirm.unban_desc') : __('moderation.form.ban_desc'))
            ->form(fn (Asset $record) => $record->user?->isUploadBanned() ? [] : [
                Forms\Components\Textarea::make('reason')
                    ->label(__('moderation.form.ban_reason'))
                    ->helperText(__('moderation.form.ban_reason_hint'))
                    ->rows(3)->maxLength(500),
            ])
            ->action(function (Asset $record, array $data) {
                if ($record->user->isUploadBanned()) {
                    FileModeration::unban($record->user, auth()->user());
                    Notification::make()->success()->title(__('moderation.msg.unbanned'))->send();

                    return;
                }
                FileModeration::ban($record->user, auth()->user(), $data['reason'] ?? null);
                Notification::make()->success()->title(__('moderation.msg.banned'))->send();
            });
    }

    /** Reason (tiles) + optional note + notify toggle. */
    public static function rejectForm(): array
    {
        return [
            Forms\Components\ToggleButtons::make('reason')
                ->label(__('moderation.form.reason'))
                ->options(collect(FileModeration::REASONS)->mapWithKeys(fn ($r) => [$r => __('moderation.reason.'.$r)])->all())
                ->icons([
                    'copyright' => 'heroicon-m-scale',
                    'malware' => 'heroicon-m-bug-ant',
                    'illegal' => 'heroicon-m-no-symbol',
                    'adult' => 'heroicon-m-eye-slash',
                    'violence' => 'heroicon-m-hand-raised',
                    'privacy' => 'heroicon-m-finger-print',
                    'spam' => 'heroicon-m-megaphone',
                    'other' => 'heroicon-m-ellipsis-horizontal-circle',
                ])
                ->colors(['malware' => 'danger', 'illegal' => 'danger', 'copyright' => 'warning'])
                ->columns(2)
                ->required(),
            Forms\Components\Textarea::make('note')
                ->label(__('moderation.form.note'))
                ->helperText(__('moderation.form.note_hint'))
                ->rows(3)->maxLength(1000),
            Forms\Components\Toggle::make('notify')
                ->label(__('moderation.form.notify'))
                ->default(true),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFileModeration::route('/'),
            'view' => Pages\ReviewFile::route('/{record}'),
        ];
    }
}
