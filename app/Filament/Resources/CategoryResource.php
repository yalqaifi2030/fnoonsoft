<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use App\Enums\ContentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.categories');
    }

    public static function getModelLabel(): string
    {
        return __('nav.category_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.categories');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('category.section.details'))
                    ->icon('heroicon-o-rectangle-stack')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('category.name'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state, string $operation) {
                                if ($operation === 'create') {
                                    $set('slug', \App\Support\Slug::make($state));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('category.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->prefixIcon('heroicon-m-link'),

                        Forms\Components\Textarea::make('description')
                            ->label(__('category.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make(__('category.section.icon'))
                    ->icon('heroicon-o-sparkles')
                    ->description(__('category.icon_hint'))
                    ->schema([
                        \App\Filament\Forms\Components\IconPicker::make('icon')
                            ->label(__('category.icon')),
                    ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('category.section.settings'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Forms\Components\ToggleButtons::make('is_active')
                            ->label(__('category.status'))
                            ->inline()
                            ->boolean(__('category.active'), __('category.inactive'))
                            ->colors([true => 'success', false => 'gray'])
                            ->icons([true => 'heroicon-m-check-circle', false => 'heroicon-m-pause-circle'])
                            ->default(true),

                        Forms\Components\Select::make('parent_id')
                            ->label(__('category.parent'))
                            ->options(fn () => Category::query()
                                ->whereNull('parent_id')
                                ->get()
                                ->mapWithKeys(fn (Category $c) => [$c->id => $c->name])
                                ->all())
                            ->searchable()
                            ->placeholder(__('category.no_parent')),

                        Forms\Components\Select::make('content_type')
                            ->label(__('category.content_type'))
                            ->options(ContentType::options())
                            ->placeholder(__('category.any_type')),

                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('category.sort_order'))
                            ->numeric()->default(0)
                            ->helperText(__('category.sort_hint')),
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

                Tables\Columns\TextColumn::make('name')
                    ->label(__('category.name'))
                    ->weight('semibold')
                    ->description(fn (Category $r) => $r->parent?->name)
                    ->searchable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('category.slug'))
                    ->badge()->color('gold')->prefix('/')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('content_type')
                    ->label(__('category.content_type'))
                    ->badge()
                    ->placeholder(__('category.any_type'))
                    ->formatStateUsing(fn ($state) => $state instanceof ContentType ? $state->label() : ($state ?: __('category.any_type'))),

                Tables\Columns\TextColumn::make('software_count')
                    ->label(__('category.items'))
                    ->counts('software')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('is_active')
                    ->label(__('category.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('category.active') : __('category.inactive'))
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('content_type')
                    ->label(__('category.content_type'))->options(ContentType::options()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('category.status'))
                    ->trueLabel(__('category.active'))->falseLabel(__('category.inactive')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_site')
                        ->label(__('category.action.view_site'))
                        ->icon('heroicon-m-arrow-top-right-on-square')->color('gray')
                        ->url(fn (Category $r) => route('browse', ['category' => $r->slug]))
                        ->openUrlInNewTab(),

                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),

                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (Category $r) => $r->is_active ? __('category.action.deactivate') : __('category.action.activate'))
                        ->icon(fn (Category $r) => $r->is_active ? 'heroicon-m-pause-circle' : 'heroicon-m-check-circle')
                        ->color(fn (Category $r) => $r->is_active ? 'gray' : 'success')
                        ->action(function (Category $r): void {
                            $r->update(['is_active' => ! $r->is_active]);
                            Notification::make()->success()->title(__('category.action.updated'))->send();
                        }),

                    Tables\Actions\ReplicateAction::make()
                        ->label(__('category.action.duplicate'))
                        ->icon('heroicon-m-document-duplicate')->color('gray')
                        ->excludeAttributes(['slug'])
                        ->beforeReplicaSaved(fn (Category $replica, Category $original) => $replica->slug = $original->slug.'-copy-'.Str::lower(Str::random(5)))
                        ->successRedirectUrl(fn (Category $replica) => static::getUrl('edit', ['record' => $replica])),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('category.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('category.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('category.action.activate'))
                        ->icon('heroicon-m-check-circle')->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('category.action.deactivate'))
                        ->icon('heroicon-m-pause-circle')->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('category.empty'))
            ->emptyStateIcon('heroicon-o-rectangle-stack');
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
