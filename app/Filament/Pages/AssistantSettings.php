<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\AssistantService;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Control panel for the public "AI Assistant" (المساعد الذكي): enable/disable,
 * the encrypted Claude API key, model, persona, welcome + suggestions, and a
 * per-visitor daily cap — plus a LIVE preview chat so the admin can test the
 * assistant against the real catalog before publishing it to visitors.
 */
class AssistantSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.assistant-settings';

    public ?array $data = [];

    /** Live-preview conversation state. */
    public array $testMessages = [];

    public string $testInput = '';

    public bool $testBusy = false;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('assistant.nav');
    }

    public function getTitle(): string
    {
        return __('assistant.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'assistant_enabled' => (bool) Setting::get('assistant_enabled', false),
            'assistant_api_key' => (string) Setting::get('assistant_api_key', ''),
            'assistant_model' => (string) Setting::get('assistant_model', 'claude-haiku-4-5'),
            'assistant_persona' => (string) Setting::get('assistant_persona', ''),
            'assistant_welcome' => (string) Setting::get('assistant_welcome', ''),
            'assistant_suggestions' => (string) Setting::get('assistant_suggestions', ''),
            'assistant_daily_limit' => (int) Setting::get('assistant_daily_limit', 30),
            'assistant_max_recs' => (int) Setting::get('assistant_max_recs', 6),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('assistant.section.connection'))
                    ->description(__('assistant.section.connection_hint'))
                    ->icon('heroicon-o-bolt')
                    ->schema([
                        Toggle::make('assistant_enabled')
                            ->label(__('assistant.enabled'))
                            ->helperText(__('assistant.enabled_hint'))
                            ->columnSpanFull(),

                        TextInput::make('assistant_api_key')
                            ->label(__('assistant.api_key'))
                            ->helperText(__('assistant.api_key_hint'))
                            ->password()->revealable()
                            ->extraInputAttributes(['dir' => 'ltr'])
                            ->autocomplete('new-password')
                            ->columnSpanFull(),

                        Select::make('assistant_model')
                            ->label(__('assistant.model'))
                            ->helperText(__('assistant.model_hint'))
                            ->options([
                                'claude-haiku-4-5' => __('assistant.model_haiku'),
                                'claude-sonnet-4-6' => __('assistant.model_sonnet'),
                                'claude-opus-4-8' => __('assistant.model_opus'),
                            ])
                            ->default('claude-haiku-4-5')
                            ->native(false)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make(__('assistant.section.behaviour'))
                    ->description(__('assistant.section.behaviour_hint'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Textarea::make('assistant_persona')
                            ->label(__('assistant.persona'))
                            ->helperText(__('assistant.persona_hint'))
                            ->placeholder(__('assistant.persona_ph'))
                            ->rows(3)->columnSpanFull(),

                        Textarea::make('assistant_welcome')
                            ->label(__('assistant.welcome'))
                            ->helperText(__('assistant.welcome_hint'))
                            ->placeholder(__('assistant.welcome_ph'))
                            ->rows(2)->columnSpanFull(),

                        Textarea::make('assistant_suggestions')
                            ->label(__('assistant.suggestions'))
                            ->helperText(__('assistant.suggestions_hint'))
                            ->placeholder(__('assistant.suggestions_ph'))
                            ->rows(4)->columnSpanFull(),
                    ]),

                Section::make(__('assistant.section.limits'))
                    ->description(__('assistant.section.limits_hint'))
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextInput::make('assistant_daily_limit')
                            ->label(__('assistant.daily_limit'))
                            ->helperText(__('assistant.daily_limit_hint'))
                            ->numeric()->minValue(0)->step(1)->suffix(__('assistant.per_day')),

                        TextInput::make('assistant_max_recs')
                            ->label(__('assistant.max_recs'))
                            ->helperText(__('assistant.max_recs_hint'))
                            ->numeric()->minValue(1)->maxValue(12)->step(1),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshCatalog')
                ->label(__('assistant.refresh_catalog'))
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(function () {
                    AssistantService::forgetCatalog();
                    Notification::make()->success()->title(__('assistant.catalog_refreshed'))->send();
                }),

            Action::make('clearTest')
                ->label(__('assistant.clear_test'))
                ->icon('heroicon-m-trash')
                ->color('gray')
                ->action('clearTest'),
        ];
    }

    /** Number of published items the assistant can recommend from. */
    public function getCatalogCount(): int
    {
        return count(AssistantService::fromSettings()->catalog()['items']);
    }

    // --- Live preview ----------------------------------------------------

    public function sendTest(): void
    {
        $text = trim($this->testInput);
        if ($text === '' || $this->testBusy) {
            return;
        }

        $state = $this->form->getState();

        if (blank($state['assistant_api_key'] ?? '')) {
            Notification::make()->warning()->title(__('assistant.need_key'))->send();

            return;
        }

        $this->testMessages[] = ['role' => 'user', 'content' => $text];
        $this->testInput = '';

        $history = collect($this->testMessages)
            ->map(fn ($m) => ['role' => $m['role'], 'content' => $m['content']])
            ->all();

        $result = AssistantService::fromConfig([
            'api_key' => $state['assistant_api_key'] ?? '',
            'model' => $state['assistant_model'] ?? 'claude-haiku-4-5',
            'persona' => $state['assistant_persona'] ?? '',
            'max_recs' => $state['assistant_max_recs'] ?? 6,
        ])->reply($history);

        if (! empty($result['error'])) {
            $this->testMessages[] = [
                'role' => 'assistant',
                'content' => __('assistant.error').' ('.$result['error'].')',
                'recommendations' => [],
            ];

            return;
        }

        $this->testMessages[] = [
            'role' => 'assistant',
            'content' => $result['reply'],
            'recommendations' => $result['recommendations'],
        ];
    }

    public function clearTest(): void
    {
        $this->testMessages = [];
        $this->testInput = '';
    }

    public function save(): void
    {
        $d = $this->form->getState();

        Setting::put('assistant_enabled', ! empty($d['assistant_enabled']) ? '1' : '0', 'boolean', 'assistant');
        Setting::putSecret('assistant_api_key', $d['assistant_api_key'] ?? '', 'assistant');
        Setting::put('assistant_model', $d['assistant_model'] ?? 'claude-haiku-4-5', 'string', 'assistant');
        Setting::put('assistant_persona', $d['assistant_persona'] ?? '', 'string', 'assistant');
        Setting::put('assistant_welcome', $d['assistant_welcome'] ?? '', 'string', 'assistant');
        Setting::put('assistant_suggestions', $d['assistant_suggestions'] ?? '', 'string', 'assistant');
        Setting::put('assistant_daily_limit', (string) (int) ($d['assistant_daily_limit'] ?? 30), 'string', 'assistant');
        Setting::put('assistant_max_recs', (string) (int) ($d['assistant_max_recs'] ?? 6), 'string', 'assistant');

        Notification::make()->success()->title(__('settings.saved'))->send();
    }
}
