<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterSubscriberResource\Pages;
use App\Models\NewsletterSubscriber;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 80;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.publishing');
    }

    public static function getNavigationLabel(): string
    {
        return __('newsletter_admin.nav');
    }

    public static function getModelLabel(): string
    {
        return __('newsletter_admin.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('newsletter_admin.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = NewsletterSubscriber::active()->count();

        return $count ?: null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label(__('newsletter_admin.email'))
                    ->icon('heroicon-m-envelope')
                    ->copyable()->searchable()->weight('medium'),

                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label(__('newsletter_admin.status'))
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')->falseIcon('heroicon-m-x-circle')
                    ->trueColor('success')->falseColor('gray'),

                Tables\Columns\TextColumn::make('locale')
                    ->label(__('newsletter_admin.locale'))
                    ->badge()->color('gray')->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('newsletter_admin.joined'))
                    ->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_confirmed')
                    ->label(__('newsletter_admin.status'))
                    ->trueLabel(__('newsletter_admin.active'))
                    ->falseLabel(__('newsletter_admin.unsubscribed'))
                    ->placeholder(__('newsletter_admin.all')),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('newsletter_admin.empty'))
            ->emptyStateIcon('heroicon-o-envelope-open');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterSubscribers::route('/'),
        ];
    }
}
