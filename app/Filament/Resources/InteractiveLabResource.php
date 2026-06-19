<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractiveLabResource\Pages;
use App\Models\InteractiveLab;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class InteractiveLabResource extends Resource
{
    protected static ?string $model = InteractiveLab::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.learn');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.labs');
    }

    public static function getModelLabel(): string
    {
        return __('nav.lab_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.labs');
    }

    /**
     * Admin-created labs are rendered by the generic, data-driven block engine
     * (see resources/views/partials/labs/_blocks.blade.php). The 5 seeded labs
     * keep their hand-built partials, which take priority by matching `key`.
     */

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('learn_admin.section.details'))
                    ->icon('heroicon-o-beaker')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Key')->disabled()->dehydrated(false)->hiddenOn('create'),
                        Forms\Components\TextInput::make('title')
                            ->label(__('learn_admin.name'))->required(),
                        Forms\Components\Textarea::make('description')
                            ->label(__('learn_admin.description'))->rows(3)->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make(__('learn_admin.section.appearance'))
                    ->icon('heroicon-o-swatch')
                    ->schema([
                        Forms\Components\TextInput::make('icon')->label(__('learn_admin.icon'))->live(onBlur: true),
                        Forms\Components\Placeholder::make('icon_preview')
                            ->label(__('learn_admin.preview'))
                            ->content(fn (Forms\Get $get) => $get('icon')
                                ? new HtmlString('<i class="'.e($get('icon')).' fa-2x" style="color:#006C35"></i>')
                                : '—'),
                        Forms\Components\Select::make('color')
                            ->label(__('learn_admin.color'))
                            ->options(LearningCategoryResource::colors()),
                    ])->columns(2),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('learn_admin.section.settings'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Forms\Components\ToggleButtons::make('is_active')
                            ->label(__('learn_admin.status'))->inline()
                            ->boolean(__('learn_admin.active'), __('learn_admin.inactive'))
                            ->colors([true => 'success', false => 'gray'])
                            ->icons([true => 'heroicon-m-check-circle', false => 'heroicon-m-pause-circle'])
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')->label(__('learn_admin.sort_order'))->numeric()->default(0),
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
                Tables\Columns\TextColumn::make('title')
                    ->label(__('learn_admin.name'))->weight('semibold')
                    ->description(fn ($record) => \Illuminate\Support\Str::limit($record->description ?? '', 60))
                    ->searchable(),
                Tables\Columns\TextColumn::make('key')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('items_count')
                    ->label(__('lab_item.title'))
                    ->counts('items')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('is_active')
                    ->label(__('learn_admin.status'))->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('learn_admin.active') : __('learn_admin.inactive'))
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label(__('learn_admin.status')),
            ])
            ->recordUrl(fn (InteractiveLab $r) => static::getUrl('edit', ['record' => $r]))
            ->actions([
                Tables\Actions\Action::make('manage_items')
                    ->label(__('learn_admin.action.manage_items'))
                    ->icon('heroicon-m-beaker')
                    ->color('primary')
                    ->button()->outlined()
                    ->url(fn (InteractiveLab $r) => static::getUrl('edit', ['record' => $r])),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->url(fn (InteractiveLab $r) => static::getUrl('view', ['record' => $r])),
                    Tables\Actions\Action::make('view_site')
                        ->label(__('learn_admin.action.view_site'))
                        ->icon('heroicon-m-arrow-top-right-on-square')->color('gray')
                        ->url(fn (InteractiveLab $r) => route('learn.lab', $r))->openUrlInNewTab(),
                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (InteractiveLab $r) => $r->is_active ? __('learn_admin.action.deactivate') : __('learn_admin.action.activate'))
                        ->icon(fn (InteractiveLab $r) => $r->is_active ? 'heroicon-m-pause-circle' : 'heroicon-m-check-circle')
                        ->color(fn (InteractiveLab $r) => $r->is_active ? 'gray' : 'success')
                        ->action(function (InteractiveLab $r): void {
                            $r->update(['is_active' => ! $r->is_active]);
                            Notification::make()->success()->title(__('learn_admin.action.updated'))->send();
                        }),
                ])->label(__('learn_admin.action.menu'))->icon('heroicon-m-ellipsis-vertical')->color('gray'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InteractiveLabResource\RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInteractiveLabs::route('/'),
            'create' => Pages\CreateInteractiveLab::route('/create'),
            'view' => Pages\ViewInteractiveLab::route('/{record}'),
            'edit' => Pages\EditInteractiveLab::route('/{record}/edit'),
        ];
    }
}
