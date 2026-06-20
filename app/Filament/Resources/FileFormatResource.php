<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FileFormatResource\Pages;
use App\Models\FileFormat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class FileFormatResource extends Resource
{
    protected static ?string $model = FileFormat::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 55;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('format_admin.nav');
    }

    public static function getModelLabel(): string
    {
        return __('format_admin.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('format_admin.plural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('extension')
                    ->label(__('format_admin.extension'))
                    ->helperText(__('format_admin.extension_hint'))
                    ->required()->maxLength(16)
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->dehydrateStateUsing(fn ($state) => ltrim(strtolower(trim((string) $state)), '.'))
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('name')
                    ->label(__('format_admin.name'))->required()->maxLength(80),

                Forms\Components\Select::make('family')
                    ->label(__('format_admin.family'))
                    ->options(collect(FileFormat::FAMILIES)->mapWithKeys(fn ($f) => [$f => __('format_admin.fam.'.$f)]))
                    ->default('other')->required()->native(false),

                Forms\Components\ColorPicker::make('color')
                    ->label(__('format_admin.color'))->default('#006C35'),

                Forms\Components\Textarea::make('description')
                    ->label(__('format_admin.description'))->rows(2)->columnSpanFull(),

                Forms\Components\TextInput::make('sort_order')
                    ->label(__('format_admin.sort'))->numeric()->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->label(__('format_admin.active'))->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('extension')
                    ->label(__('format_admin.format'))
                    ->formatStateUsing(fn ($state, FileFormat $r) => new HtmlString(
                        '<span style="display:inline-block;padding:.18rem .55rem;border-radius:.4rem;font-weight:800;color:#fff;font-size:.72rem;background:'.e($r->badgeColor()).'">.'.e(strtoupper((string) $state)).'</span>'
                    ))
                    ->html()->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('format_admin.name'))->weight('semibold')->searchable(),

                Tables\Columns\TextColumn::make('family')
                    ->label(__('format_admin.family'))
                    ->formatStateUsing(fn ($state) => __('format_admin.fam.'.$state))
                    ->badge()->color('gray'),

                Tables\Columns\TextColumn::make('software_count')
                    ->label(__('format_admin.used_by'))
                    ->counts('software')->badge()->color('success'),

                Tables\Columns\ToggleColumn::make('is_active')->label(__('format_admin.active')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('family')
                    ->label(__('format_admin.family'))
                    ->options(collect(FileFormat::FAMILIES)->mapWithKeys(fn ($f) => [$f => __('format_admin.fam.'.$f)])),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),
                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])->icon('heroicon-m-ellipsis-vertical')->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('format_admin.empty'))
            ->emptyStateIcon('heroicon-o-document-duplicate');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFileFormats::route('/'),
            'create' => Pages\CreateFileFormat::route('/create'),
            'edit' => Pages\EditFileFormat::route('/{record}/edit'),
        ];
    }
}
