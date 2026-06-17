<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Theme;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Brand-colour control for the whole product. The admin sets three colours
 * (primary, secondary, accent) — applied live to the public site and (optionally)
 * the Filament panels via App\Support\Theme. Includes one-click presets and a
 * live preview so nothing changes for visitors until it's saved.
 */
class ThemeSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?int $navigationSort = 7;

    /** Reached via the settings hub cards, not the sidebar. */
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.theme-settings';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('theme.nav');
    }

    public function getTitle(): string
    {
        return __('theme.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'theme_primary' => Theme::primary(),
            'theme_secondary' => Theme::secondary(),
            'theme_accent' => Theme::accent(),
            'theme_apply_panel' => Theme::appliesToPanel(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('theme.section.colors'))
                    ->description(__('theme.section.colors_hint'))
                    ->icon('heroicon-o-paint-brush')
                    ->schema([
                        ColorPicker::make('theme_primary')
                            ->label(__('theme.primary'))
                            ->helperText(__('theme.primary_hint'))
                            ->required()->live(onBlur: true),
                        ColorPicker::make('theme_secondary')
                            ->label(__('theme.secondary'))
                            ->helperText(__('theme.secondary_hint'))
                            ->required()->live(onBlur: true),
                        ColorPicker::make('theme_accent')
                            ->label(__('theme.accent'))
                            ->helperText(__('theme.accent_hint'))
                            ->required()->live(onBlur: true),
                    ])->columns(3),

                Section::make(__('theme.section.scope'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Toggle::make('theme_apply_panel')
                            ->label(__('theme.apply_panel'))
                            ->helperText(__('theme.apply_panel_hint'))
                            ->live(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')
                ->label(__('theme.reset'))
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('theme.reset_confirm'))
                ->action(function () {
                    $this->data['theme_primary'] = Theme::DEFAULTS['primary'];
                    $this->data['theme_secondary'] = Theme::DEFAULTS['secondary'];
                    $this->data['theme_accent'] = Theme::DEFAULTS['accent'];
                }),
        ];
    }

    /** Apply a curated preset to the form (not saved until the admin hits Save). */
    public function applyPreset(string $key): void
    {
        $preset = Theme::PRESETS[$key] ?? null;
        if (! $preset) {
            return;
        }

        $this->data['theme_primary'] = $preset[0];
        $this->data['theme_secondary'] = $preset[1];
        $this->data['theme_accent'] = $preset[2];
    }

    /** @return array<string,array{0:string,1:string,2:string}> */
    public function getPresets(): array
    {
        return Theme::PRESETS;
    }

    public function save(): void
    {
        $d = $this->form->getState();

        foreach (['theme_primary', 'theme_secondary', 'theme_accent'] as $key) {
            $hex = $d[$key] ?? '';
            if (! Theme::valid($hex)) {
                Notification::make()->danger()->title(__('theme.invalid'))->send();

                return;
            }
            Setting::put($key, $hex, 'string', 'theme');
        }

        Setting::put('theme_apply_panel', ! empty($d['theme_apply_panel']) ? '1' : '0', 'boolean', 'theme');

        Notification::make()->success()->title(__('theme.saved'))->send();
    }
}
