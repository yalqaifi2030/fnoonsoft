<?php

namespace App\Filament\Resources;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Filament\Resources\MobileAppResource\Pages;
use App\Models\Software;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dedicated management page for Mobile App Templates. Reuses the Software model
 * and the shared form, scoped to content_type = mobile_app so apps are managed
 * here instead of in the general Content resource.
 */
class MobileAppResource extends Resource
{
    protected static ?string $model = Software::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.mobile_apps');
    }

    public static function getModelLabel(): string
    {
        return __('nav.mobile_app_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.mobile_apps');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('content_type', ContentType::MobileApp->value);
    }

    public static function form(Form $form): Form
    {
        // Reuse the full, shared software form (the content-type field hides
        // itself here; store + live-preview sections show automatically).
        return SoftwareResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(asset('favicon.ico')),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('software.name'))
                    ->searchable()->sortable()
                    ->weight('bold')
                    ->description(fn (Software $r) => $r->category?->name),

                Tables\Columns\TextColumn::make('current_version')
                    ->label(__('software.version'))
                    ->badge()->color('gray')->placeholder('—'),

                Tables\Columns\IconColumn::make('live_preview_url')
                    ->label(__('software.section.live'))
                    ->boolean()
                    ->trueIcon('heroicon-s-play-circle')->falseIcon('heroicon-o-minus')
                    ->trueColor('success')->falseColor('gray')
                    ->getStateUsing(fn (Software $r) => $r->hasLivePreview())
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('software.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ContentStatus ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof ContentStatus ? $state->color() : 'gray'),

                Tables\Columns\TextColumn::make('downloads_count')
                    ->label(__('software.downloads'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->numeric()->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label(__('software.featured'))
                    ->boolean()
                    ->trueIcon('heroicon-s-star')->falseIcon('heroicon-o-star')
                    ->trueColor('warning')->falseColor('gray')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('software.status'))->options(ContentStatus::options()),
                Tables\Filters\TernaryFilter::make('is_featured')->label(__('software.featured')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_site')
                        ->label(__('software.action.view_site'))
                        ->icon('heroicon-m-arrow-top-right-on-square')->color('gray')
                        ->url(fn (Software $r) => route('software.show', $r))
                        ->openUrlInNewTab()
                        ->visible(fn (Software $r) => $r->status === ContentStatus::Published),

                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->url(fn (Software $r) => static::getUrl('view', ['record' => $r])),

                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-pencil-square')
                        ->url(fn (Software $r) => static::getUrl('edit', ['record' => $r])),

                    Tables\Actions\Action::make('toggle_publish')
                        ->label(fn (Software $r) => $r->status === ContentStatus::Published ? __('software.action.unpublish') : __('software.action.publish'))
                        ->icon(fn (Software $r) => $r->status === ContentStatus::Published ? 'heroicon-m-eye-slash' : 'heroicon-m-check-circle')
                        ->color(fn (Software $r) => $r->status === ContentStatus::Published ? 'gray' : 'success')
                        ->requiresConfirmation()
                        ->action(function (Software $r): void {
                            $publishing = $r->status !== ContentStatus::Published;
                            $r->update([
                                'status' => $publishing ? ContentStatus::Published->value : ContentStatus::Draft->value,
                                'published_at' => $publishing ? ($r->published_at ?? now()) : $r->published_at,
                            ]);
                            Notification::make()->success()->title(__('software.action.updated'))->send();
                        }),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('software.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('software.empty'))
            ->emptyStateIcon('heroicon-o-device-phone-mobile');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMobileApps::route('/'),
            'create' => Pages\CreateMobileApp::route('/create'),
            'view' => Pages\ViewMobileApp::route('/{record}'),
            'edit' => Pages\EditMobileApp::route('/{record}/edit'),
        ];
    }
}
