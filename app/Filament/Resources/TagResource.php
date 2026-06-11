<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
use App\Filament\Resources\TagResource\RelationManagers;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.tags');
    }

    public static function getModelLabel(): string
    {
        return __('nav.tag_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.tags');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('tag.section.details'))
                ->icon('heroicon-o-tag')
                ->description(__('tag.hint'))
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('tag.name'))
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Forms\Set $set, ?string $state, string $operation) {
                            if ($operation === 'create') {
                                $set('slug', \App\Support\Slug::make($state));
                            }
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->label(__('tag.slug'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->prefixIcon('heroicon-m-hashtag'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('tag.name'))
                    ->badge()
                    ->color('gold')
                    ->icon('heroicon-m-hashtag')
                    ->searchable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('tag.slug'))
                    ->color('gray')
                    ->prefix('/')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('software_count')
                    ->label(__('tag.items'))
                    ->counts('software')
                    ->badge()->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('tag.added'))
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),

                    Tables\Actions\ReplicateAction::make()
                        ->label(__('tag.action.duplicate'))
                        ->icon('heroicon-m-document-duplicate')->color('gray')
                        ->excludeAttributes(['slug'])
                        ->beforeReplicaSaved(fn (Tag $replica, Tag $original) => $replica->slug = $original->slug.'-copy-'.Str::lower(Str::random(5)))
                        ->successRedirectUrl(fn (Tag $replica) => static::getUrl('edit', ['record' => $replica])),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('tag.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('tag.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading(__('tag.empty'))
            ->emptyStateIcon('heroicon-o-tag');
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
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
        ];
    }
}
