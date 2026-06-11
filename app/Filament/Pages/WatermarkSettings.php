<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Admin controls for the image watermark: a tiled/corner, semi-transparent text
 * stamped on uploaded images to protect the site's content.
 */
class WatermarkSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.watermark-settings';

    protected static ?int $navigationSort = 60;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.watermark.nav');
    }

    public function getTitle(): string
    {
        return __('settings.watermark.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'watermark_enabled' => (bool) Setting::get('watermark_enabled', false),
            'watermark_text' => Setting::get('watermark_text'),
            'watermark_position' => Setting::get('watermark_position', 'tiled') ?: 'tiled',
            'watermark_opacity' => (int) (Setting::get('watermark_opacity', 25) ?: 25),
            'watermark_size' => (float) (Setting::get('watermark_size', 4) ?: 4),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('settings.watermark.section'))
                    ->description(__('settings.watermark.hint'))
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Toggle::make('watermark_enabled')
                            ->label(__('settings.watermark.enabled'))
                            ->helperText(__('settings.watermark.enabled_hint'))
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('watermark_text')
                            ->label(__('settings.watermark.text'))
                            ->helperText(__('settings.watermark.text_hint'))
                            ->placeholder(parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'finunsoft.com')
                            ->maxLength(60)
                            ->visible(fn ($get) => $get('watermark_enabled')),

                        Select::make('watermark_position')
                            ->label(__('settings.watermark.position'))
                            ->options([
                                'tiled' => __('settings.watermark.tiled'),
                                'corner' => __('settings.watermark.corner'),
                            ])
                            ->native(false)
                            ->visible(fn ($get) => $get('watermark_enabled')),

                        TextInput::make('watermark_opacity')
                            ->label(__('settings.watermark.opacity'))
                            ->helperText(__('settings.watermark.opacity_hint'))
                            ->numeric()->minValue(3)->maxValue(80)->suffix('%')
                            ->visible(fn ($get) => $get('watermark_enabled')),

                        TextInput::make('watermark_size')
                            ->label(__('settings.watermark.size'))
                            ->helperText(__('settings.watermark.size_hint'))
                            ->numeric()->minValue(2)->maxValue(12)->step(0.5)->suffix('%')
                            ->visible(fn ($get) => $get('watermark_enabled')),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $d = $this->form->getState();

        Setting::put('watermark_enabled', (bool) ($d['watermark_enabled'] ?? false), 'boolean', 'watermark');
        Setting::put('watermark_text', (string) ($d['watermark_text'] ?? ''), 'string', 'watermark');
        Setting::put('watermark_position', (string) ($d['watermark_position'] ?? 'tiled'), 'string', 'watermark');
        Setting::put('watermark_opacity', (string) ($d['watermark_opacity'] ?? 25), 'string', 'watermark');
        Setting::put('watermark_size', (string) ($d['watermark_size'] ?? 4), 'string', 'watermark');

        Notification::make()->success()->title(__('settings.saved'))->send();
    }
}
