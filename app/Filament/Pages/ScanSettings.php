<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

/**
 * Configure malware scanning (VirusTotal by SHA-256) from the admin panel.
 * Saved values are read at scan time by App\Services\Upload\MalwareScanService.
 */
class ScanSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.scan-settings';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.scan.nav');
    }

    public function getTitle(): string
    {
        return __('settings.scan.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'scan_enabled' => (bool) Setting::get('scan_enabled', false),
            'scan_provider' => Setting::get('scan_provider', 'virustotal'),
            'scan_virustotal_key' => Setting::get('scan_virustotal_key'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('settings.scan.section'))
                    ->description(__('settings.scan.hint'))
                    ->icon('heroicon-o-bug-ant')
                    ->schema([
                        Toggle::make('scan_enabled')
                            ->label(__('settings.scan.enabled'))
                            ->helperText(__('settings.scan.enabled_hint'))
                            ->live()
                            ->columnSpanFull(),

                        Select::make('scan_provider')
                            ->label(__('settings.scan.provider'))
                            ->options([
                                'virustotal' => 'VirusTotal',
                                'clamav' => 'ClamAV',
                            ])
                            ->default('virustotal')
                            ->native(false)
                            ->visible(fn ($get) => $get('scan_enabled')),

                        TextInput::make('scan_virustotal_key')
                            ->label(__('settings.scan.vt_key'))
                            ->helperText(__('settings.scan.vt_key_hint'))
                            ->password()->revealable()
                            ->autocomplete('new-password')
                            ->extraInputAttributes(['dir' => 'ltr'])
                            ->visible(fn ($get) => $get('scan_enabled') && $get('scan_provider') === 'virustotal'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label(__('settings.scan.test'))
                ->icon('heroicon-m-beaker')
                ->color('gray')
                ->action('testKey'),
        ];
    }

    /** Validate the VirusTotal key by looking up the EICAR test file's hash. */
    public function testKey(): void
    {
        $key = $this->form->getState()['scan_virustotal_key'] ?? null;

        if (! $key) {
            Notification::make()->danger()->title(__('settings.scan.test_nokey'))->send();

            return;
        }

        try {
            $eicar = '275a021bbfb6489e54d471899f7db9d1663fc695ec2fe2a2c4538aabf651fd0f';
            $res = Http::withHeaders(['x-apikey' => $key])->timeout(20)
                ->get("https://www.virustotal.com/api/v3/files/{$eicar}");

            if ($res->status() === 401) {
                Notification::make()->danger()->title(__('settings.scan.test_badkey'))->send();

                return;
            }

            // 200 (known) or 404 (unknown) both mean the key authenticated.
            $detected = (int) ($res->json('data.attributes.last_analysis_stats.malicious', 0));
            Notification::make()->success()
                ->title(__('settings.scan.test_ok'))
                ->body(__('settings.scan.test_ok_body', ['n' => $detected]))
                ->send();
        } catch (\Throwable $e) {
            Notification::make()->danger()->title(__('settings.scan.test_fail'))->body($e->getMessage())->persistent()->send();
        }
    }

    public function save(): void
    {
        $s = $this->form->getState();

        Setting::put('scan_enabled', (bool) ($s['scan_enabled'] ?? false), 'boolean', 'scan');
        Setting::put('scan_provider', $s['scan_provider'] ?? 'virustotal', 'string', 'scan');
        Setting::putSecret('scan_virustotal_key', $s['scan_virustotal_key'] ?? '', 'scan');

        Notification::make()->success()->title(__('settings.saved'))->send();
    }
}
