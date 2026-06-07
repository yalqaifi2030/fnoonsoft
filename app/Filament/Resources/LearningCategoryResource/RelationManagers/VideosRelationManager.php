<?php

namespace App\Filament\Resources\LearningCategoryResource\RelationManagers;

use App\Models\LearningVideo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class VideosRelationManager extends RelationManager
{
    protected static string $relationship = 'videos';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('learn_admin.videos');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label(__('learn_admin.video.title'))->required()->columnSpanFull(),

            Forms\Components\Textarea::make('description')
                ->label(__('learn_admin.video.description'))->rows(2)->columnSpanFull(),

            // --- Video source: YouTube / other URL / upload ---
            Forms\Components\Section::make(__('learn_admin.video.source'))
                ->icon('heroicon-o-film')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\ToggleButtons::make('source')
                        ->label(__('learn_admin.video.source'))
                        ->inline()
                        ->options([
                            'youtube' => __('learn_admin.video.src_youtube'),
                            'external' => __('learn_admin.video.src_external'),
                            'upload' => __('learn_admin.video.src_upload'),
                        ])
                        ->icons([
                            'youtube' => 'heroicon-m-play-circle',
                            'external' => 'heroicon-m-link',
                            'upload' => 'heroicon-m-arrow-up-tray',
                        ])
                        ->colors(['youtube' => 'danger', 'external' => 'info', 'upload' => 'success'])
                        ->default('youtube')
                        ->live()
                        ->required(),

                    Forms\Components\TextInput::make('url')
                        ->label(fn (Forms\Get $get) => $get('source') === 'youtube' ? __('learn_admin.video.url') : __('learn_admin.video.url_external'))
                        ->url()
                        ->prefixIcon('heroicon-m-link')
                        ->helperText(fn (Forms\Get $get) => $get('source') === 'youtube'
                            ? 'https://www.youtube.com/watch?v=…'
                            : 'https://…/video.mp4  ·  Vimeo  ·  any direct link')
                        ->required(fn (Forms\Get $get) => in_array($get('source'), ['youtube', 'external']))
                        ->visible(fn (Forms\Get $get) => in_array($get('source'), ['youtube', 'external']))
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('file_path')
                        ->label(__('learn_admin.video.file'))
                        ->disk('public')->directory('learning-videos')
                        ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                        ->maxSize(1024 * 200) // 200 MB
                        ->helperText(__('learn_admin.video.file_hint'))
                        ->required(fn (Forms\Get $get) => $get('source') === 'upload')
                        ->visible(fn (Forms\Get $get) => $get('source') === 'upload')
                        ->columnSpanFull(),
                ]),

            Forms\Components\TextInput::make('duration')
                ->label(__('learn_admin.video.duration'))->placeholder('12:30'),

            Forms\Components\Select::make('level')
                ->label(__('learn_admin.video.level'))
                ->options([
                    'beginner' => __('learn_admin.level.beginner'),
                    'intermediate' => __('learn_admin.level.intermediate'),
                    'advanced' => __('learn_admin.level.advanced'),
                ])->default('beginner'),

            Forms\Components\TextInput::make('sort_order')->label(__('learn_admin.sort_order'))->numeric()->default(0),

            Forms\Components\Toggle::make('is_active')->label(__('learn_admin.active'))->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('thumb')
                    ->label('')
                    ->state(fn (LearningVideo $r) => $r->thumbnailUrl())
                    ->height(40)->width(64)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('learn_admin.video.title'))->weight('semibold')->searchable()->limit(40),

                Tables\Columns\TextColumn::make('source')
                    ->label(__('learn_admin.video.source'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('learn_admin.video.src_'.($state === 'youtube' ? 'youtube' : ($state === 'upload' ? 'upload' : 'external'))))
                    ->icon(fn ($state) => match ($state) {
                        'upload' => 'heroicon-m-arrow-up-tray',
                        'external' => 'heroicon-m-link',
                        default => 'heroicon-m-play-circle',
                    })
                    ->color(fn ($state) => match ($state) {
                        'upload' => 'success', 'external' => 'info', default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('level')
                    ->label(__('learn_admin.video.level'))->badge()
                    ->formatStateUsing(fn ($state) => __('learn_admin.level.'.$state))
                    ->color(fn ($state) => match ($state) {
                        'advanced' => 'danger', 'intermediate' => 'warning', default => 'success',
                    }),

                Tables\Columns\TextColumn::make('duration')->label(__('learn_admin.video.duration'))->placeholder('—'),

                Tables\Columns\IconColumn::make('is_active')->label(__('learn_admin.status'))->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label(__('learn_admin.video.add'))->icon('heroicon-m-plus')->modalWidth('2xl'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('watch')
                        ->label(__('learn_admin.video.watch'))
                        ->icon('heroicon-m-play')
                        ->color('gray')
                        ->url(fn (LearningVideo $r) => $r->isYoutube()
                            ? ($r->url ?: ('https://youtu.be/'.$r->youtubeId()))
                            : $r->videoSrc())
                        ->openUrlInNewTab()
                        ->visible(fn (LearningVideo $r) => filled($r->playerSrc())),

                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square')->modalWidth('2xl'),

                    Tables\Actions\ReplicateAction::make()
                        ->label(__('learn_admin.video.duplicate'))
                        ->icon('heroicon-m-document-duplicate')
                        ->color('gray')
                        ->excludeAttributes(['file_path'])
                        ->beforeReplicaSaved(fn (LearningVideo $replica) => $replica->is_active = false),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('learn_admin.video.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('learn_admin.video.actions')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('learn_admin.action.activate'))->icon('heroicon-m-check-circle')->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('learn_admin.action.deactivate'))->icon('heroicon-m-pause-circle')->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
