<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactBlockResource\Pages;
use App\Models\ContactBlock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Senders the contact form ignores silently (email / whole domain / IP). */
class ContactBlockResource extends Resource
{
    protected static ?string $model = ContactBlock::class;

    protected static ?string $slug = 'contact-blocks';

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';

    /** Reached from the Messages page header, not the sidebar. */
    protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return __('message.spam.block_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('message.spam.blocked_list');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\ToggleButtons::make('type')
                ->label(__('message.spam.block_what'))
                ->options([
                    'email' => __('message.spam.type.email'),
                    'domain' => __('message.spam.type.domain'),
                    'ip' => __('message.spam.type.ip'),
                ])
                ->icons(['email' => 'heroicon-m-envelope', 'domain' => 'heroicon-m-globe-alt', 'ip' => 'heroicon-m-signal'])
                ->inline()->required()->default('email')->live(),
            Forms\Components\TextInput::make('value')
                ->label(__('message.spam.value'))
                ->required()->maxLength(190)
                ->placeholder(fn (Forms\Get $get) => match ($get('type')) {
                    'domain' => 'spam-domain.com', 'ip' => '203.0.113.7', default => 'name@example.com',
                })
                ->dehydrateStateUsing(fn (?string $state) => ltrim(strtolower(trim((string) $state)), '@'))
                ->extraInputAttributes(['dir' => 'ltr']),
            Forms\Components\TextInput::make('reason')
                ->label(__('message.spam.reason_label'))
                ->maxLength(255),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label(__('message.spam.block_what'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __('message.spam.type.'.$state))
                    ->icon(fn (string $state) => match ($state) {
                        'domain' => 'heroicon-m-globe-alt', 'ip' => 'heroicon-m-signal', default => 'heroicon-m-envelope',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'domain' => 'warning', 'ip' => 'info', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label(__('message.spam.value'))
                    ->weight('semibold')
                    ->description(fn (ContactBlock $r) => $r->reason ? \Illuminate\Support\Str::limit($r->reason, 70) : null)
                    ->searchable()->copyable(),
                Tables\Columns\TextColumn::make('hits')
                    ->label(__('message.spam.hits'))
                    ->badge()->color('danger')
                    ->alignCenter()->sortable(),
                Tables\Columns\TextColumn::make('last_hit_at')
                    ->label(__('message.spam.last_hit'))
                    ->since()->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('message.received'))
                    ->since()->sortable(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label(__('message.spam.unblock'))
                    ->icon('heroicon-m-lock-open')
                    ->modalHeading(__('message.spam.unblock')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label(__('message.spam.unblock')),
            ])
            ->emptyStateHeading(__('message.spam.blocked_empty'))
            ->emptyStateIcon('heroicon-o-no-symbol');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageContactBlocks::route('/'),
        ];
    }
}
