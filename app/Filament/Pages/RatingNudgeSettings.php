<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * A gentle, auto-dismissing toast that nudges visitors to rate the site.
 * Fully admin-editable (text, timing, optional CTA).
 */
class RatingNudgeSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.rating-nudge-settings';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public function getTitle(): string
    {
        return __('settings.nudge.title');
    }

    public function mount(): void
    {
        $title = Setting::get('rating_nudge_title');
        $message = Setting::get('rating_nudge_message');
        $cta = Setting::get('rating_nudge_cta_label');

        $this->form->fill([
            'enabled' => (bool) Setting::get('rating_nudge_enabled'),
            'title' => is_array($title) ? $title : ['ar' => 'أعجبك الموقع؟', 'en' => 'Enjoying the site?'],
            'message' => is_array($message) ? $message : ['ar' => 'قيّمنا — رأيك يسعدنا ويساعد غيرك 🌟', 'en' => 'Rate us — your feedback means a lot 🌟'],
            'cta_label' => is_array($cta) ? $cta : ['ar' => '', 'en' => ''],
            'cta_url' => Setting::text('rating_nudge_cta_url', ''),
            'delay' => (int) (Setting::get('rating_nudge_delay') ?: 8),
            'duration' => (int) (Setting::get('rating_nudge_duration') ?: 10),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('settings.nudge.status_section'))
                    ->icon('heroicon-o-power')
                    ->schema([
                        Toggle::make('enabled')
                            ->label(__('settings.nudge.enabled'))
                            ->helperText(__('settings.nudge.enabled_hint'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('settings.nudge.text_section'))
                    ->description(__('settings.nudge.text_hint'))
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->schema([
                        TextInput::make('title.ar')->label('العنوان (عربي)')->extraInputAttributes(['dir' => 'rtl']),
                        TextInput::make('title.en')->label('Title (English)')->extraInputAttributes(['dir' => 'ltr']),

                        Textarea::make('message.ar')->label('الرسالة (عربي)')->rows(2)->extraInputAttributes(['dir' => 'rtl']),
                        Textarea::make('message.en')->label('Message (English)')->rows(2)->extraInputAttributes(['dir' => 'ltr']),

                        TextInput::make('cta_label.ar')->label('زرّ الإجراء (عربي) — اختياري')->extraInputAttributes(['dir' => 'rtl']),
                        TextInput::make('cta_label.en')->label('CTA label (English) — optional')->extraInputAttributes(['dir' => 'ltr']),

                        TextInput::make('cta_url')
                            ->label(__('settings.nudge.cta_url'))
                            ->helperText(__('settings.nudge.cta_url_hint'))
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make(__('settings.nudge.timing_section'))
                    ->icon('heroicon-o-clock')
                    ->schema([
                        TextInput::make('delay')
                            ->label(__('settings.nudge.delay'))
                            ->helperText(__('settings.nudge.delay_hint'))
                            ->numeric()->minValue(0)->maxValue(120)->default(8),
                        TextInput::make('duration')
                            ->label(__('settings.nudge.duration'))
                            ->helperText(__('settings.nudge.duration_hint'))
                            ->numeric()->minValue(3)->maxValue(60)->default(10),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $d = $this->form->getState();

        Setting::put('rating_nudge_enabled', ! empty($d['enabled']) ? '1' : '0', 'boolean', 'nudge');
        Setting::put('rating_nudge_title', ['ar' => $d['title']['ar'] ?? '', 'en' => $d['title']['en'] ?? ''], 'json', 'nudge');
        Setting::put('rating_nudge_message', ['ar' => $d['message']['ar'] ?? '', 'en' => $d['message']['en'] ?? ''], 'json', 'nudge');
        Setting::put('rating_nudge_cta_label', ['ar' => $d['cta_label']['ar'] ?? '', 'en' => $d['cta_label']['en'] ?? ''], 'json', 'nudge');
        Setting::put('rating_nudge_cta_url', $d['cta_url'] ?: '', 'string', 'nudge');
        Setting::put('rating_nudge_delay', (string) ((int) ($d['delay'] ?? 8)), 'string', 'nudge');
        Setting::put('rating_nudge_duration', (string) ((int) ($d['duration'] ?? 10)), 'string', 'nudge');

        Notification::make()->success()->title(__('settings.saved'))->send();
    }
}
