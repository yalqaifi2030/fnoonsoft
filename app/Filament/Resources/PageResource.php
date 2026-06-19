<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.publishing');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.pages');
    }

    public static function getModelLabel(): string
    {
        return __('nav.page_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.pages');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('page.section.content'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('page.title'))
                            ->required()
                            ->live(onBlur: true)
                            ->maxLength(180)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state, string $operation) {
                                if ($operation === 'create') {
                                    $set('slug', \App\Support\Slug::make($state));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('page.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(200)
                            ->prefixIcon('heroicon-m-link')
                            ->helperText(fn (?Page $record) => $record ? url('/'.$record->slug) : null),

                        Forms\Components\RichEditor::make('body')
                            ->label(__('page.body'))
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('page-attachments')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(__('page.section.seo'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->description(__('page.seo_hint'))
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')->label(__('page.meta_title'))->maxLength(180),
                        Forms\Components\Textarea::make('meta_description')->label(__('page.meta_description'))->rows(2)->maxLength(300),
                    ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('page.section.publish'))
                    ->icon('heroicon-o-paper-airplane')
                    ->schema([
                        Forms\Components\ToggleButtons::make('is_published')
                            ->label(__('page.status'))
                            ->inline()
                            ->boolean(__('page.published'), __('page.draft'))
                            ->colors([true => 'success', false => 'gray'])
                            ->icons([true => 'heroicon-m-check-circle', false => 'heroicon-m-pencil'])
                            ->default(true),

                        Forms\Components\Placeholder::make('updated_at_info')
                            ->label(__('page.updated'))
                            ->content(fn (?Page $record) => $record?->updated_at?->diffForHumans() ?? '—'),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('page.title'))
                    ->weight('semibold')
                    ->icon('heroicon-m-document-text')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('page.slug'))
                    ->badge()
                    ->color('gold')
                    ->prefix('/')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('is_published')
                    ->label(__('page.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('page.published') : __('page.draft'))
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('page.updated'))
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label(__('page.status'))
                    ->trueLabel(__('page.published'))
                    ->falseLabel(__('page.draft')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_site')
                        ->label(__('page.action.view_site'))
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->color('gray')
                        ->url(fn (Page $r) => url('/'.$r->slug))
                        ->openUrlInNewTab()
                        ->visible(fn (Page $r) => (bool) $r->is_published),

                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->url(fn (Page $r) => static::getUrl('view', ['record' => $r])),

                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),

                    Tables\Actions\Action::make('toggle_publish')
                        ->label(fn (Page $r) => $r->is_published ? __('page.action.unpublish') : __('page.action.publish'))
                        ->icon(fn (Page $r) => $r->is_published ? 'heroicon-m-eye-slash' : 'heroicon-m-check-circle')
                        ->color(fn (Page $r) => $r->is_published ? 'gray' : 'success')
                        ->requiresConfirmation()
                        ->action(function (Page $r): void {
                            $r->update(['is_published' => ! $r->is_published]);
                            Notification::make()->success()->title(__('page.action.updated'))->send();
                        }),

                    Tables\Actions\ReplicateAction::make()
                        ->label(__('page.action.duplicate'))
                        ->icon('heroicon-m-document-duplicate')
                        ->color('gray')
                        ->excludeAttributes(['slug'])
                        ->beforeReplicaSaved(function (Page $replica, Page $original): void {
                            $replica->is_published = false;
                            $replica->slug = $original->slug.'-copy-'.Str::lower(Str::random(5));
                        })
                        ->successRedirectUrl(fn (Page $replica) => static::getUrl('edit', ['record' => $replica])),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('page.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('page.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')
                        ->label(__('page.action.publish'))
                        ->icon('heroicon-m-check-circle')->color('success')
                        ->action(fn ($records) => $records->each->update(['is_published' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label(__('page.action.unpublish'))
                        ->icon('heroicon-m-eye-slash')->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_published' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('page.empty'))
            ->emptyStateIcon('heroicon-o-document-text');
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'view' => Pages\ViewPage::route('/{record}'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
