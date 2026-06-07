<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/**
 * Configure the outgoing mail (SMTP) account from the admin panel. Saved values
 * override .env at runtime (see AppServiceProvider::applyDynamicConfig()).
 */
class EmailSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 4;

    /** Reached via the settings hub cards, not the sidebar. */
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.email-settings';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.mail.nav');
    }

    public function getTitle(): string
    {
        return __('settings.mail.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'host' => Setting::get('mail_host'),
            'port' => Setting::get('mail_port', '587'),
            'username' => Setting::get('mail_username'),
            'password' => Setting::get('mail_password'),
            'encryption' => Setting::get('mail_encryption', 'tls'),
            'from_address' => Setting::get('mail_from_address'),
            'from_name' => Setting::get('mail_from_name'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('settings.mail.section'))
                    ->description(__('settings.mail.hint'))
                    ->icon('heroicon-o-paper-airplane')
                    ->schema([
                        TextInput::make('host')->label(__('settings.mail.host'))
                            ->extraInputAttributes(['dir' => 'ltr'])->placeholder('smtp.example.com'),
                        TextInput::make('port')->label(__('settings.mail.port'))
                            ->numeric()->placeholder('587')->extraInputAttributes(['dir' => 'ltr']),

                        TextInput::make('username')->label(__('settings.mail.username'))
                            ->extraInputAttributes(['dir' => 'ltr'])->autocomplete(false),
                        TextInput::make('password')->label(__('settings.mail.password'))
                            ->password()->revealable()->extraInputAttributes(['dir' => 'ltr'])->autocomplete('new-password'),

                        Select::make('encryption')->label(__('settings.mail.encryption'))
                            ->options(['tls' => 'TLS', 'ssl' => 'SSL', '' => __('settings.mail.none')])
                            ->default('tls'),

                        TextInput::make('from_address')->label(__('settings.mail.from_address'))
                            ->email()->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('from_name')->label(__('settings.mail.from_name'))
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label(__('settings.mail.test'))
                ->icon('heroicon-m-paper-airplane')
                ->color('gray')
                ->action('sendTest'),
        ];
    }

    public function sendTest(): void
    {
        $s = $this->form->getState();
        $to = auth()->user()?->email;

        if (! $to) {
            Notification::make()->danger()->title(__('settings.mail.no_recipient'))->send();

            return;
        }

        // Apply the form values to the live mail config for this request.
        Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $s['host'] ?? null,
            'mail.mailers.smtp.port' => (int) ($s['port'] ?: 587),
            'mail.mailers.smtp.username' => $s['username'] ?: null,
            'mail.mailers.smtp.password' => $s['password'] ?: null,
            'mail.mailers.smtp.encryption' => $s['encryption'] ?: null,
            'mail.from.address' => $s['from_address'] ?: 'no-reply@'.request()->getHost(),
            'mail.from.name' => $s['from_name'] ?: config('app.name'),
        ]);

        try {
            Mail::raw(__('settings.mail.test_body'), function ($m) use ($to) {
                $m->to($to)->subject(__('settings.mail.test_subject'));
            });

            Notification::make()->success()->title(__('settings.mail.test_ok', ['email' => $to]))->send();
        } catch (\Throwable $e) {
            Notification::make()->danger()
                ->title(__('settings.mail.test_fail'))
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    public function save(): void
    {
        $s = $this->form->getState();

        Setting::put('mail_host', $s['host'] ?? '', 'string', 'mail');
        Setting::put('mail_port', $s['port'] ?? '587', 'string', 'mail');
        Setting::put('mail_username', $s['username'] ?? '', 'string', 'mail');
        Setting::putSecret('mail_password', $s['password'] ?? '', 'mail');
        Setting::put('mail_encryption', $s['encryption'] ?? '', 'string', 'mail');
        Setting::put('mail_from_address', $s['from_address'] ?? '', 'string', 'mail');
        Setting::put('mail_from_name', $s['from_name'] ?? '', 'string', 'mail');

        Notification::make()->success()->title(__('settings.saved'))->send();
    }
}
