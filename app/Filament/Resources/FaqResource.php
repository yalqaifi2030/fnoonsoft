<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Filament\Resources\FaqResource\RelationManagers;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.publishing');
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.faqs');
    }

    public static function getModelLabel(): string
    {
        return __('nav.faq_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.faqs');
    }

    /** @return array<string,string> group value => label */
    public static function groups(): array
    {
        return [
            'general' => __('faq.group.general'),
            'downloads' => __('faq.group.downloads'),
            'account' => __('faq.group.account'),
            'payment' => __('faq.group.payment'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('faq.section.qa'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Forms\Components\Textarea::make('question')
                            ->label(__('faq.question'))
                            ->required()
                            ->rows(2)
                            ->maxLength(300),

                        Forms\Components\RichEditor::make('answer')
                            ->label(__('faq.answer'))
                            ->required()
                            ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'h3'])
                            ->columnSpanFull(),
                    ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('faq.section.display'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Forms\Components\ToggleButtons::make('is_active')
                            ->label(__('faq.status'))
                            ->inline()
                            ->boolean(__('faq.active'), __('faq.inactive'))
                            ->colors([true => 'success', false => 'gray'])
                            ->icons([true => 'heroicon-m-check-circle', false => 'heroicon-m-pause-circle'])
                            ->default(true),

                        Forms\Components\Select::make('group')
                            ->label(__('faq.group_label'))
                            ->options(self::groups())
                            ->default('general')
                            ->required(),

                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('faq.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('faq.sort_hint')),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->groups([
                Tables\Grouping\Group::make('group')
                    ->label(__('faq.group_label'))
                    ->getTitleFromRecordUsing(fn (Faq $r) => self::groups()[$r->group] ?? $r->group),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('question')
                    ->label(__('faq.question'))
                    ->weight('semibold')
                    ->description(fn (Faq $r) => Str::limit(strip_tags($r->answer ?? ''), 70))
                    ->searchable()
                    ->wrap()
                    ->limit(70),

                Tables\Columns\TextColumn::make('group')
                    ->label(__('faq.group_label'))
                    ->badge()
                    ->color('gold')
                    ->formatStateUsing(fn ($state) => self::groups()[$state] ?? $state),

                Tables\Columns\TextColumn::make('is_active')
                    ->label(__('faq.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('faq.active') : __('faq.inactive'))
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label(__('faq.group_label'))
                    ->options(self::groups()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('faq.status'))
                    ->trueLabel(__('faq.active'))
                    ->falseLabel(__('faq.inactive')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()->icon('heroicon-m-pencil-square'),

                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (Faq $r) => $r->is_active ? __('faq.action.deactivate') : __('faq.action.activate'))
                        ->icon(fn (Faq $r) => $r->is_active ? 'heroicon-m-pause-circle' : 'heroicon-m-check-circle')
                        ->color(fn (Faq $r) => $r->is_active ? 'gray' : 'success')
                        ->requiresConfirmation()
                        ->action(function (Faq $r): void {
                            $r->update(['is_active' => ! $r->is_active]);
                            Notification::make()->success()->title(__('faq.action.updated'))->send();
                        }),

                    Tables\Actions\ReplicateAction::make()
                        ->label(__('faq.action.duplicate'))
                        ->icon('heroicon-m-document-duplicate')
                        ->color('gray')
                        ->beforeReplicaSaved(fn (Faq $replica) => $replica->is_active = false)
                        ->successRedirectUrl(fn (Faq $replica) => static::getUrl('edit', ['record' => $replica])),

                    Tables\Actions\DeleteAction::make()->icon('heroicon-m-trash'),
                ])
                    ->label(__('faq.action.menu'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip(__('faq.action.menu')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('faq.action.activate'))
                        ->icon('heroicon-m-check-circle')->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('faq.action.deactivate'))
                        ->icon('heroicon-m-pause-circle')->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('faq.empty'))
            ->emptyStateIcon('heroicon-o-question-mark-circle');
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
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
