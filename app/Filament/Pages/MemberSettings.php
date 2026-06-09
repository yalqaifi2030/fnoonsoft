<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Admin controls for the member dashboard (/dashboard): master switch, default
 * per-member storage quota, and an optional per-file size ceiling.
 */
class MemberSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.member-settings';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public function getTitle(): string
    {
        return __('settings.members.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'member_uploads_enabled' => (bool) Setting::get('member_uploads_enabled', false),
            'member_quota_gb' => (float) (Setting::get('member_quota_gb', 10) ?: 10),
            'member_max_file_gb' => (float) (Setting::get('member_max_file_gb', 0) ?: 0),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('settings.members.section'))
                    ->description(__('settings.members.hint'))
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->schema([
                        Toggle::make('member_uploads_enabled')
                            ->label(__('settings.members.enabled'))
                            ->helperText(__('settings.members.enabled_hint'))
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('member_quota_gb')
                            ->label(__('settings.members.quota'))
                            ->helperText(__('settings.members.quota_hint'))
                            ->numeric()->minValue(0)->step(1)->suffix('GB')
                            ->visible(fn ($get) => $get('member_uploads_enabled')),

                        TextInput::make('member_max_file_gb')
                            ->label(__('settings.members.max_file'))
                            ->helperText(__('settings.members.max_file_hint'))
                            ->numeric()->minValue(0)->step(1)->suffix('GB')
                            ->visible(fn ($get) => $get('member_uploads_enabled')),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $d = $this->form->getState();

        Setting::put('member_uploads_enabled', (bool) ($d['member_uploads_enabled'] ?? false), 'boolean', 'members');
        Setting::put('member_quota_gb', (string) ($d['member_quota_gb'] ?? 10), 'string', 'members');
        Setting::put('member_max_file_gb', (string) ($d['member_max_file_gb'] ?? 0), 'string', 'members');

        Notification::make()->success()->title(__('settings.saved'))->send();
    }
}
