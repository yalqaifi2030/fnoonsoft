<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

/**
 * Configure Google AdSense from the admin panel: master switch, publisher id,
 * Auto vs manual mode, per-placement slots, and an /ads.txt preview.
 */
class AdSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 7;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.ad-settings';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.ads.nav');
    }

    public function getTitle(): string
    {
        return __('settings.ads.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'ads_enabled' => (bool) Setting::get('ads_enabled', false),
            'ads_publisher_id' => Setting::get('ads_publisher_id'),
            'ads_mode' => Setting::get('ads_mode', 'auto'),
            'ads_hide_members' => (bool) Setting::get('ads_hide_members', true),
            'ads_slot_header' => Setting::get('ads_slot_header'),
            'ads_slot_incontent' => Setting::get('ads_slot_incontent'),
            'ads_slot_sidebar' => Setting::get('ads_slot_sidebar'),
            'ads_slot_gateway' => Setting::get('ads_slot_gateway'),
            'ads_slot_footer' => Setting::get('ads_slot_footer'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('settings.ads.section'))
                    ->description(__('settings.ads.hint'))
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Toggle::make('ads_enabled')
                            ->label(__('settings.ads.enabled'))
                            ->helperText(__('settings.ads.enabled_hint'))
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('ads_publisher_id')
                            ->label(__('settings.ads.publisher'))
                            ->helperText(__('settings.ads.publisher_hint'))
                            ->placeholder('ca-pub-0000000000000000')
                            ->extraInputAttributes(['dir' => 'ltr'])
                            ->live(onBlur: true)
                            ->visible(fn ($get) => $get('ads_enabled')),

                        Select::make('ads_mode')
                            ->label(__('settings.ads.mode'))
                            ->options([
                                'auto' => __('settings.ads.mode_auto'),
                                'manual' => __('settings.ads.mode_manual'),
                            ])
                            ->default('auto')
                            ->native(false)
                            ->live()
                            ->visible(fn ($get) => $get('ads_enabled')),

                        Toggle::make('ads_hide_members')
                            ->label(__('settings.ads.hide_members'))
                            ->helperText(__('settings.ads.hide_members_hint'))
                            ->visible(fn ($get) => $get('ads_enabled'))
                            ->columnSpanFull(),

                        Placeholder::make('ads_txt')
                            ->label('ads.txt')
                            ->content(fn ($get) => $get('ads_publisher_id')
                                ? 'google.com, '.Str::after($get('ads_publisher_id'), 'ca-').', DIRECT, f08c47fec0942fa0'
                                : '—')
                            ->helperText(__('settings.ads.ads_txt_hint'))
                            ->visible(fn ($get) => $get('ads_enabled'))
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make(__('settings.ads.slots_section'))
                    ->description(__('settings.ads.slots_hint'))
                    ->icon('heroicon-o-rectangle-group')
                    ->visible(fn ($get) => $get('ads_enabled') && $get('ads_mode') === 'manual')
                    ->schema([
                        TextInput::make('ads_slot_header')->label(__('settings.ads.slot_header'))->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('ads_slot_incontent')->label(__('settings.ads.slot_incontent'))->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('ads_slot_sidebar')->label(__('settings.ads.slot_sidebar'))->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('ads_slot_gateway')->label(__('settings.ads.slot_gateway'))->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('ads_slot_footer')->label(__('settings.ads.slot_footer'))->extraInputAttributes(['dir' => 'ltr']),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $d = $this->form->getState();

        Setting::put('ads_enabled', (bool) ($d['ads_enabled'] ?? false), 'boolean', 'ads');
        Setting::put('ads_publisher_id', $d['ads_publisher_id'] ?? '', 'string', 'ads');
        Setting::put('ads_mode', $d['ads_mode'] ?? 'auto', 'string', 'ads');
        Setting::put('ads_hide_members', (bool) ($d['ads_hide_members'] ?? true), 'boolean', 'ads');

        foreach (['header', 'incontent', 'sidebar', 'gateway', 'footer'] as $p) {
            Setting::put('ads_slot_'.$p, $d['ads_slot_'.$p] ?? '', 'string', 'ads');
        }

        Notification::make()->success()->title(__('settings.saved'))->send();
    }
}
