<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

/**
 * Toggle the public site into a branded "closed for maintenance" page.
 * Admin/upload panels stay reachable; signed-in staff still see the live site.
 */
class MaintenanceSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 5;

    /** Reached via the settings hub cards, not the sidebar. */
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.maintenance-settings';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.maintenance.nav');
    }

    public function getTitle(): string
    {
        return __('settings.maintenance.title');
    }

    public function mount(): void
    {
        $title = Setting::get('maintenance_title');
        $message = Setting::get('maintenance_message');

        $this->form->fill([
            'enabled' => (bool) Setting::get('maintenance_enabled'),
            'title' => is_array($title) ? $title : ['en' => '', 'ar' => ''],
            'message' => is_array($message) ? $message : ['en' => '', 'ar' => ''],
            'until' => Setting::get('maintenance_until') ?: null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('settings.maintenance.status_section'))
                    ->icon('heroicon-o-power')
                    ->schema([
                        Placeholder::make('status_display')
                            ->hiddenLabel()
                            ->content(fn (Get $get) => new HtmlString(
                                $get('enabled')
                                    ? '<div style="display:flex;align-items:center;gap:.6rem;padding:.85rem 1.1rem;border-radius:.85rem;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.25);color:#b91c1c;font-weight:800">'
                                        .'<span style="width:.6rem;height:.6rem;border-radius:50%;background:#dc2626;box-shadow:0 0 0 4px rgba(220,38,38,.2)"></span>'
                                        .e(__('settings.maintenance.is_closed')).'</div>'
                                    : '<div style="display:flex;align-items:center;gap:.6rem;padding:.85rem 1.1rem;border-radius:.85rem;background:rgba(0,108,53,.1);border:1px solid rgba(0,108,53,.25);color:#006C35;font-weight:800">'
                                        .'<span style="width:.6rem;height:.6rem;border-radius:50%;background:#16a34a;box-shadow:0 0 0 4px rgba(22,163,74,.2)"></span>'
                                        .e(__('settings.maintenance.is_open')).'</div>'
                            )),

                        Toggle::make('enabled')
                            ->label(__('settings.maintenance.enabled'))
                            ->helperText(__('settings.maintenance.enabled_hint'))
                            ->live()
                            ->columnSpanFull(),
                    ]),

                Section::make(__('settings.maintenance.section'))
                    ->description(__('settings.maintenance.hint'))
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->schema([
                        TextInput::make('title.en')->label('Title (English)')->extraInputAttributes(['dir' => 'ltr'])
                            ->placeholder('We’ll be back soon'),
                        TextInput::make('title.ar')->label('العنوان (عربي)')->extraInputAttributes(['dir' => 'rtl'])
                            ->placeholder('الموقع مغلق للصيانة'),

                        Textarea::make('message.en')->label('Message (English)')->rows(2)->extraInputAttributes(['dir' => 'ltr']),
                        Textarea::make('message.ar')->label('الرسالة (عربي)')->rows(2)->extraInputAttributes(['dir' => 'rtl']),

                        DateTimePicker::make('until')
                            ->label(__('settings.maintenance.until'))
                            ->helperText(__('settings.maintenance.until_hint'))
                            ->native(false)->seconds(false)->minDate(now())
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__('settings.maintenance.preview'))
                ->icon('heroicon-m-eye')
                ->color('gray')
                ->url(fn () => route('maintenance.preview'))
                ->openUrlInNewTab(),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::put('maintenance_enabled', ! empty($data['enabled']) ? '1' : '0', 'boolean', 'maintenance');
        Setting::put('maintenance_title', [
            'en' => $data['title']['en'] ?? '',
            'ar' => $data['title']['ar'] ?? '',
        ], 'json', 'maintenance');
        Setting::put('maintenance_message', [
            'en' => $data['message']['en'] ?? '',
            'ar' => $data['message']['ar'] ?? '',
        ], 'json', 'maintenance');
        Setting::put('maintenance_until', $data['until'] ?: '', 'string', 'maintenance');

        Notification::make()->success()->title(__('settings.saved'))->send();
    }
}
