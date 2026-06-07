<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-oval-left-ellipsis';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.engagement');
    }

    public static function getNavigationLabel(): string
    {
        return __('comment.nav');
    }

    public static function getModelLabel(): string
    {
        return __('comment.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('comment.nav');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (Comment::where('status', 'pending')->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('author_name')->label(__('comment.author'))->disabled(),
            Forms\Components\TextInput::make('author_email')->label(__('comment.email'))->disabled(),
            Forms\Components\Textarea::make('body')->label(__('comment.body'))->rows(5)->columnSpanFull(),
            Forms\Components\ToggleButtons::make('status')
                ->label(__('comment.status'))
                ->options(['pending' => __('comment.pending'), 'approved' => __('comment.approved'), 'rejected' => __('comment.rejected')])
                ->colors(['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'])
                ->icons(['pending' => 'heroicon-m-clock', 'approved' => 'heroicon-m-check-circle', 'rejected' => 'heroicon-m-x-circle'])
                ->inline(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('author_name')
                    ->label(__('comment.author'))
                    ->weight('semibold')
                    ->description(fn (Comment $r) => $r->user?->name ? '@'.$r->user->name : $r->author_email)
                    ->searchable(),

                Tables\Columns\TextColumn::make('body')
                    ->label(__('comment.body'))
                    ->formatStateUsing(fn ($state) => Str::limit($state, 70))
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('commentable.name')
                    ->label(__('comment.on'))
                    ->badge()->color('gold')
                    ->limit(24),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('comment.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('comment.'.$state))
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success', 'rejected' => 'danger', default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('created_at')->label(__('comment.when'))->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('comment.status'))
                    ->options(['pending' => __('comment.pending'), 'approved' => __('comment.approved'), 'rejected' => __('comment.rejected')]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label(__('comment.action.approve'))->icon('heroicon-m-check-circle')->color('success')
                        ->visible(fn (Comment $r) => $r->status !== 'approved')
                        ->action(function (Comment $r): void {
                            $r->update(['status' => 'approved']);
                            Notification::make()->success()->title(__('comment.action.updated'))->send();
                        }),
                    Tables\Actions\Action::make('reject')
                        ->label(__('comment.action.reject'))->icon('heroicon-m-x-circle')->color('danger')
                        ->visible(fn (Comment $r) => $r->status !== 'rejected')
                        ->requiresConfirmation()
                        ->action(function (Comment $r): void {
                            $r->update(['status' => 'rejected']);
                            Notification::make()->warning()->title(__('comment.action.updated'))->send();
                        }),
                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),
                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])->label(__('comment.actions'))->icon('heroicon-m-ellipsis-vertical')->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->label(__('comment.action.approve'))->icon('heroicon-m-check-circle')->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'approved']))->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('comment.empty'))
            ->emptyStateIcon('heroicon-o-chat-bubble-oval-left-ellipsis');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
