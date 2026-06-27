<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeveloperResource\Pages;
use App\Filament\Resources\DeveloperResource\RelationManagers;
use App\Models\Developer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class DeveloperResource extends Resource
{
    protected static ?string $model = Developer::class;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket-square';

    protected static ?int $navigationSort = 3;

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
        return __('nav.developers');
    }

    public static function getModelLabel(): string
    {
        return __('nav.developer_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.developers');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('developer.section.details'))
                    ->icon('heroicon-o-code-bracket-square')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('developer.name'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state, string $operation) {
                                if ($operation === 'create') {
                                    $set('slug', \App\Support\Slug::make($state));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('developer.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->prefixIcon('heroicon-m-link'),

                        Forms\Components\TextInput::make('website')
                            ->label(__('developer.website'))
                            ->url()
                            ->prefixIcon('heroicon-m-globe-alt')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('email')
                            ->label(__('developer.email'))
                            ->email()
                            ->prefixIcon('heroicon-m-envelope'),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('developer.phone'))
                            ->helperText(__('developer.phone_hint'))
                            ->prefixIcon('heroicon-m-chat-bubble-left-right'),
                        Forms\Components\TextInput::make('twitter')
                            ->label(__('developer.twitter'))
                            ->helperText(__('developer.twitter_hint'))
                            ->prefixIcon('heroicon-m-at-symbol')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label(__('developer.description'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('developer.section.settings'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Forms\Components\ToggleButtons::make('is_verified')
                            ->label(__('developer.status'))
                            ->inline()
                            ->boolean(__('developer.verified'), __('developer.unverified'))
                            ->colors([true => 'success', false => 'gray'])
                            ->icons([true => 'heroicon-m-check-badge', false => 'heroicon-m-minus-circle'])
                            ->default(false),
                    ]),

                Forms\Components\Section::make(__('developer.section.logo'))
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label(__('developer.logo'))
                            ->image()->imageEditor()
                            ->directory('developers')->disk('public')
                            ->avatar(),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn (Developer $r) => 'https://ui-avatars.com/api/?name='.urlencode($r->name).'&background=006C35&color=fff'),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('developer.name'))
                    ->weight('semibold')
                    ->description(fn (Developer $r) => $r->website)
                    ->searchable(),

                Tables\Columns\TextColumn::make('software_count')
                    ->label(__('developer.items'))
                    ->counts('software')
                    ->badge()->color('primary'),

                Tables\Columns\TextColumn::make('is_verified')
                    ->label(__('developer.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('developer.verified') : __('developer.unverified'))
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-badge' : null)
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('developer.added'))
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label(__('developer.status'))
                    ->trueLabel(__('developer.verified'))
                    ->falseLabel(__('developer.unverified')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('visit_website')
                        ->label(__('developer.action.visit'))
                        ->icon('heroicon-m-arrow-top-right-on-square')->color('gray')
                        ->url(fn (Developer $r) => $r->website)
                        ->openUrlInNewTab()
                        ->visible(fn (Developer $r) => filled($r->website)),

                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),

                    Tables\Actions\Action::make('toggle_verified')
                        ->label(fn (Developer $r) => $r->is_verified ? __('developer.action.unverify') : __('developer.action.verify'))
                        ->icon(fn (Developer $r) => $r->is_verified ? 'heroicon-m-minus-circle' : 'heroicon-m-check-badge')
                        ->color(fn (Developer $r) => $r->is_verified ? 'gray' : 'success')
                        ->action(function (Developer $r): void {
                            $r->update(['is_verified' => ! $r->is_verified]);
                            Notification::make()->success()->title(__('developer.action.updated'))->send();
                        }),

                    Tables\Actions\ReplicateAction::make()
                        ->label(__('developer.action.duplicate'))
                        ->icon('heroicon-m-document-duplicate')->color('gray')
                        ->excludeAttributes(['slug'])
                        ->beforeReplicaSaved(fn (Developer $replica, Developer $original) => $replica->slug = $original->slug.'-copy-'.Str::lower(Str::random(5)))
                        ->successRedirectUrl(fn (Developer $replica) => static::getUrl('edit', ['record' => $replica])),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('developer.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('developer.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('verify')
                        ->label(__('developer.action.verify'))
                        ->icon('heroicon-m-check-badge')->color('success')
                        ->action(fn ($records) => $records->each->update(['is_verified' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('unverify')
                        ->label(__('developer.action.unverify'))
                        ->icon('heroicon-m-minus-circle')->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_verified' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading(__('developer.empty'))
            ->emptyStateIcon('heroicon-o-code-bracket-square');
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
            'index' => Pages\ListDevelopers::route('/'),
            'create' => Pages\CreateDeveloper::route('/create'),
            'edit' => Pages\EditDeveloper::route('/{record}/edit'),
        ];
    }
}
