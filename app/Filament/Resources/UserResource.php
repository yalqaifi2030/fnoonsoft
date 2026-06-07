<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.users');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.users');
    }

    public static function getModelLabel(): string
    {
        return __('nav.user_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.users');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('user.section.account'))
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('user.name'))
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->label(__('user.email'))
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->prefixIcon('heroicon-m-envelope'),

                        Forms\Components\TextInput::make('password')
                            ->label(__('user.password'))
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation) => $operation === 'create')
                            ->helperText(fn (string $operation) => $operation === 'edit' ? __('user.password_hint') : null)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make(__('user.section.avatar'))
                    ->icon('heroicon-o-photo')
                    ->collapsed()
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label(__('user.avatar'))
                            ->image()->imageEditor()->avatar()
                            ->directory('avatars')->disk('public'),
                    ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('user.section.access'))
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label(__('user.roles'))
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => __('user.role.'.$record->name))
                            ->required(),

                        Forms\Components\ToggleButtons::make('is_active')
                            ->label(__('user.status'))
                            ->inline()
                            ->boolean(__('user.active'), __('user.inactive'))
                            ->colors([true => 'success', false => 'danger'])
                            ->icons([true => 'heroicon-m-check-circle', false => 'heroicon-m-no-symbol'])
                            ->default(true),
                    ]),

                Forms\Components\Section::make(__('user.section.preferences'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\Select::make('locale')
                            ->label(__('user.locale'))
                            ->options(['en' => 'English', 'ar' => 'العربية'])
                            ->default('en')
                            ->required(),

                        Forms\Components\TextInput::make('country')
                            ->label(__('user.country'))
                            ->maxLength(2)
                            ->placeholder('SA'),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn (User $r) => 'https://ui-avatars.com/api/?name='.urlencode($r->name).'&background=006C35&color=fff'),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('user.name'))
                    ->weight('semibold')
                    ->description(fn (User $r) => $r->email)
                    ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('user.roles'))
                    ->badge()
                    ->color('gold')
                    ->formatStateUsing(fn ($state) => __('user.role.'.$state))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('is_active')
                    ->label(__('user.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('user.active') : __('user.inactive'))
                    ->color(fn ($state) => $state ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('user.joined'))
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label(__('user.roles'))
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => __('user.role.'.$record->name))
                    ->multiple()->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('user.status'))
                    ->trueLabel(__('user.active'))->falseLabel(__('user.inactive')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // --- Primary ---
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),

                        Tables\Actions\Action::make('change_password')
                            ->label(__('user.action.change_password'))
                            ->icon('heroicon-m-key')
                            ->color('warning')
                            ->modalWidth('md')
                            ->form([
                                Forms\Components\TextInput::make('password')
                                    ->label(__('user.new_password'))
                                    ->password()->revealable()
                                    ->required()->minLength(8)->confirmed(),
                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label(__('user.confirm_password'))
                                    ->password()->revealable()
                                    ->required(),
                            ])
                            ->action(function (User $r, array $data): void {
                                $r->update(['password' => bcrypt($data['password'])]);
                                Notification::make()->success()->title(__('user.action.password_changed'))->send();
                            }),
                    ])->dropdown(false),

                    // --- Account state ---
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\Action::make('verify_email')
                            ->label(__('user.action.verify_email'))
                            ->icon('heroicon-m-check-badge')
                            ->color('success')
                            ->visible(fn (User $r) => is_null($r->email_verified_at))
                            ->requiresConfirmation()
                            ->action(function (User $r): void {
                                $r->forceFill(['email_verified_at' => now()])->save();
                                Notification::make()->success()->title(__('user.action.email_verified'))->send();
                            }),

                        Tables\Actions\Action::make('send_reset')
                            ->label(__('user.action.send_reset'))
                            ->icon('heroicon-m-envelope')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->action(function (User $r): void {
                                \Illuminate\Support\Facades\Password::sendResetLink(['email' => $r->email]);
                                Notification::make()->success()->title(__('user.action.reset_sent'))->send();
                            }),

                        Tables\Actions\Action::make('toggle_active')
                            ->label(fn (User $r) => $r->is_active ? __('user.action.deactivate') : __('user.action.activate'))
                            ->icon(fn (User $r) => $r->is_active ? 'heroicon-m-no-symbol' : 'heroicon-m-check-circle')
                            ->color(fn (User $r) => $r->is_active ? 'danger' : 'success')
                            ->requiresConfirmation()
                            ->visible(fn (User $r) => $r->id !== auth()->id())
                            ->action(function (User $r): void {
                                $r->update(['is_active' => ! $r->is_active]);
                                Notification::make()->success()->title(__('user.action.updated'))->send();
                            }),
                    ])->dropdown(false),

                    // --- Danger ---
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-m-trash')
                        ->visible(fn (User $r) => $r->id !== auth()->id()),
                ])
                    ->label(__('user.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('user.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('user.action.activate'))
                        ->icon('heroicon-m-check-circle')->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('user.action.deactivate'))
                        ->icon('heroicon-m-no-symbol')->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('user.empty'))
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
