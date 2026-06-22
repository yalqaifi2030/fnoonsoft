<?php

namespace App\Filament\Resources;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Filament\Resources\SoftwareResource\Pages;
use App\Models\Software;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SoftwareResource extends Resource
{
    protected static ?string $model = Software::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 1;

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
        return __('nav.software');
    }

    public static function getModelLabel(): string
    {
        return __('nav.software_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.software');
    }

    /** Pending-review count badge on the nav item. */
    public static function getNavigationBadge(): ?string
    {
        return (string) (Software::where('status', ContentStatus::Pending->value)->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Tabs::make()->persistTabInQueryString()->columnSpanFull()->schema([
                Forms\Components\Tabs\Tab::make(__('software.tab.basics'))->icon('heroicon-o-cube')->schema([

                Forms\Components\Section::make(__('software.section.basics'))
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\Select::make('content_type')
                            ->label(__('software.type'))
                            ->options(ContentType::options())
                            ->default(ContentType::Application->value)
                            ->live()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label(__('software.name'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state, string $operation) {
                                if ($operation === 'create') {
                                    $set('slug', \App\Support\Slug::make($state));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('software.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->prefixIcon('heroicon-m-link'),

                        Forms\Components\Textarea::make('short_description')
                            ->label(__('software.short_description'))
                            ->rows(2)
                            ->maxLength(300)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description')
                            ->label(__('software.description'))
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('software-attachments')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make(__('software.section.details'))
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('current_version')
                            ->label(__('software.version'))->placeholder('1.0.0'),

                        Forms\Components\Select::make('license_type')
                            ->label(__('software.license'))
                            ->options([
                                'free' => __('content.license.free'),
                                'trial' => __('content.license.trial'),
                                'open_source' => __('content.license.open_source'),
                                'paid' => __('content.license.paid'),
                            ])
                            ->default('free')
                            ->live()
                            ->required(),

                        Forms\Components\TextInput::make('price')
                            ->label(__('software.price'))
                            ->numeric()->prefix('$')
                            ->visible(fn (Forms\Get $get) => $get('license_type') === 'paid'),

                        Forms\Components\CheckboxList::make('os_support')
                            ->label(__('software.os'))
                            ->options([
                                'windows' => 'Windows', 'macos' => 'macOS', 'linux' => 'Linux',
                                'android' => 'Android', 'ios' => 'iOS', 'web' => 'Web',
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => in_array($get('content_type'), ['application', 'plugin'])),

                        Forms\Components\Select::make('fileFormats')
                            ->label(__('software.file_formats'))
                            ->helperText(__('software.file_formats_hint'))
                            ->relationship('fileFormats', 'extension')
                            ->getOptionLabelFromRecordUsing(fn ($record) => '.'.strtoupper($record->extension).' — '.$record->name)
                            ->multiple()->searchable()->preload()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('meta.programming_language')
                            ->label(__('software.programming_language'))
                            ->visible(fn (Forms\Get $get) => $get('content_type') === 'script'),
                        Forms\Components\TextInput::make('meta.framework')
                            ->label(__('software.framework'))
                            ->visible(fn (Forms\Get $get) => in_array($get('content_type'), ['script', 'template', 'plugin'])),
                        Forms\Components\TextInput::make('meta.demo_url')
                            ->label(__('software.demo_url'))->url()
                            ->visible(fn (Forms\Get $get) => $get('content_type') === 'template'),
                        Forms\Components\TextInput::make('meta.platform')
                            ->label(__('software.platform'))
                            ->visible(fn (Forms\Get $get) => $get('content_type') === 'plugin'),
                    ])->columns(2),
                ]),

                Forms\Components\Tabs\Tab::make(__('software.tab.download'))->icon('heroicon-o-arrow-down-tray')->schema([
                Forms\Components\Section::make(__('software.section.downloads'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->schema([
                        Forms\Components\View::make('filament.forms.big-file-upload')
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('downloadLinks')
                            ->relationship()
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\TextInput::make('label')->label(__('software.link.label')),
                                Forms\Components\TextInput::make('note')
                                    ->label(__('software.link.note'))
                                    ->helperText(__('software.link.note_hint'))
                                    ->maxLength(60),
                                Forms\Components\Select::make('type')
                                    ->label(__('software.link.type'))
                                    ->options(['r2' => __('software.link.r2'), 'external' => __('software.link.external')])
                                    ->default('external')->live()->required(),
                                Forms\Components\TextInput::make('r2_key')
                                    ->label(__('software.link.r2_key'))
                                    ->visible(fn (Forms\Get $get) => $get('type') === 'r2'),
                                Forms\Components\TextInput::make('external_url')->label(__('software.link.url'))->url()
                                    ->visible(fn (Forms\Get $get) => $get('type') === 'external'),
                                Forms\Components\TextInput::make('size_bytes')->numeric()->label(__('software.link.size')),
                                Forms\Components\Hidden::make('original_filename'),
                                Forms\Components\Toggle::make('is_portable')->label(__('software.link.portable')),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['external_url'] ?? __('software.link.item'))
                            ->collapsed()
                            ->addActionLabel(__('software.section.downloads'))
                            ->defaultItems(0),
                    ]),
                ]),

                Forms\Components\Tabs\Tab::make(__('software.tab.media'))->icon('heroicon-o-photo')->schema([
                Forms\Components\Section::make(__('software.section.screenshots'))
                    ->icon('heroicon-o-photo')
                    ->description(__('software.screenshots_hint'))
                    ->collapsed()
                    ->schema([
                        Forms\Components\Repeater::make('screenshots')
                            ->relationship()
                            ->hiddenLabel()
                            ->orderColumn('sort_order')
                            ->schema([
                                Forms\Components\FileUpload::make('path')
                                    ->label(__('software.screenshot'))
                                    ->image()->imageEditor()
                                    ->directory('screenshots')->disk('public')
                                    ->required(),
                                Forms\Components\TextInput::make('caption')->label(__('software.caption')),
                            ])
                            ->columns(2)
                            ->grid(2)
                            ->addActionLabel(__('software.add_screenshot'))
                            ->defaultItems(0),
                    ]),

                Forms\Components\Section::make(__('software.section.before_after'))
                    ->icon('heroicon-o-arrows-right-left')
                    ->description(__('software.before_after_hint'))
                    ->collapsed()
                    ->schema([
                        Forms\Components\Repeater::make('beforeAfterSlides')
                            ->relationship()
                            ->hiddenLabel()
                            ->orderColumn('sort_order')
                            ->schema([
                                Forms\Components\ToggleButtons::make('media_type')
                                    ->label(__('software.ba.type'))
                                    ->inline()
                                    ->options(['image' => __('software.ba.image'), 'video' => __('software.ba.video')])
                                    ->icons(['image' => 'heroicon-m-photo', 'video' => 'heroicon-m-film'])
                                    ->default('image')->live()->columnSpanFull(),
                                Forms\Components\FileUpload::make('before_path')
                                    ->label(__('software.ba.before'))
                                    ->disk('public')->directory('before-after')
                                    ->acceptedFileTypes(fn (Forms\Get $get) => ($get('media_type') ?? 'image') === 'video'
                                        ? ['video/mp4', 'video/webm', 'video/ogg']
                                        : ['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(fn (Forms\Get $get) => ($get('media_type') ?? 'image') === 'video' ? 1024 * 200 : 1024 * 8)
                                    ->required(),
                                Forms\Components\FileUpload::make('after_path')
                                    ->label(__('software.ba.after'))
                                    ->disk('public')->directory('before-after')
                                    ->acceptedFileTypes(fn (Forms\Get $get) => ($get('media_type') ?? 'image') === 'video'
                                        ? ['video/mp4', 'video/webm', 'video/ogg']
                                        : ['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(fn (Forms\Get $get) => ($get('media_type') ?? 'image') === 'video' ? 1024 * 200 : 1024 * 8)
                                    ->required(),
                                Forms\Components\TextInput::make('before_label')
                                    ->label(__('software.ba.before_label'))->placeholder(__('site.before_after.before')),
                                Forms\Components\TextInput::make('after_label')
                                    ->label(__('software.ba.after_label'))->placeholder(__('site.before_after.after')),
                                Forms\Components\TextInput::make('caption')->label(__('software.caption'))->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->addActionLabel(__('software.ba.add'))
                            ->defaultItems(0),
                    ]),

                Forms\Components\Section::make(__('software.section.model'))
                    ->icon('heroicon-o-cube-transparent')
                    ->description(__('software.model_hint'))
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('model_glb')
                            ->label(__('software.model_glb'))
                            ->helperText(__('software.model_glb_hint'))
                            ->acceptedFileTypes(['model/gltf-binary', 'model/gltf+json', 'model/obj', 'text/plain', 'application/octet-stream'])
                            ->disk('public')->directory('models')
                            ->maxSize(1024 * 60) // 60 MB — keep web previews light
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('model_usdz')
                            ->label(__('software.model_usdz'))
                            ->helperText(__('software.model_usdz_hint'))
                            ->acceptedFileTypes(['model/vnd.usdz+zip', 'application/octet-stream'])
                            ->disk('public')->directory('models')
                            ->maxSize(1024 * 60),
                        Forms\Components\FileUpload::make('model_poster')
                            ->label(__('software.model_poster'))
                            ->helperText(__('software.model_poster_hint'))
                            ->image()->imageEditor()
                            ->disk('public')->directory('models')
                            ->maxSize(2048),
                    ]),

                Forms\Components\Section::make(__('software.section.features'))
                    ->icon('heroicon-o-sparkles')
                    ->description(__('software.features_hint'))
                    ->collapsed()
                    ->schema([
                        Forms\Components\Repeater::make('features')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\TextInput::make('en')->label('English')->required()->extraInputAttributes(['dir' => 'ltr']),
                                Forms\Components\TextInput::make('ar')->label('العربية')->required()->extraInputAttributes(['dir' => 'rtl']),
                            ])
                            ->columns(2)
                            ->addActionLabel(__('software.add_feature'))
                            ->reorderable()
                            ->defaultItems(0),
                    ]),

                Forms\Components\Section::make(__('software.section.video'))
                    ->icon('heroicon-o-film')
                    ->description(__('software.video_hint'))
                    ->collapsed()
                    ->schema([
                        Forms\Components\ToggleButtons::make('video_source')
                            ->label(__('software.video.source'))
                            ->inline()
                            ->options([
                                'youtube' => __('software.video.src_youtube'),
                                'external' => __('software.video.src_external'),
                                'upload' => __('software.video.src_upload'),
                            ])
                            ->icons([
                                'youtube' => 'heroicon-m-play-circle',
                                'external' => 'heroicon-m-link',
                                'upload' => 'heroicon-m-arrow-up-tray',
                            ])
                            ->colors(['youtube' => 'danger', 'external' => 'info', 'upload' => 'success'])
                            ->default('youtube')
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('video_url')
                            ->label(fn (Forms\Get $get) => ($get('video_source') ?? 'youtube') === 'youtube' ? __('software.video.url') : __('software.video.url_external'))
                            ->url()
                            ->prefixIcon('heroicon-m-link')
                            ->helperText(fn (Forms\Get $get) => ($get('video_source') ?? 'youtube') === 'youtube'
                                ? 'https://www.youtube.com/watch?v=…'
                                : 'https://…/video.mp4  ·  Vimeo  ·  any direct link')
                            ->visible(fn (Forms\Get $get) => in_array($get('video_source') ?? 'youtube', ['youtube', 'external']))
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('video_path')
                            ->label(__('software.video.file'))
                            ->disk('public')->directory('software-videos')
                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                            ->maxSize(1024 * 300) // 300 MB
                            ->helperText(__('software.video.file_hint'))
                            ->visible(fn (Forms\Get $get) => ($get('video_source') ?? 'youtube') === 'upload')
                            ->columnSpanFull(),
                    ]),
                ]),

                Forms\Components\Tabs\Tab::make(__('software.tab.advanced'))->icon('heroicon-o-adjustments-horizontal')->schema([
                Forms\Components\Section::make(__('software.section.notice'))
                    ->icon('heroicon-o-megaphone')
                    ->description(__('software.notice_hint'))
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('notice_enabled')
                            ->label(__('software.notice_enabled'))
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notice_text')
                            ->label(__('software.notice_text'))
                            ->rows(3)->maxLength(600)
                            ->visible(fn (Forms\Get $get) => $get('notice_enabled'))
                            ->columnSpanFull(),
                        Forms\Components\ToggleButtons::make('notice_type')
                            ->label(__('software.notice_type'))
                            ->inline()
                            ->options([
                                'info' => __('software.notice.type_info'),
                                'success' => __('software.notice.type_success'),
                                'warning' => __('software.notice.type_warning'),
                                'promo' => __('software.notice.type_promo'),
                            ])
                            ->icons([
                                'info' => 'heroicon-m-information-circle',
                                'success' => 'heroicon-m-check-circle',
                                'warning' => 'heroicon-m-exclamation-triangle',
                                'promo' => 'heroicon-m-megaphone',
                            ])
                            ->colors(['info' => 'info', 'success' => 'success', 'warning' => 'warning', 'promo' => 'primary'])
                            ->default('info')
                            ->visible(fn (Forms\Get $get) => $get('notice_enabled')),
                        Forms\Components\TextInput::make('notice_url')
                            ->label(__('software.notice_url'))
                            ->helperText(__('software.notice_url_hint'))
                            ->url()->prefixIcon('heroicon-m-link')->extraInputAttributes(['dir' => 'ltr'])
                            ->visible(fn (Forms\Get $get) => $get('notice_enabled')),
                    ]),

                Forms\Components\Section::make(__('software.section.code'))
                    ->icon('heroicon-o-code-bracket')
                    ->description(__('software.code_hint'))
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('code_language')
                            ->label(__('software.code.language'))
                            ->options([
                                'php' => 'PHP',
                                'javascript' => 'JavaScript',
                                'typescript' => 'TypeScript',
                                'python' => 'Python',
                                'bash' => 'Bash / Shell',
                                'html' => 'HTML',
                                'css' => 'CSS',
                                'json' => 'JSON',
                                'sql' => 'SQL',
                                'java' => 'Java',
                                'csharp' => 'C#',
                                'cpp' => 'C++',
                                'go' => 'Go',
                                'rust' => 'Rust',
                                'ruby' => 'Ruby',
                                'yaml' => 'YAML',
                            ])
                            ->searchable()
                            ->native(false)
                            ->placeholder(__('software.code.language_ph')),

                        Forms\Components\Textarea::make('code')
                            ->label(__('software.code.body'))
                            ->rows(16)
                            ->extraInputAttributes([
                                'style' => 'font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 13px; line-height: 1.6; direction: ltr; tab-size: 4;',
                                'spellcheck' => 'false',
                                'wrap' => 'off',
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(__('software.section.seo'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->description(__('software.seo_hint'))
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')->label(__('software.meta_title'))->maxLength(180),
                        Forms\Components\Textarea::make('meta_description')->label(__('software.meta_description'))->rows(2)->maxLength(300),
                    ]),
                ]),
                ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('software.section.publish'))
                    ->icon('heroicon-o-paper-airplane')
                    ->schema([
                        Forms\Components\ToggleButtons::make('status')
                            ->label(__('software.status'))
                            ->options(ContentStatus::options())
                            ->colors(collect(ContentStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->color()])->all())
                            ->icons([
                                'draft' => 'heroicon-m-pencil',
                                'pending' => 'heroicon-m-clock',
                                'published' => 'heroicon-m-check-circle',
                                'rejected' => 'heroicon-m-x-circle',
                            ])
                            ->default(ContentStatus::Draft->value)
                            ->required(),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label(__('software.published_at'))->native(false)->seconds(false)
                            ->disabled()->dehydrated(false)
                            ->helperText(__('software.published_at_hint')),

                        Forms\Components\Toggle::make('is_featured')->label(__('software.featured'))->inline(false),
                        Forms\Components\Toggle::make('is_editor_choice')->label(__('software.editor_choice'))->inline(false),
                        Forms\Components\Toggle::make('is_malware_free')->label(__('software.malware_free'))->inline(false),
                    ]),

                Forms\Components\Section::make(__('software.section.taxonomy'))
                    ->icon('heroicon-o-folder')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label(__('software.category'))
                            ->relationship('category')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                            ->searchable()->preload(),
                        Forms\Components\Select::make('developer_id')
                            ->label(__('software.developer'))
                            ->relationship('developer', 'name')
                            ->searchable()->preload(),
                        Forms\Components\Select::make('tags')
                            ->label(__('software.tags'))
                            ->relationship('tags')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                            ->multiple()->preload(),
                    ]),

                Forms\Components\Section::make(__('software.section.media'))
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Forms\Components\FileUpload::make('icon')
                            ->label(__('software.icon'))
                            ->helperText(__('software.icon_hint'))
                            ->image()->imageEditor()->imageEditorAspectRatios([null, '1:1'])
                            // Standardise new icons to a crisp 256px square (high-res for the card).
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('256')
                            ->imageResizeTargetHeight('256')
                            ->directory('icons')->disk('public'),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label('')
                    ->disk('public')
                    ->height(44)->width(44)
                    ->extraImgAttributes(['class' => 'rounded-xl object-cover']),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('software.name'))
                    ->weight('semibold')
                    ->description(fn (Software $r) => $r->developer?->name)
                    ->searchable()
                    ->limit(36),

                Tables\Columns\TextColumn::make('content_type')
                    ->label(__('software.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ContentType ? $state->label() : $state),

                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('software.category'))
                    ->badge()->color('gold')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('license_type')
                    ->label(__('software.license'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('content.license.'.$state))
                    ->color(fn ($state) => in_array($state, ['free', 'open_source']) ? 'success' : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('software.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ContentStatus ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof ContentStatus ? $state->color() : 'gray'),

                Tables\Columns\TextColumn::make('downloads_count')
                    ->label(__('software.downloads'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->numeric()->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label(__('software.featured'))
                    ->boolean()
                    ->trueIcon('heroicon-s-star')->falseIcon('heroicon-o-star')
                    ->trueColor('warning')->falseColor('gray')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('content_type')
                    ->label(__('software.type'))->options(ContentType::options()),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('software.status'))->options(ContentStatus::options()),
                Tables\Filters\SelectFilter::make('license_type')
                    ->label(__('software.license'))
                    ->options([
                        'free' => __('content.license.free'),
                        'trial' => __('content.license.trial'),
                        'open_source' => __('content.license.open_source'),
                        'paid' => __('content.license.paid'),
                    ]),
                Tables\Filters\TernaryFilter::make('is_featured')->label(__('software.featured')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_site')
                        ->label(__('software.action.view_site'))
                        ->icon('heroicon-m-arrow-top-right-on-square')->color('gray')
                        ->url(fn (Software $r) => route('software.show', $r))
                        ->openUrlInNewTab()
                        ->visible(fn (Software $r) => $r->status === ContentStatus::Published),

                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->url(fn (Software $r) => static::getUrl('view', ['record' => $r])),

                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),

                    Tables\Actions\Action::make('copy_links')
                        ->label(__('software.action.copy_links'))
                        ->icon('heroicon-m-clipboard-document-list')->color('gray')
                        ->visible(fn (Software $r) => $r->downloadLinks->isNotEmpty())
                        ->action(fn () => Notification::make()->success()->title(__('software.action.links_copied'))->send())
                        ->extraAttributes(fn (Software $r) => [
                            'x-on:click' => 'setTimeout(() => window.fnoonCopy('.\Illuminate\Support\Js::from(
                                $r->downloadLinks
                                    ->map(fn ($l) => route('download.gateway', [$r, $l]))
                                    ->implode("\n")
                            ).'), 60)',
                        ]),

                    Tables\Actions\Action::make('toggle_publish')
                        ->label(fn (Software $r) => $r->status === ContentStatus::Published ? __('software.action.unpublish') : __('software.action.publish'))
                        ->icon(fn (Software $r) => $r->status === ContentStatus::Published ? 'heroicon-m-eye-slash' : 'heroicon-m-check-circle')
                        ->color(fn (Software $r) => $r->status === ContentStatus::Published ? 'gray' : 'success')
                        ->requiresConfirmation()
                        ->action(function (Software $r): void {
                            $publishing = $r->status !== ContentStatus::Published;
                            $r->update([
                                'status' => $publishing ? ContentStatus::Published->value : ContentStatus::Draft->value,
                                'published_at' => $publishing ? ($r->published_at ?? now()) : $r->published_at,
                            ]);
                            Notification::make()->success()->title(__('software.action.updated'))->send();
                        }),

                    Tables\Actions\Action::make('toggle_feature')
                        ->label(fn (Software $r) => $r->is_featured ? __('software.action.unfeature') : __('software.action.feature'))
                        ->icon(fn (Software $r) => $r->is_featured ? 'heroicon-m-star' : 'heroicon-o-star')
                        ->color('warning')
                        ->action(function (Software $r): void {
                            $r->update(['is_featured' => ! $r->is_featured]);
                            Notification::make()->success()->title(__('software.action.updated'))->send();
                        }),

                    Tables\Actions\ReplicateAction::make()
                        ->label(__('software.action.duplicate'))
                        ->icon('heroicon-m-document-duplicate')->color('gray')
                        ->excludeAttributes(['slug', 'downloads_count', 'views_count', 'reviews_count', 'rating_avg', 'published_at'])
                        ->beforeReplicaSaved(function (Software $replica, Software $original): void {
                            $replica->status = ContentStatus::Draft->value;
                            $replica->slug = $original->slug.'-copy-'.Str::lower(Str::random(5));
                        })
                        ->successRedirectUrl(fn (Software $replica) => static::getUrl('edit', ['record' => $replica])),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('software.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('software.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')
                        ->label(__('software.action.publish'))
                        ->icon('heroicon-m-check-circle')->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => ContentStatus::Published->value, 'published_at' => now()]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label(__('software.action.unpublish'))
                        ->icon('heroicon-m-eye-slash')->color('gray')
                        ->action(fn ($records) => $records->each->update(['status' => ContentStatus::Draft->value]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('feature')
                        ->label(__('software.action.feature'))
                        ->icon('heroicon-m-star')->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_featured' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('software.empty'))
            ->emptyStateIcon('heroicon-o-cube');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSoftware::route('/'),
            'create' => Pages\CreateSoftware::route('/create'),
            'view' => Pages\ViewSoftware::route('/{record}'),
            'edit' => Pages\EditSoftware::route('/{record}/edit'),
        ];
    }
}
