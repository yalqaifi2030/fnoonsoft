<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LearningCategoryResource\Pages;
use App\Filament\Resources\LearningCategoryResource\RelationManagers;
use App\Models\LearningCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class LearningCategoryResource extends Resource
{
    protected static ?string $model = LearningCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.learn');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.learning_categories');
    }

    public static function getModelLabel(): string
    {
        return __('nav.learning_category_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.learning_categories');
    }

    /** @return array<string,string> tailwind gradient => label */
    public static function colors(): array
    {
        return [
            'from-emerald-500 to-green-700' => 'Green',
            'from-sky-500 to-indigo-700' => 'Blue',
            'from-fuchsia-500 to-purple-700' => 'Purple',
            'from-rose-500 to-red-700' => 'Red',
            'from-amber-400 to-orange-600' => 'Amber',
            'from-cyan-500 to-teal-700' => 'Teal',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('learn_admin.section.details'))
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('learn_admin.name'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state, string $operation) {
                                if ($operation === 'create') {
                                    $set('slug', \App\Support\Slug::make($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->label(__('learn_admin.slug'))
                            ->required()->unique(ignoreRecord: true)
                            ->prefixIcon('heroicon-m-link'),
                        Forms\Components\Textarea::make('description')
                            ->label(__('learn_admin.description'))->rows(3)->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make(__('learn_admin.section.appearance'))
                    ->icon('heroicon-o-swatch')
                    ->schema([
                        Forms\Components\TextInput::make('icon')
                            ->label(__('learn_admin.icon'))
                            ->default('fa-solid fa-graduation-cap')
                            ->live(onBlur: true)
                            ->helperText('fa-solid fa-microchip'),
                        Forms\Components\Placeholder::make('icon_preview')
                            ->label(__('learn_admin.preview'))
                            ->content(fn (Forms\Get $get) => $get('icon')
                                ? new HtmlString('<i class="'.e($get('icon')).' fa-2x" style="color:#006C35"></i>')
                                : '—'),
                        Forms\Components\Select::make('color')
                            ->label(__('learn_admin.color'))
                            ->options(self::colors())
                            ->default('from-emerald-500 to-green-700'),
                    ])->columns(2),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('learn_admin.section.settings'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Forms\Components\ToggleButtons::make('is_active')
                            ->label(__('learn_admin.status'))
                            ->inline()
                            ->boolean(__('learn_admin.active'), __('learn_admin.inactive'))
                            ->colors([true => 'success', false => 'gray'])
                            ->icons([true => 'heroicon-m-check-circle', false => 'heroicon-m-pause-circle'])
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('learn_admin.sort_order'))->numeric()->default(0),
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
                    ->label('')->html()
                    ->formatStateUsing(fn ($state) => $state ? new HtmlString('<i class="'.e($state).' fa-lg" style="color:#006C35"></i>') : ''),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('learn_admin.name'))->weight('semibold')->searchable(),
                Tables\Columns\TextColumn::make('videos_count')
                    ->label(__('learn_admin.videos'))->counts('videos')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('is_active')
                    ->label(__('learn_admin.status'))->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('learn_admin.active') : __('learn_admin.inactive'))
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label(__('learn_admin.status')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_site')
                        ->label(__('learn_admin.action.view_site'))
                        ->icon('heroicon-m-arrow-top-right-on-square')->color('gray')
                        ->url(fn (LearningCategory $r) => route('learn.category', $r))->openUrlInNewTab(),
                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (LearningCategory $r) => $r->is_active ? __('learn_admin.action.deactivate') : __('learn_admin.action.activate'))
                        ->icon(fn (LearningCategory $r) => $r->is_active ? 'heroicon-m-pause-circle' : 'heroicon-m-check-circle')
                        ->color(fn (LearningCategory $r) => $r->is_active ? 'gray' : 'success')
                        ->action(function (LearningCategory $r): void {
                            $r->update(['is_active' => ! $r->is_active]);
                            Notification::make()->success()->title(__('learn_admin.action.updated'))->send();
                        }),
                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('learn_admin.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->emptyStateHeading(__('learn_admin.empty'))
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VideosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLearningCategories::route('/'),
            'create' => Pages\CreateLearningCategory::route('/create'),
            'edit' => Pages\EditLearningCategory::route('/{record}/edit'),
        ];
    }
}
