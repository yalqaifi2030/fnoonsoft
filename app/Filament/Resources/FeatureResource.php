<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeatureResource\Pages;
use App\Models\Feature;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class FeatureResource extends Resource
{
    protected static ?string $model = Feature::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.publishing');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.features');
    }

    public static function getModelLabel(): string
    {
        return __('nav.feature_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.features');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('feature.section.content'))
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('feature.title'))
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label(__('feature.description'))
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(__('feature.section.icon'))
                    ->icon('heroicon-o-star')
                    ->description(__('feature.icon_hint'))
                    ->schema([
                        Forms\Components\TextInput::make('icon')
                            ->label(__('feature.icon'))
                            ->default('fa-solid fa-star')
                            ->live(onBlur: true)
                            ->columnSpan(2),

                        Forms\Components\Placeholder::make('icon_preview')
                            ->label(__('feature.preview'))
                            ->content(fn (Forms\Get $get) => $get('icon')
                                ? new HtmlString('<i class="'.e($get('icon')).' fa-2x" style="color:#006C35"></i>')
                                : '—'),
                    ])->columns(3),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('feature.section.settings'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Forms\Components\ToggleButtons::make('is_active')
                            ->label(__('feature.status'))
                            ->inline()
                            ->boolean(__('feature.active'), __('feature.inactive'))
                            ->colors([true => 'success', false => 'gray'])
                            ->icons([true => 'heroicon-m-check-circle', false => 'heroicon-m-pause-circle'])
                            ->default(true),

                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('feature.sort_order'))
                            ->numeric()->default(0)
                            ->helperText(__('feature.sort_hint')),
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
                Tables\Columns\TextColumn::make('icon')
                    ->label('')
                    ->html()
                    ->formatStateUsing(fn ($state) => $state
                        ? new HtmlString('<i class="'.e($state).' fa-lg" style="color:#006C35"></i>')
                        : ''),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('feature.title'))
                    ->weight('semibold')
                    ->description(fn (Feature $r) => Str::limit($r->description ?? '', 70))
                    ->searchable(),

                Tables\Columns\TextColumn::make('is_active')
                    ->label(__('feature.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('feature.active') : __('feature.inactive'))
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('feature.status'))
                    ->trueLabel(__('feature.active'))->falseLabel(__('feature.inactive')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),

                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (Feature $r) => $r->is_active ? __('feature.action.deactivate') : __('feature.action.activate'))
                        ->icon(fn (Feature $r) => $r->is_active ? 'heroicon-m-pause-circle' : 'heroicon-m-check-circle')
                        ->color(fn (Feature $r) => $r->is_active ? 'gray' : 'success')
                        ->action(function (Feature $r): void {
                            $r->update(['is_active' => ! $r->is_active]);
                            Notification::make()->success()->title(__('feature.action.updated'))->send();
                        }),

                    Tables\Actions\ReplicateAction::make()
                        ->label(__('feature.action.duplicate'))
                        ->icon('heroicon-m-document-duplicate')->color('gray')
                        ->beforeReplicaSaved(fn (Feature $replica) => $replica->is_active = false)
                        ->successRedirectUrl(fn (Feature $replica) => static::getUrl('edit', ['record' => $replica])),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('feature.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('feature.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('feature.empty'))
            ->emptyStateIcon('heroicon-o-sparkles');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeatures::route('/'),
            'create' => Pages\CreateFeature::route('/create'),
            'edit' => Pages\EditFeature::route('/{record}/edit'),
        ];
    }
}
