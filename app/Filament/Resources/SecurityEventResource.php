<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityEventResource\Pages;
use App\Models\SecurityEvent;
use App\Support\Security;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SecurityEventResource extends Resource
{
    protected static ?string $model = SecurityEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('security_admin.events_nav');
    }

    public static function getModelLabel(): string
    {
        return __('security_admin.events_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('security_admin.events_plural');
    }

    // Badge = attacks logged today.
    public static function getNavigationBadge(): ?string
    {
        $count = SecurityEvent::whereDate('created_at', today())->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    private static function sevColor(string $s): string
    {
        return ['critical' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'gray'][$s] ?? 'gray';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('security_admin.when'))
                    ->since()->dateTimeTooltip()->sortable(),

                Tables\Columns\TextColumn::make('severity')
                    ->label(__('security_admin.severity'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __("security_admin.sev.$state"))
                    ->color(fn (string $state) => static::sevColor($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('security_admin.type'))
                    ->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => \Illuminate\Support\Facades\Lang::has("security_admin.types.$state")
                        ? __("security_admin.types.$state")
                        : $state),

                Tables\Columns\TextColumn::make('ip')
                    ->label(__('security_admin.ip'))
                    ->badge()->color('gray')->copyable()->searchable(),

                Tables\Columns\TextColumn::make('country')
                    ->label(__('security_admin.country'))
                    ->placeholder('—')->toggleable(),

                Tables\Columns\TextColumn::make('path')
                    ->label(__('security_admin.path'))
                    ->limit(40)->tooltip(fn (SecurityEvent $r) => $r->path)
                    ->color('gray')->toggleable(),

                Tables\Columns\TextColumn::make('detail')
                    ->label(__('security_admin.detail'))
                    ->limit(50)->tooltip(fn (SecurityEvent $r) => $r->detail)
                    ->wrap()->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('security_admin.member'))
                    ->placeholder(__('security_admin.guest'))
                    ->badge()->color(fn (SecurityEvent $r) => $r->user_id ? 'warning' : 'gray'),

                Tables\Columns\IconColumn::make('blocked')
                    ->label(__('security_admin.blocked_flag'))
                    ->boolean()
                    ->trueIcon('heroicon-m-no-symbol')->trueColor('danger')
                    ->falseIcon('heroicon-m-minus')->falseColor('gray'),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label(__('security_admin.agent'))
                    ->limit(30)->tooltip(fn (SecurityEvent $r) => $r->user_agent)
                    ->color('gray')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->label(__('security_admin.filter_severity'))
                    ->options([
                        'critical' => __('security_admin.sev.critical'),
                        'high' => __('security_admin.sev.high'),
                        'medium' => __('security_admin.sev.medium'),
                        'low' => __('security_admin.sev.low'),
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('security_admin.filter_type'))
                    ->options([
                        'sqli' => __('security_admin.types.sqli'),
                        'xss' => __('security_admin.types.xss'),
                        'traversal' => __('security_admin.types.traversal'),
                        'lfi' => __('security_admin.types.lfi'),
                        'rce' => __('security_admin.types.rce'),
                        'scanner_ua' => __('security_admin.types.scanner_ua'),
                        'honeypot' => __('security_admin.types.honeypot'),
                        'bruteforce' => __('security_admin.types.bruteforce'),
                    ]),
                Tables\Filters\TernaryFilter::make('blocked')
                    ->label(__('security_admin.blocked_flag')),
            ])
            ->actions([
                Tables\Actions\Action::make('block')
                    ->label(__('security_admin.block_ip'))
                    ->icon('heroicon-m-no-symbol')->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (SecurityEvent $r) => ! Security::isBlocked($r->ip))
                    ->action(function (SecurityEvent $r) {
                        Security::block($r->ip, $r->type, __('security_admin.reason_auto', ['type' => $r->type]), auto: false);
                        Notification::make()->success()->title(__('security_admin.block_ip_done'))->send();
                    }),

                Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash')->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('security_admin.events_empty'))
            ->emptyStateIcon('heroicon-o-shield-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSecurityEvents::route('/'),
        ];
    }
}
