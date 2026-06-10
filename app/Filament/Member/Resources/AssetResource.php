<?php

namespace App\Filament\Member\Resources;

use App\Filament\Member\Resources\AssetResource\Pages;
use App\Filament\Upload\Resources\AssetResource as StaffAssets;
use App\Models\Asset;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "My Files" — the signed-in member's own assets, scoped strictly to them.
 * Reuses the staff AssetResource helpers (copy link, share kit, password) with a
 * leaner member-friendly table plus a rename action. Registering this resource
 * also defines the `filament.member.resources.assets.index` route, which the
 * upload-center "view all" link points to.
 */
class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('member.files.nav');
    }

    public static function getModelLabel(): string
    {
        return __('member.files.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('member.files.plural');
    }

    /** Files are created by uploading, never by hand. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** Strictly the signed-in member's own files. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
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
                    ->height(44)->width(44)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->visibility('public'),

                Tables\Columns\TextColumn::make('original_name')
                    ->label(__('member.files.name'))
                    ->weight('semibold')
                    ->description(fn (Asset $r) => StaffAssets::humanSize($r->size_bytes))
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('kind')
                    ->label(__('member.files.type'))
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

                Tables\Columns\TextColumn::make('status')
                    ->label(__('member.files.status'))
                    ->badge()
                    ->state(fn (Asset $r) => $r->statusKey())
                    ->formatStateUsing(fn ($state) => __('asset_admin.status_'.$state))
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success', 'expired' => 'danger', default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('password')
                    ->label(__('asset_admin.protected'))
                    ->state(fn (Asset $r) => $r->hasPassword())
                    ->boolean()
                    ->trueIcon('heroicon-s-lock-closed')->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('downloads_count')
                    ->label(__('member.files.downloads'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->numeric()->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('member.files.created'))
                    ->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kind')
                    ->label(__('member.files.type'))
                    ->options([
                        'image' => __('asset_admin.kind_image'),
                        'pdf' => __('asset_admin.kind_pdf'),
                        'file' => __('asset_admin.kind_file'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    StaffAssets::copyLinkAction(),
                    StaffAssets::shareAction(),

                    Tables\Actions\Action::make('open')
                        ->label(__('asset_admin.action.open'))
                        ->icon('heroicon-m-arrow-top-right-on-square')->color('gray')
                        ->url(fn (Asset $r) => $r->pageUrl())->openUrlInNewTab(),

                    static::renameAction(),
                    StaffAssets::passwordAction(),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('asset_admin.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()->outlined(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('member.files.empty'))
            ->emptyStateDescription(__('member.files.empty_desc'))
            ->emptyStateIcon('heroicon-o-folder-open');
    }

    /** Let a member rename their own file (the display name). */
    public static function renameAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('rename')
            ->label(__('member.files.rename'))
            ->icon('heroicon-m-pencil-square')->color('gray')
            ->fillForm(fn (Asset $r) => ['original_name' => $r->original_name])
            ->form([
                Forms\Components\TextInput::make('original_name')
                    ->label(__('member.files.name'))
                    ->required()->maxLength(255),
            ])
            ->action(function (Asset $r, array $data) {
                $r->update(['original_name' => $data['original_name']]);
                Notification::make()->success()->title(__('asset_admin.msg.updated'))->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
        ];
    }
}
