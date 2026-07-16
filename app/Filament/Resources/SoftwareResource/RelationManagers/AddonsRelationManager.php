<?php

namespace App\Filament\Resources\SoftwareResource\RelationManagers;

use App\Filament\Resources\SoftwareResource;
use App\Models\Software;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The addons/plugins that target this program. Addons are full content items of
 * their own (own page, download links, ratings) — this panel just links them to
 * their host, so nothing about the upload/download engine is duplicated.
 */
class AddonsRelationManager extends RelationManager
{
    protected static string $relationship = 'addons';

    protected static ?string $icon = 'heroicon-o-puzzle-piece';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('software.section.addons');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->addons()->count();

        return $count ?: null;
    }

    /** Standalone items that can still be attached (not already an addon, not self). */
    private function attachableOptions(): array
    {
        return Software::query()
            ->whereNull('addon_for_id')
            ->whereKeyNot($this->getOwnerRecord()->getKey())
            ->limit(500)->get()
            ->sortBy(fn (Software $s) => (string) $s->name)
            ->mapWithKeys(fn (Software $s) => [$s->id => $s->name.' — '.$s->content_type->label()])
            ->all();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('slug')
            ->defaultSort('downloads_count', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label('')
                    ->disk('public')
                    ->square()->size(38),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('software.name'))
                    ->weight('bold')->searchable()->wrap(),

                Tables\Columns\TextColumn::make('content_type')
                    ->label(__('software.type'))
                    ->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\ContentType ? $state->label() : $state),

                Tables\Columns\TextColumn::make('current_version')
                    ->label(__('software.version'))
                    ->badge()->color('info')->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('software.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\ContentStatus ? $state->label() : $state)
                    ->color(fn ($state) => ($state instanceof \App\Enums\ContentStatus && $state->value === 'published') ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('downloads_count')
                    ->label(__('software.downloads'))
                    ->badge()->color('gray')->sortable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('attach')
                    ->label(__('software.addon_attach'))
                    ->icon('heroicon-m-link')->color('primary')
                    ->form(fn () => [
                        Forms\Components\Select::make('software_id')
                            ->label(__('software.addon_attach_pick'))
                            ->helperText(__('software.addon_attach_hint'))
                            ->options($this->attachableOptions())
                            ->searchable()->required(),
                    ])
                    ->action(function (array $data): void {
                        Software::whereKey($data['software_id'])
                            ->update(['addon_for_id' => $this->getOwnerRecord()->getKey()]);

                        Notification::make()->success()->title(__('software.addon_attached'))->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label(__('software.addon_open'))
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Software $record) => SoftwareResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('detach')
                    ->label(__('software.addon_detach'))
                    ->icon('heroicon-m-link-slash')->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(__('software.addon_detach_hint'))
                    ->action(function (Software $record): void {
                        $record->update(['addon_for_id' => null]);
                        Notification::make()->success()->title(__('software.addon_detached'))->send();
                    }),
            ])
            ->emptyStateHeading(__('software.addon_empty'))
            ->emptyStateDescription(__('software.addon_empty_hint'))
            ->emptyStateIcon('heroicon-o-puzzle-piece');
    }
}
