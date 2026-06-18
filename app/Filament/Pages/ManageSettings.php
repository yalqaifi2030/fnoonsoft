<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\Software;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Central place to control all the otherwise-hardcoded public-site texts:
 * identity, hero, footer, social links and contact info. Bilingual values
 * are stored as JSON {en,ar} in the `settings` table.
 */
class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 2;

    /** Reached via the settings hub cards, not the sidebar. */
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    /** Bilingual (JSON) settings. */
    protected array $translatable = [
        'site_name', 'tagline', 'hero_title', 'hero_subtitle',
        'footer_about', 'cta_title', 'cta_text',
    ];

    /** Single-value settings. */
    protected array $plain = [
        'site_logo', 'site_favicon',
        'contact_email', 'contact_phone',
        'social_twitter', 'social_facebook', 'social_instagram',
        'social_youtube', 'social_github',
    ];

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.nav');
    }

    public function getTitle(): string
    {
        return __('settings.title');
    }

    public function mount(): void
    {
        $data = [];
        foreach ($this->translatable as $key) {
            $val = Setting::get($key);
            $data[$key] = is_array($val) ? $val : ['en' => '', 'ar' => ''];
        }
        foreach ($this->plain as $key) {
            $data[$key] = Setting::get($key);
        }

        // Editor's-choice spotlight (mixed types).
        $data['spotlight_enabled'] = (bool) Setting::get('spotlight_enabled', true);
        $data['spotlight_software_id'] = Setting::get('spotlight_software_id');
        $data['spotlight_bg'] = Setting::get('spotlight_bg');
        $data['spotlight_overlay'] = Setting::get('spotlight_overlay', 'medium');
        $data['spotlight_badge'] = Setting::get('spotlight_badge');

        // Engagement / moderation.
        $data['comments_auto_approve'] = (bool) Setting::get('comments_auto_approve', false);

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()->tabs([
                    Tabs\Tab::make(__('settings.tab.identity'))
                        ->icon('heroicon-o-identification')
                        ->schema([
                            $this->bilingual('site_name', __('settings.site_name')),
                            $this->bilingual('tagline', __('settings.tagline')),
                        ]),

                    Tabs\Tab::make(__('settings.tab.branding'))
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Section::make(__('settings.branding_section'))
                                ->description(__('settings.branding_hint'))
                                ->icon('heroicon-o-sparkles')
                                ->columns(2)
                                ->schema([
                                    FileUpload::make('site_logo')
                                        ->label(__('settings.site_logo'))
                                        ->helperText(__('settings.site_logo_hint'))
                                        ->image()
                                        ->imageEditor()
                                        ->disk('public')->directory('site')
                                        ->maxSize(1024)
                                        ->panelLayout('compact')
                                        ->imagePreviewHeight('80'),
                                    FileUpload::make('site_favicon')
                                        ->label(__('settings.site_favicon'))
                                        ->helperText(__('settings.site_favicon_hint'))
                                        ->acceptedFileTypes([
                                            // SVG excluded — a same-origin SVG can carry <script> (stored XSS).
                                            'image/png', 'image/x-icon', 'image/vnd.microsoft.icon',
                                        ])
                                        ->disk('public')->directory('site')
                                        ->maxSize(512)
                                        ->panelLayout('compact'),
                                ]),
                        ]),

                    Tabs\Tab::make(__('settings.tab.spotlight'))
                        ->icon('heroicon-o-star')
                        ->schema([
                            Section::make(__('settings.spotlight.section'))
                                ->description(__('settings.spotlight.hint'))
                                ->icon('heroicon-o-trophy')
                                ->columns(2)
                                ->schema([
                                    Toggle::make('spotlight_enabled')
                                        ->label(__('settings.spotlight.enabled'))
                                        ->helperText(__('settings.spotlight.enabled_hint'))
                                        ->default(true)
                                        ->columnSpanFull(),
                                    Select::make('spotlight_software_id')
                                        ->label(__('settings.spotlight.software'))
                                        ->helperText(__('settings.spotlight.software_hint'))
                                        ->placeholder(__('settings.spotlight.software_auto'))
                                        ->options(fn () => Software::query()->published()
                                            ->orderByDesc('downloads_count')->get()->pluck('name', 'id'))
                                        ->searchable()
                                        ->native(false),
                                    Select::make('spotlight_overlay')
                                        ->label(__('settings.spotlight.overlay'))
                                        ->helperText(__('settings.spotlight.overlay_hint'))
                                        ->options([
                                            'soft' => __('settings.spotlight.overlay_soft'),
                                            'medium' => __('settings.spotlight.overlay_medium'),
                                            'strong' => __('settings.spotlight.overlay_strong'),
                                        ])
                                        ->default('medium')
                                        ->selectablePlaceholder(false)
                                        ->native(false),
                                    FileUpload::make('spotlight_bg')
                                        ->label(__('settings.spotlight.bg'))
                                        ->helperText(__('settings.spotlight.bg_hint'))
                                        ->image()
                                        ->imageEditor()
                                        ->disk('public')->directory('spotlight')
                                        ->maxSize(3072)
                                        ->columnSpanFull(),
                                    TextInput::make('spotlight_badge')
                                        ->label(__('settings.spotlight.badge'))
                                        ->helperText(__('settings.spotlight.badge_hint'))
                                        ->placeholder(__('site.sections.editor_choice'))
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tabs\Tab::make(__('settings.tab.hero'))
                        ->icon('heroicon-o-megaphone')
                        ->schema([
                            $this->bilingual('hero_title', __('settings.hero_title')),
                            $this->bilingual('hero_subtitle', __('settings.hero_subtitle'), textarea: true),
                        ]),

                    Tabs\Tab::make(__('settings.tab.cta'))
                        ->icon('heroicon-o-rocket-launch')
                        ->schema([
                            $this->bilingual('cta_title', __('settings.cta_title')),
                            $this->bilingual('cta_text', __('settings.cta_text'), textarea: true),
                        ]),

                    Tabs\Tab::make(__('settings.tab.footer'))
                        ->icon('heroicon-o-bars-3-bottom-left')
                        ->schema([
                            $this->bilingual('footer_about', __('settings.footer_about'), textarea: true),
                        ]),

                    Tabs\Tab::make(__('settings.tab.comments'))
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->schema([
                            Section::make(__('settings.comments.section'))
                                ->description(__('settings.comments.hint'))
                                ->icon('heroicon-o-shield-check')
                                ->schema([
                                    Toggle::make('comments_auto_approve')
                                        ->label(__('settings.comments.auto_approve'))
                                        ->helperText(__('settings.comments.auto_approve_hint')),
                                ]),
                        ]),

                    Tabs\Tab::make(__('settings.tab.social'))
                        ->icon('heroicon-o-share')
                        ->schema([
                            Section::make()->columns(2)->schema([
                                TextInput::make('social_twitter')->label('X / Twitter')->url()->prefixIcon('heroicon-m-link'),
                                TextInput::make('social_facebook')->label('Facebook')->url()->prefixIcon('heroicon-m-link'),
                                TextInput::make('social_instagram')->label('Instagram')->url()->prefixIcon('heroicon-m-link'),
                                TextInput::make('social_youtube')->label('YouTube')->url()->prefixIcon('heroicon-m-link'),
                                TextInput::make('social_github')->label('GitHub')->url()->prefixIcon('heroicon-m-link'),
                            ]),
                        ]),

                    Tabs\Tab::make(__('settings.tab.contact'))
                        ->icon('heroicon-o-envelope')
                        ->schema([
                            Section::make()->columns(2)->schema([
                                TextInput::make('contact_email')->label(__('settings.contact_email'))->email()->prefixIcon('heroicon-m-envelope'),
                                TextInput::make('contact_phone')->label(__('settings.contact_phone'))->prefixIcon('heroicon-m-phone'),
                            ]),
                        ]),
                ])->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    private function bilingual(string $key, string $label, bool $textarea = false): Section
    {
        $en = $textarea ? Textarea::make("{$key}.en")->rows(2) : TextInput::make("{$key}.en");
        $ar = $textarea ? Textarea::make("{$key}.ar")->rows(2) : TextInput::make("{$key}.ar");

        return Section::make($label)->columns(2)->schema([
            $en->label('English')->extraInputAttributes(['dir' => 'ltr']),
            $ar->label('العربية')->extraInputAttributes(['dir' => 'rtl']),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($this->translatable as $key) {
            Setting::put($key, [
                'en' => $data[$key]['en'] ?? '',
                'ar' => $data[$key]['ar'] ?? '',
            ], 'json', 'site');
        }
        foreach ($this->plain as $key) {
            Setting::put($key, $data[$key] ?? '', 'string', 'site');
        }

        // Editor's-choice spotlight.
        Setting::put('spotlight_enabled', (bool) ($data['spotlight_enabled'] ?? true), 'boolean', 'spotlight');
        Setting::put('spotlight_software_id', $data['spotlight_software_id'] ?? '', 'string', 'spotlight');
        Setting::put('spotlight_bg', $data['spotlight_bg'] ?? '', 'string', 'spotlight');
        Setting::put('spotlight_overlay', $data['spotlight_overlay'] ?? 'medium', 'string', 'spotlight');
        Setting::put('spotlight_badge', $data['spotlight_badge'] ?? '', 'string', 'spotlight');

        Setting::put('comments_auto_approve', (bool) ($data['comments_auto_approve'] ?? false), 'boolean', 'engagement');

        Notification::make()->success()->title(__('settings.saved'))->send();
    }
}
