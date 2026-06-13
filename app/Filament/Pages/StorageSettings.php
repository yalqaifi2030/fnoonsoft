<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Aws\S3\S3Client;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Configure the S3-compatible object storage used by the upload engine —
 * Cloudflare R2, Amazon S3, or iDrive e2 — from the admin panel. Saved values
 * override .env at runtime (see AppServiceProvider::applyDynamicConfig()).
 */
class StorageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?int $navigationSort = 3;

    /** Reached via the settings hub cards, not the sidebar. */
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.storage-settings';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.storage.nav');
    }

    public function getTitle(): string
    {
        return __('settings.storage.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'provider' => Setting::get('storage_provider', 'r2'),
            'key' => Setting::get('storage_key'),
            'secret' => Setting::get('storage_secret'),
            'bucket' => Setting::get('storage_bucket'),
            'region' => Setting::get('storage_region', 'auto'),
            'endpoint' => Setting::get('storage_endpoint'),
            'path_style' => (bool) Setting::get('storage_path_style', true),
            'public_url' => Setting::get('storage_public_url'),
            'proxy' => (bool) Setting::get('storage_proxy', false),
            'brand_downloads' => (bool) Setting::get('brand_downloads', true),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('settings.storage.section'))
                    ->description(__('settings.storage.hint'))
                    ->icon('heroicon-o-server-stack')
                    ->schema([
                        Select::make('provider')
                            ->label(__('settings.storage.provider'))
                            ->options([
                                'r2' => 'Cloudflare R2',
                                'aws' => 'Amazon S3',
                                'idrive' => 'iDrive e2 (idrive.com/s3)',
                                'custom' => __('settings.storage.custom'),
                            ])
                            ->default('r2')->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($state === 'r2') {
                                    $set('region', 'auto');
                                    $set('path_style', true);
                                } elseif ($state === 'aws') {
                                    $set('region', 'us-east-1');
                                    $set('path_style', false);
                                    $set('endpoint', null);
                                } elseif ($state === 'idrive') {
                                    $set('path_style', true);
                                    if (blank($get('region'))) {
                                        $set('region', 'us-east-1');
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('key')->label(__('settings.storage.key'))
                            ->extraInputAttributes(['dir' => 'ltr'])->autocomplete(false),
                        TextInput::make('secret')->label(__('settings.storage.secret'))
                            ->password()->revealable()->extraInputAttributes(['dir' => 'ltr'])->autocomplete('new-password'),

                        TextInput::make('bucket')->label(__('settings.storage.bucket'))
                            ->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('region')->label(__('settings.storage.region'))
                            ->extraInputAttributes(['dir' => 'ltr'])->placeholder('auto'),

                        TextInput::make('endpoint')->label(__('settings.storage.endpoint'))
                            ->url()->extraInputAttributes(['dir' => 'ltr'])
                            ->helperText(fn (Get $get) => match ($get('provider')) {
                                'r2' => 'https://<account>.r2.cloudflarestorage.com',
                                'idrive' => 'https://<region>.idrivee2-xx.com',
                                'aws' => __('settings.storage.endpoint_aws'),
                                default => 'https://…',
                            })
                            ->visible(fn (Get $get) => $get('provider') !== 'aws')
                            ->columnSpanFull(),

                        TextInput::make('public_url')->label(__('settings.storage.public_url'))
                            ->url()->extraInputAttributes(['dir' => 'ltr'])
                            ->helperText(__('settings.storage.public_url_hint'))
                            ->columnSpanFull(),

                        Toggle::make('path_style')->label(__('settings.storage.path_style'))
                            ->helperText(__('settings.storage.path_style_hint'))
                            ->default(true),

                        Toggle::make('proxy')->label(__('settings.storage.proxy'))
                            ->helperText(__('settings.storage.proxy_hint'))
                            ->columnSpanFull(),

                        Toggle::make('brand_downloads')->label(__('settings.storage.brand'))
                            ->helperText(__('settings.storage.brand_hint'))
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label(__('settings.storage.test'))
                ->icon('heroicon-m-signal')
                ->color('gray')
                ->action('testConnection'),

            Action::make('cors')
                ->label(__('settings.storage.apply_cors'))
                ->icon('heroicon-m-globe-alt')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('settings.storage.apply_cors_desc'))
                ->action('applyCors'),
        ];
    }

    /** Build an S3 client from the current (unsaved) form state. */
    private function makeClient(array $s): S3Client
    {
        return new S3Client(array_filter([
            'version' => 'latest',
            'region' => $s['region'] ?: 'auto',
            'endpoint' => $s['endpoint'] ?: null,
            'use_path_style_endpoint' => (bool) ($s['path_style'] ?? true),
            'credentials' => ['key' => $s['key'], 'secret' => $s['secret']],
        ], fn ($v) => $v !== null));
    }

    public function testConnection(): void
    {
        $s = $this->form->getState();

        try {
            $this->makeClient($s)->headBucket(['Bucket' => $s['bucket']]);
            Notification::make()->success()->title(__('settings.storage.test_ok'))->send();
        } catch (\Throwable $e) {
            Notification::make()->danger()
                ->title(__('settings.storage.test_fail'))
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    /** Set a browser-upload-friendly CORS policy on the bucket automatically. */
    public function applyCors(): void
    {
        $s = $this->form->getState();
        $host = request()->getHost();
        $origins = array_values(array_unique(array_filter([
            rtrim((string) config('app.url'), '/'),
            'https://'.$host,
            'http://'.$host,
        ])));

        try {
            $this->makeClient($s)->putBucketCors([
                'Bucket' => $s['bucket'],
                'CORSConfiguration' => [
                    'CORSRules' => [[
                        'AllowedOrigins' => $origins,
                        'AllowedMethods' => ['GET', 'PUT', 'POST', 'HEAD'],
                        'AllowedHeaders' => ['*'],
                        'ExposeHeaders' => ['ETag'],
                        'MaxAgeSeconds' => 3600,
                    ]],
                ],
            ]);

            Notification::make()->success()
                ->title(__('settings.storage.cors_ok'))
                ->body(implode(', ', $origins))
                ->send();
        } catch (\Throwable $e) {
            Notification::make()->danger()
                ->title(__('settings.storage.cors_fail'))
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    public function save(): void
    {
        $s = $this->form->getState();

        Setting::put('storage_provider', $s['provider'] ?? 'r2', 'string', 'storage');
        Setting::put('storage_key', $s['key'] ?? '', 'string', 'storage');
        Setting::putSecret('storage_secret', $s['secret'] ?? '', 'storage');
        Setting::put('storage_bucket', $s['bucket'] ?? '', 'string', 'storage');
        Setting::put('storage_region', $s['region'] ?? 'auto', 'string', 'storage');
        Setting::put('storage_endpoint', $s['endpoint'] ?? '', 'string', 'storage');
        Setting::put('storage_path_style', $s['path_style'] ? '1' : '0', 'boolean', 'storage');
        Setting::put('storage_public_url', $s['public_url'] ?? '', 'string', 'storage');
        Setting::put('storage_proxy', ! empty($s['proxy']) ? '1' : '0', 'boolean', 'storage');
        Setting::put('brand_downloads', ! empty($s['brand_downloads']) ? '1' : '0', 'boolean', 'storage');

        Notification::make()->success()->title(__('settings.saved'))->send();
    }
}
