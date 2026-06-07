<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Filament\Resources\ArticleResource\RelationManagers;
use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug'];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.publishing');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.articles');
    }

    public static function getModelLabel(): string
    {
        return __('nav.article_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.articles');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('article.section.content'))
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('article.title'))
                            ->required()
                            ->live(onBlur: true)
                            ->maxLength(180)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state, string $operation) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state ?? ''));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('article.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(200)
                            ->prefixIcon('heroicon-m-link'),

                        Forms\Components\Textarea::make('excerpt')
                            ->label(__('article.excerpt'))
                            ->rows(3)
                            ->maxLength(300)
                            ->helperText(__('article.excerpt_hint')),

                        Forms\Components\RichEditor::make('body')
                            ->label(__('article.body'))
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('article-attachments')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(__('article.section.seo'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->description(__('article.seo_hint'))
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')->label(__('article.meta_title'))->maxLength(180),
                        Forms\Components\Textarea::make('meta_description')->label(__('article.meta_description'))->rows(2)->maxLength(300),
                    ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('article.section.publish'))
                    ->icon('heroicon-o-paper-airplane')
                    ->schema([
                        Forms\Components\ToggleButtons::make('status')
                            ->label(__('article.status'))
                            ->inline()
                            ->options([
                                'draft' => __('article.draft'),
                                'published' => __('article.published'),
                            ])
                            ->colors(['draft' => 'gray', 'published' => 'success'])
                            ->icons(['draft' => 'heroicon-m-pencil', 'published' => 'heroicon-m-check-circle'])
                            ->default('draft')
                            ->required(),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label(__('article.published_at'))
                            ->native(false)
                            ->seconds(false)
                            ->default(now()),

                        Forms\Components\Placeholder::make('views_count')
                            ->label(__('article.views'))
                            ->content(fn (?Article $record): string => $record ? number_format($record->views_count) : '0'),
                    ]),

                Forms\Components\Section::make(__('article.section.organize'))
                    ->icon('heroicon-o-folder')
                    ->schema([
                        Forms\Components\Select::make('article_category_id')
                            ->label(__('article.category'))
                            ->relationship('category')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\TextInput::make('slug')->required()->unique('article_categories', 'slug'),
                            ]),

                        Forms\Components\Select::make('user_id')
                            ->label(__('article.author'))
                            ->relationship('author', 'name')
                            ->searchable()
                            ->preload()
                            ->default(auth()->id()),
                    ]),

                Forms\Components\Section::make(__('article.section.cover'))
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Forms\Components\FileUpload::make('cover_image')
                            ->label(__('article.cover'))
                            ->image()
                            ->imageEditor()
                            ->directory('articles')
                            ->disk('public'),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('')
                    ->disk('public')
                    ->height(44)->width(64)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('article.title'))
                    ->weight('semibold')
                    ->description(fn (Article $r) => Str::limit(strip_tags($r->excerpt ?? ''), 60))
                    ->searchable()
                    ->limit(45),

                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('article.category'))
                    ->badge()
                    ->color('gold')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('author.name')
                    ->label(__('article.author'))
                    ->icon('heroicon-m-user')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('article.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('article.'.$state))
                    ->color(fn ($state) => $state === 'published' ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('views_count')
                    ->label(__('article.views'))
                    ->icon('heroicon-m-eye')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label(__('article.published_at'))
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('article.status'))
                    ->options([
                        'draft' => __('article.draft'),
                        'published' => __('article.published'),
                    ]),
                Tables\Filters\SelectFilter::make('article_category_id')
                    ->label(__('article.category'))
                    ->relationship('category', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // --- View on the public site (published only) ---
                    Tables\Actions\Action::make('view_site')
                        ->label(__('article.action.view_site'))
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->color('gray')
                        ->url(fn (Article $r) => route('blog.show', $r))
                        ->openUrlInNewTab()
                        ->visible(fn (Article $r) => $r->status === 'published'),

                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-pencil-square'),

                    // --- Publish / Unpublish toggle ---
                    Tables\Actions\Action::make('toggle_publish')
                        ->label(fn (Article $r) => $r->status === 'published'
                            ? __('article.action.unpublish')
                            : __('article.action.publish'))
                        ->icon(fn (Article $r) => $r->status === 'published'
                            ? 'heroicon-m-eye-slash'
                            : 'heroicon-m-check-circle')
                        ->color(fn (Article $r) => $r->status === 'published' ? 'gray' : 'success')
                        ->requiresConfirmation()
                        ->action(function (Article $r): void {
                            $publishing = $r->status !== 'published';
                            $r->update([
                                'status' => $publishing ? 'published' : 'draft',
                                'published_at' => $publishing ? ($r->published_at ?? now()) : $r->published_at,
                            ]);
                            Notification::make()
                                ->success()
                                ->title(__('article.action.updated'))
                                ->send();
                        }),

                    // --- Duplicate ---
                    Tables\Actions\ReplicateAction::make()
                        ->label(__('article.action.duplicate'))
                        ->icon('heroicon-m-document-duplicate')
                        ->color('gray')
                        ->excludeAttributes(['slug', 'views_count', 'published_at'])
                        ->beforeReplicaSaved(function (Article $replica, Article $original): void {
                            $replica->status = 'draft';
                            $replica->slug = $original->slug.'-copy-'.Str::lower(Str::random(5));
                        })
                        ->successRedirectUrl(fn (Article $replica) => static::getUrl('edit', ['record' => $replica])),

                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-m-trash'),
                ])
                    ->label(__('article.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('article.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // bulk publish / unpublish
                    Tables\Actions\BulkAction::make('publish')
                        ->label(__('article.action.publish'))
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'published', 'published_at' => now()]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label(__('article.action.unpublish'))
                        ->icon('heroicon-m-eye-slash')
                        ->color('gray')
                        ->action(fn ($records) => $records->each->update(['status' => 'draft']))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('article.empty'))
            ->emptyStateIcon('heroicon-o-newspaper');
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
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
