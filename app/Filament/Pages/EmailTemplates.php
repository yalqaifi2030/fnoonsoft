<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\MailTemplate;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;

/**
 * Admin control for the branded transactional emails (verify, reset, welcome):
 * per-template subject / heading / body / button / footer, with a live preview
 * and a test send. The design is fixed (resources/views/emails/branded).
 */
class EmailTemplates extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?int $navigationSort = 5;

    /** Reached via the settings hub cards, not the sidebar. */
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.email-templates';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('mailtpl.nav');
    }

    public function getTitle(): string
    {
        return __('mailtpl.title');
    }

    public function mount(): void
    {
        $data = ['active' => 'verify'];

        foreach (MailTemplate::KEYS as $tpl) {
            foreach (MailTemplate::FIELDS as $f) {
                $data[$tpl][$f] = MailTemplate::field($tpl, $f);
            }
        }
        $data['welcome']['enabled'] = MailTemplate::enabled('welcome');

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('mailtpl.section.choose'))
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        Select::make('active')
                            ->label(__('mailtpl.choose'))
                            ->options(collect(MailTemplate::KEYS)->mapWithKeys(fn ($k) => [$k => __("mailtpl.tpl.$k")])->all())
                            ->default('verify')->live()->native(false)->selectablePlaceholder(false),
                    ]),

                $this->templateSection('verify'),
                $this->templateSection('reset'),
                $this->templateSection('welcome'),
            ])
            ->statePath('data');
    }

    private function templateSection(string $tpl): Section
    {
        $fields = [
            TextInput::make("$tpl.subject")->label(__('mailtpl.subject'))->required()->columnSpanFull(),
            TextInput::make("$tpl.heading")->label(__('mailtpl.heading'))->required()->columnSpanFull(),
            Textarea::make("$tpl.body")->label(__('mailtpl.body'))->rows(5)->columnSpanFull(),
            TextInput::make("$tpl.button_label")->label(__('mailtpl.button_label')),
            Textarea::make("$tpl.footer")->label(__('mailtpl.footer'))->rows(2)->columnSpanFull(),
        ];

        if ($tpl === 'welcome') {
            $fields[] = Toggle::make('welcome.enabled')
                ->label(__('mailtpl.welcome_enabled'))
                ->helperText(__('mailtpl.welcome_enabled_hint'));
        }

        return Section::make(__("mailtpl.tpl.$tpl"))
            ->description(__('mailtpl.placeholders').'  '.implode('  ', MailTemplate::PLACEHOLDERS[$tpl]))
            ->icon('heroicon-o-envelope')
            ->visible(fn ($get) => $get('active') === $tpl)
            ->schema($fields)
            ->columns(2);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__('mailtpl.preview'))
                ->icon('heroicon-m-eye')
                ->color('gray')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('mailtpl.close'))
                ->modalWidth('5xl')
                ->modalContent(fn () => view('filament.pages.email-template-preview', [
                    'html' => $this->renderActive(),
                ])),

            Action::make('test')
                ->label(__('mailtpl.test'))
                ->icon('heroicon-m-paper-airplane')
                ->color('gray')
                ->action('sendTest'),
        ];
    }

    /** Rendered HTML of the currently selected template, from the unsaved form. */
    private function renderActive(): string
    {
        $tpl = $this->data['active'] ?? 'verify';

        return MailTemplate::renderHtml($tpl, $this->data[$tpl] ?? MailTemplate::resolved($tpl), url('/dashboard'));
    }

    public function sendTest(): void
    {
        $to = auth()->user()?->email;
        if (! $to) {
            Notification::make()->danger()->title(__('mailtpl.no_recipient'))->send();

            return;
        }

        $tpl = $this->data['active'] ?? 'verify';
        $vd = MailTemplate::viewData($tpl, MailTemplate::sampleVars($tpl), url('/dashboard'), $this->data[$tpl] ?? null);

        try {
            Mail::html(view('emails.branded', $vd)->render(), function ($m) use ($to, $vd) {
                $m->to($to)->subject('['.__('mailtpl.test_prefix').'] '.$vd['subject']);
            });

            Notification::make()->success()->title(__('mailtpl.test_ok', ['email' => $to]))->send();
        } catch (\Throwable $e) {
            Notification::make()->danger()
                ->title(__('mailtpl.test_fail'))
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    public function save(): void
    {
        $d = $this->form->getState();

        foreach (MailTemplate::KEYS as $tpl) {
            foreach (MailTemplate::FIELDS as $f) {
                Setting::put("mail_tpl_{$tpl}_{$f}", (string) ($d[$tpl][$f] ?? ''), 'string', 'mail_tpl');
            }
        }
        Setting::put('mail_tpl_welcome_enabled', ! empty($d['welcome']['enabled']) ? '1' : '0', 'boolean', 'mail_tpl');

        Notification::make()->success()->title(__('settings.saved'))->send();
    }
}
