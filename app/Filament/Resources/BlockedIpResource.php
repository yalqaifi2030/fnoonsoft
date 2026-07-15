<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockedIpResource\Pages;
use App\Models\BlockedIp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class BlockedIpResource extends Resource
{
    protected static ?string $model = BlockedIp::class;

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';

    protected static ?int $navigationSort = 41;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('security_admin.blocked_nav');
    }

    public static function getModelLabel(): string
    {
        return __('security_admin.blocked_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('security_admin.blocked_plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = BlockedIp::active()->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('ip')
                ->label(__('security_admin.ip'))
                ->required()->ipv4()->maxLength(45)
                ->unique(ignoreRecord: true)
                ->helperText(__('security_admin.add_block_hint'))
                ->extraInputAttributes(['dir' => 'ltr']),

            Forms\Components\TextInput::make('reason')
                ->label(__('security_admin.reason'))
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('ip')
                    ->label(__('security_admin.ip'))
                    ->badge()->color('danger')->copyable()->searchable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label(__('security_admin.reason'))
                    ->limit(50)->tooltip(fn (BlockedIp $r) => $r->reason)->placeholder('—')->wrap(),

                Tables\Columns\TextColumn::make('auto')
                    ->label(__('security_admin.source'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? __('security_admin.auto') : __('security_admin.manual'))
                    ->color(fn (bool $state) => $state ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('hits')
                    ->label(__('security_admin.hits'))
                    ->badge()->color('gray')->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('security_admin.expires'))
                    ->formatStateUsing(fn ($state, BlockedIp $r) => $state === null
                        ? __('security_admin.permanent')
                        : ($r->isActive() ? $state->diffForHumans() : __('security_admin.expired')))
                    ->color(fn ($state, BlockedIp $r) => $state === null ? 'danger' : ($r->isActive() ? 'warning' : 'gray'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('security_admin.when'))
                    ->since()->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('auto')
                    ->label(__('security_admin.source'))
                    ->trueLabel(__('security_admin.auto'))
                    ->falseLabel(__('security_admin.manual'))
                    ->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label(__('security_admin.unblock'))
                    ->icon('heroicon-m-lock-open')
                    ->after(fn () => Cache::forget('sec:blocked')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label(__('security_admin.unblock'))
                        ->after(fn () => Cache::forget('sec:blocked')),
                ]),
            ])
            ->emptyStateHeading(__('security_admin.blocked_empty'))
            ->emptyStateIcon('heroicon-o-shield-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlockedIps::route('/'),
            'create' => Pages\CreateBlockedIp::route('/create'),
        ];
    }
}
