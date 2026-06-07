<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Filament\Resources\BannerResource\RelationManagers;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.publishing');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.banners');
    }

    public static function getModelLabel(): string
    {
        return __('nav.banner_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.banners');
    }

    /** @return array<string,string> position value => label */
    public static function positions(): array
    {
        return [
            'home_top' => __('banner.position.home_top'),
            'home_middle' => __('banner.position.home_middle'),
            'sidebar' => __('banner.position.sidebar'),
            'footer' => __('banner.position.footer'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('banner.section.media'))
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label(__('banner.image'))
                            ->image()
                            ->imageEditor()
                            ->directory('banners')
                            ->disk('public')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('title')
                            ->label(__('banner.title'))
                            ->maxLength(180),

                        Forms\Components\TextInput::make('link')
                            ->label(__('banner.link'))
                            ->url()
                            ->prefixIcon('heroicon-m-link')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make(__('banner.section.schedule'))
                    ->icon('heroicon-o-calendar-days')
                    ->description(__('banner.schedule_hint'))
                    ->collapsed()
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label(__('banner.starts_at'))->native(false)->seconds(false),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label(__('banner.ends_at'))->native(false)->seconds(false)
                            ->after('starts_at'),
                    ])->columns(2),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('banner.section.display'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Forms\Components\ToggleButtons::make('is_active')
                            ->label(__('banner.status'))
                            ->inline()
                            ->boolean(__('banner.active'), __('banner.inactive'))
                            ->colors([true => 'success', false => 'gray'])
                            ->icons([true => 'heroicon-m-check-circle', false => 'heroicon-m-pause-circle'])
                            ->default(true),

                        Forms\Components\Select::make('position')
                            ->label(__('banner.position_label'))
                            ->options(self::positions())
                            ->default('home_top')
                            ->required(),

                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('banner.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('banner.sort_hint')),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('banner.image'))
                    ->disk('public')
                    ->height(40)->width(96)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('banner.title'))
                    ->weight('semibold')
                    ->placeholder('—')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('position')
                    ->label(__('banner.position_label'))
                    ->badge()
                    ->color('gold')
                    ->formatStateUsing(fn ($state) => self::positions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('is_active')
                    ->label(__('banner.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('banner.active') : __('banner.inactive'))
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label(__('banner.ends_at'))
                    ->dateTime('Y-m-d')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('position')
                    ->label(__('banner.position_label'))
                    ->options(self::positions()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('banner.status'))
                    ->trueLabel(__('banner.active'))
                    ->falseLabel(__('banner.inactive')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('visit_link')
                        ->label(__('banner.action.visit'))
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->color('gray')
                        ->url(fn (Banner $r) => $r->link)
                        ->openUrlInNewTab()
                        ->visible(fn (Banner $r) => filled($r->link)),

                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),

                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (Banner $r) => $r->is_active ? __('banner.action.deactivate') : __('banner.action.activate'))
                        ->icon(fn (Banner $r) => $r->is_active ? 'heroicon-m-pause-circle' : 'heroicon-m-check-circle')
                        ->color(fn (Banner $r) => $r->is_active ? 'gray' : 'success')
                        ->requiresConfirmation()
                        ->action(function (Banner $r): void {
                            $r->update(['is_active' => ! $r->is_active]);
                            Notification::make()->success()->title(__('banner.action.updated'))->send();
                        }),

                    Tables\Actions\ReplicateAction::make()
                        ->label(__('banner.action.duplicate'))
                        ->icon('heroicon-m-document-duplicate')
                        ->color('gray')
                        ->beforeReplicaSaved(fn (Banner $replica) => $replica->is_active = false)
                        ->successRedirectUrl(fn (Banner $replica) => static::getUrl('edit', ['record' => $replica])),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('banner.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('banner.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('banner.action.activate'))
                        ->icon('heroicon-m-check-circle')->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('banner.action.deactivate'))
                        ->icon('heroicon-m-pause-circle')->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('banner.empty'))
            ->emptyStateIcon('heroicon-o-photo');
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
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
