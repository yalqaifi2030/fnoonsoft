<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Lets the admin rebrand both panels (name + logo per panel), set the favicon,
 * logo height and default theme — applied live via App\Support\Branding.
 */
class BrandingSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?int $navigationSort = 6;

    /** Reached via the settings hub cards, not the sidebar. */
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.branding-settings';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.branding.nav');
    }

    public function getTitle(): string
    {
        return __('settings.branding.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'brand_admin_name' => Setting::get('brand_admin_name', 'Fnoon Admin'),
            'brand_admin_logo' => Setting::get('brand_admin_logo'),
            'brand_upload_name' => Setting::get('brand_upload_name', 'Fnoon Upload'),
            'brand_upload_logo' => Setting::get('brand_upload_logo'),
            'brand_favicon' => Setting::get('brand_favicon'),
            'brand_logo_height' => Setting::get('brand_logo_height', '2.5rem'),
            'brand_theme' => Setting::get('brand_theme', 'system'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('settings.branding.admin_section'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        TextInput::make('brand_admin_name')
                            ->label(__('settings.branding.name'))->placeholder('Fnoon Admin'),
                        FileUpload::make('brand_admin_logo')
                            ->label(__('settings.branding.logo'))
                            ->image()->imageEditor()
                            ->disk('public')->directory('branding')
                            ->maxSize(1024),
                    ])->columns(2),

                Section::make(__('settings.branding.upload_section'))
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->schema([
                        TextInput::make('brand_upload_name')
                            ->label(__('settings.branding.name'))->placeholder('Fnoon Upload'),
                        FileUpload::make('brand_upload_logo')
                            ->label(__('settings.branding.logo'))
                            ->image()->imageEditor()
                            ->disk('public')->directory('branding')
                            ->maxSize(1024),
                    ])->columns(2),

                Section::make(__('settings.branding.general_section'))
                    ->icon('heroicon-o-swatch')
                    ->schema([
                        FileUpload::make('brand_favicon')
                            ->label(__('settings.branding.favicon'))
                            ->helperText(__('settings.branding.favicon_hint'))
                            ->image()->disk('public')->directory('branding')->maxSize(512),
                        TextInput::make('brand_logo_height')
                            ->label(__('settings.branding.logo_height'))
                            ->placeholder('2.5rem')->extraInputAttributes(['dir' => 'ltr']),
                        Select::make('brand_theme')
                            ->label(__('settings.branding.theme'))
                            ->options([
                                'system' => __('settings.branding.theme_system'),
                                'light' => __('settings.branding.theme_light'),
                                'dark' => __('settings.branding.theme_dark'),
                            ])
                            ->default('system'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $d = $this->form->getState();

        foreach ([
            'brand_admin_name', 'brand_admin_logo', 'brand_upload_name', 'brand_upload_logo',
            'brand_favicon', 'brand_logo_height', 'brand_theme',
        ] as $key) {
            Setting::put($key, $d[$key] ?? '', 'string', 'branding');
        }

        Notification::make()->success()->title(__('settings.saved'))->send();
    }
}
