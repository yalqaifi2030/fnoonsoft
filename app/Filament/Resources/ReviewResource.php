<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Filament\Resources\ReviewResource\RelationManagers;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.engagement');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.reviews');
    }

    public static function getModelLabel(): string
    {
        return __('nav.review_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.reviews');
    }

    /** Pending count badge on the nav item, so moderators see the queue. */
    public static function getNavigationBadge(): ?string
    {
        return (string) (Review::where('status', 'pending')->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /** @return array<string,string> */
    public static function statuses(): array
    {
        return [
            'pending' => __('review.status.pending'),
            'approved' => __('review.status.approved'),
            'rejected' => __('review.status.rejected'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('review.section.review'))
                    ->icon('heroicon-o-star')
                    ->schema([
                        Forms\Components\Select::make('software_id')
                            ->label(__('review.software'))
                            ->relationship('software')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                            ->searchable()->preload()->required(),

                        Forms\Components\Select::make('user_id')
                            ->label(__('review.user'))
                            ->relationship('user', 'name')
                            ->searchable()->preload()->required(),

                        Forms\Components\ToggleButtons::make('rating')
                            ->label(__('review.rating'))
                            ->inline()
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                            ->icons([1 => 'heroicon-m-star', 2 => 'heroicon-m-star', 3 => 'heroicon-m-star', 4 => 'heroicon-m-star', 5 => 'heroicon-m-star'])
                            ->colors([1 => 'danger', 2 => 'warning', 3 => 'warning', 4 => 'success', 5 => 'success'])
                            ->default(5)
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->label(__('review.title'))
                            ->maxLength(160),

                        Forms\Components\Textarea::make('body')
                            ->label(__('review.body'))
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('review.section.moderation'))
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\ToggleButtons::make('status')
                            ->label(__('review.status_label'))
                            ->options(self::statuses())
                            ->colors(['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'])
                            ->icons(['pending' => 'heroicon-m-clock', 'approved' => 'heroicon-m-check-circle', 'rejected' => 'heroicon-m-x-circle'])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Placeholder::make('created_info')
                            ->label(__('review.submitted'))
                            ->content(fn (?Review $record) => $record?->created_at?->diffForHumans() ?? '—'),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('software.name')
                    ->label(__('review.software'))
                    ->weight('semibold')
                    ->limit(28)
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('review.user'))
                    ->icon('heroicon-m-user')
                    ->searchable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label(__('review.rating'))
                    ->formatStateUsing(fn ($state) => str_repeat('★', (int) $state).str_repeat('☆', 5 - (int) $state))
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('review.title'))
                    ->description(fn (Review $r) => \Illuminate\Support\Str::limit($r->body ?? '', 60))
                    ->placeholder('—')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('review.status_label'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::statuses()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('review.submitted'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('review.status_label'))
                    ->options(self::statuses()),
                Tables\Filters\SelectFilter::make('rating')
                    ->label(__('review.rating'))
                    ->options([5 => '5★', 4 => '4★', 3 => '3★', 2 => '2★', 1 => '1★']),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_software')
                        ->label(__('review.action.view_software'))
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->color('gray')
                        ->url(fn (Review $r) => $r->software ? route('software.show', $r->software) : null)
                        ->openUrlInNewTab()
                        ->visible(fn (Review $r) => (bool) $r->software),

                    Tables\Actions\Action::make('approve')
                        ->label(__('review.action.approve'))
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn (Review $r) => $r->status !== 'approved')
                        ->action(function (Review $r): void {
                            $r->update(['status' => 'approved']);
                            Notification::make()->success()->title(__('review.action.approved'))->send();
                        }),

                    Tables\Actions\Action::make('reject')
                        ->label(__('review.action.reject'))
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Review $r) => $r->status !== 'rejected')
                        ->action(function (Review $r): void {
                            $r->update(['status' => 'rejected']);
                            Notification::make()->warning()->title(__('review.action.rejected'))->send();
                        }),

                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),
                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('review.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('review.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->label(__('review.action.approve'))
                        ->icon('heroicon-m-check-circle')->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'approved']))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('reject')
                        ->label(__('review.action.reject'))
                        ->icon('heroicon-m-x-circle')->color('danger')
                        ->action(fn ($records) => $records->each->update(['status' => 'rejected']))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('review.empty'))
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
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
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
