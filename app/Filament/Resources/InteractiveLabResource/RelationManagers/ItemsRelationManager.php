<?php

namespace App\Filament\Resources\InteractiveLabResource\RelationManagers;

use App\Models\LabItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('lab_item.title');
    }

    private function labKey(): string
    {
        return $this->getOwnerRecord()->key;
    }

    public function form(Form $form): Form
    {
        $common = [
            Forms\Components\TextInput::make('title')
                ->label(__('lab_item.name'))->required()->columnSpanFull(),
        ];

        $specific = match ($this->labKey()) {
            'playground' => [
                Forms\Components\Textarea::make('data.html')->label('HTML')->rows(5)->extraInputAttributes(['dir' => 'ltr', 'class' => 'font-mono']),
                Forms\Components\Textarea::make('data.css')->label('CSS')->rows(5)->extraInputAttributes(['dir' => 'ltr', 'class' => 'font-mono']),
                Forms\Components\Textarea::make('data.js')->label('JavaScript')->rows(5)->extraInputAttributes(['dir' => 'ltr', 'class' => 'font-mono'])->columnSpanFull(),
            ],
            'arduino' => [
                Forms\Components\Select::make('data.type')->label(__('lab_item.arduino_type'))
                    ->options(['blink' => 'Blink', 'fade' => 'Fade (PWM)', 'traffic' => 'Traffic light', 'sos' => 'SOS'])
                    ->default('blink')->required(),
                Forms\Components\TextInput::make('data.delay')->label(__('lab_item.delay'))->numeric()->default(500),
                Forms\Components\Textarea::make('data.code')->label(__('lab_item.code'))->rows(8)->extraInputAttributes(['dir' => 'ltr', 'class' => 'font-mono'])->columnSpanFull(),
            ],
            'ai' => [
                Forms\Components\TextInput::make('data.degree')->label(__('lab_item.degree'))->numeric()->default(1)->minValue(1)->maxValue(5),
                Forms\Components\Textarea::make('data.points')->label(__('lab_item.points'))
                    ->helperText('x,y; x,y; …')->rows(4)->extraInputAttributes(['dir' => 'ltr'])->columnSpanFull(),
            ],
            'security' => [
                Forms\Components\Select::make('data.type')->label(__('lab_item.security_type'))
                    ->options([
                        'caesar' => 'Caesar', 'password' => 'Password', 'hash' => 'SHA-256',
                        'base64' => 'Base64', 'brute' => 'Brute-force',
                        'custom' => __('lab_item.custom_interaction'),
                    ])
                    ->default('caesar')->required()->live()->native(false),
                Forms\Components\Select::make('data.icon')->label(__('lab_item.icon'))
                    ->options([
                        'fa-solid fa-right-left' => 'Swap  ⇄',
                        'fa-solid fa-key' => 'Key  🔑',
                        'fa-solid fa-fingerprint' => 'Fingerprint',
                        'fa-solid fa-code' => 'Code  </>',
                        'fa-solid fa-stopwatch' => 'Stopwatch',
                        'fa-solid fa-shield-halved' => 'Shield',
                        'fa-solid fa-lock' => 'Lock',
                        'fa-solid fa-bug' => 'Bug',
                        'fa-solid fa-user-secret' => 'Hacker',
                        'fa-solid fa-wand-magic-sparkles' => 'Sparkles ✨',
                        'fa-solid fa-flask' => 'Flask',
                    ])
                    ->searchable()->native(false)
                    ->helperText(__('lab_item.icon_hint')),
                Forms\Components\TextInput::make('data.sample')->label(__('lab_item.sample'))
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->visible(fn (Forms\Get $get) => ($get('data.type') ?? 'caesar') !== 'custom')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('data.html')->label(__('lab_item.custom_html'))
                    ->helperText(__('lab_item.custom_html_hint'))
                    ->rows(10)->extraInputAttributes(['dir' => 'ltr', 'class' => 'font-mono'])
                    ->visible(fn (Forms\Get $get) => ($get('data.type') ?? 'caesar') === 'custom')
                    ->columnSpanFull(),
            ],
            'snippets' => [
                Forms\Components\Select::make('data.category')->label(__('lab_item.category'))
                    ->options(['arduino' => 'Arduino', 'ai' => 'AI', 'js' => 'JavaScript', 'security' => 'Security'])->required(),
                Forms\Components\TextInput::make('data.lang')->label(__('lab_item.lang'))->placeholder('python, cpp, javascript, bash')->required(),
                Forms\Components\Textarea::make('data.code')->label(__('lab_item.code'))->rows(8)->extraInputAttributes(['dir' => 'ltr', 'class' => 'font-mono'])->columnSpanFull(),
            ],
            default => [],
        };

        $settings = [
            Forms\Components\TextInput::make('sort_order')->label(__('lab_item.sort_order'))->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label(__('lab_item.active'))->default(true),
        ];

        return $form
            ->schema([
                Forms\Components\Section::make()->schema($common),
                Forms\Components\Section::make(__('lab_item.content'))->schema($specific)->columns(2),
                Forms\Components\Section::make(__('lab_item.settings'))->schema($settings)->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->recordClasses(fn (LabItem $r) => $r->is_active ? null : 'opacity-60')
            ->columns([
                Tables\Columns\TextColumn::make('kind')
                    ->label('')
                    ->badge()
                    ->state(fn (LabItem $r) => $this->kind($r)[0])
                    ->color(fn (LabItem $r) => $this->kind($r)[1])
                    ->icon(fn (LabItem $r) => $this->kind($r)[2]),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('lab_item.name'))
                    ->weight('semibold')
                    ->description(fn (LabItem $r) => $this->summarize($r))
                    ->searchable(),

                Tables\Columns\TextColumn::make('meta')
                    ->label(__('lab_item.summary'))
                    ->state(fn (LabItem $r) => $this->metaText($r))
                    ->badge()->color('gray')
                    ->toggleable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('lab_item.status')),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label(__('lab_item.status')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('lab_item.add'))
                    ->icon('heroicon-m-plus')
                    ->modalWidth('3xl'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('preview')
                        ->label(__('lab_item.preview'))
                        ->icon('heroicon-m-eye')
                        ->color('gray')
                        ->modalWidth('2xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel(__('lab_item.close'))
                        ->modalContent(fn (LabItem $record) => view('filament.lab-item-preview', [
                            'record' => $record,
                            'key' => $this->labKey(),
                        ])),

                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square')->modalWidth('3xl'),

                    Tables\Actions\ReplicateAction::make()
                        ->label(__('lab_item.duplicate'))
                        ->icon('heroicon-m-document-duplicate')
                        ->color('gray')
                        ->beforeReplicaSaved(fn (LabItem $replica) => $replica->is_active = false),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('lab_item.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('lab_item.actions')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('lab_item.activate'))->icon('heroicon-m-check-circle')->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('lab_item.deactivate'))->icon('heroicon-m-pause-circle')->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('lab_item.empty'))
            ->emptyStateIcon('heroicon-o-beaker')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label(__('lab_item.add'))->modalWidth('3xl'),
            ]);
    }

    /** @return array{0:string,1:string,2:string} label, color, icon */
    private function kind(LabItem $r): array
    {
        $d = $r->data ?? [];

        return match ($this->labKey()) {
            'playground' => ['Template', 'success', 'heroicon-m-code-bracket'],
            'arduino' => match ($d['type'] ?? 'blink') {
                'fade' => ['Fade', 'warning', 'heroicon-m-adjustments-vertical'],
                'traffic' => ['Traffic', 'danger', 'heroicon-m-bolt'],
                'sos' => ['SOS', 'danger', 'heroicon-m-signal'],
                default => ['Blink', 'info', 'heroicon-m-light-bulb'],
            },
            'ai' => ['Preset', 'info', 'heroicon-m-chart-bar-square'],
            'security' => match ($d['type'] ?? 'caesar') {
                'hash' => ['SHA-256', 'warning', 'heroicon-m-finger-print'],
                'base64' => ['Base64', 'gray', 'heroicon-m-code-bracket'],
                'brute' => ['Brute', 'danger', 'heroicon-m-clock'],
                'password' => ['Password', 'warning', 'heroicon-m-key'],
                'custom' => ['Custom', 'primary', 'heroicon-m-puzzle-piece'],
                default => ['Caesar', 'success', 'heroicon-m-arrows-right-left'],
            },
            'snippets' => [strtoupper($d['lang'] ?? 'code'), 'gray', 'heroicon-m-document-text'],
            default => ['—', 'gray', 'heroicon-m-cube'],
        };
    }

    private function metaText(LabItem $r): string
    {
        $d = $r->data ?? [];

        return match ($this->labKey()) {
            'arduino' => ($d['delay'] ?? 0).' ms',
            'snippets' => $d['category'] ?? '—',
            'ai' => 'degree '.($d['degree'] ?? 1),
            'security' => $d['type'] ?? '—',
            'playground' => 'HTML · CSS · JS',
            default => '',
        };
    }

    private function summarize(LabItem $r): string
    {
        $d = $r->data ?? [];

        return match ($this->labKey()) {
            'arduino', 'snippets' => Str::limit(strip_tags($d['code'] ?? ''), 50),
            'ai' => Str::limit($d['points'] ?? '', 50),
            'security' => ($d['type'] ?? '') === 'custom'
                ? Str::limit(strip_tags($d['html'] ?? ''), 50)
                : 'sample: '.($d['sample'] ?? '—'),
            'playground' => Str::limit(strip_tags($d['html'] ?? ''), 50),
            default => '',
        };
    }
}
