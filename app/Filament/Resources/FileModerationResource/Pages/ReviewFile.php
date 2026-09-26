<?php

namespace App\Filament\Resources\FileModerationResource\Pages;

use App\Filament\Resources\FileModerationResource as R;
use App\Models\Asset;
use App\Support\ArchiveInspector;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

/** The detailed review screen: preview, facts, uploader, scan, reports, history. */
class ReviewFile extends ViewRecord
{
    protected static string $resource = R::class;

    protected static string $view = 'filament.moderation.review';

    /** Filled on demand by inspectArchive() for ZIPs on object storage. */
    public ?array $remoteArchive = null;

    public bool $remoteArchiveFailed = false;

    public function inspectArchive(): void
    {
        abort_unless(R::canViewAny(), 403);

        $this->remoteArchive = ArchiveInspector::inspectRemote($this->record);
        $this->remoteArchiveFailed = $this->remoteArchive === null;
    }

    public function getTitle(): string
    {
        return (string) $this->record->original_name;
    }

    public function getBreadcrumb(): string
    {
        return __('moderation.action.review');
    }

    protected function getHeaderActions(): array
    {
        return [
            R::approveAction(Actions\Action::make('approve'))->visible(fn (Asset $record) => $record->moderation_status === 'pending'),
            R::restoreAction(Actions\Action::make('restore')),
            R::rejectAction(Actions\Action::make('reject')),
            R::downloadAction(Actions\Action::make('download'))->color('primary'),

            Actions\ActionGroup::make([
                R::openPageAction(Actions\Action::make('open_page')),
                R::trustAction(Actions\Action::make('trust')),
                R::banAction(Actions\Action::make('ban')),
                Actions\Action::make('owner_profile')
                    ->label(__('moderation.action.owner_profile'))
                    ->icon('heroicon-m-user-circle')->color('gray')
                    ->visible(fn (Asset $record) => $record->user !== null && \App\Filament\Resources\UserResource::canViewAny())
                    ->url(fn (Asset $record) => \App\Filament\Resources\UserResource::getUrl('edit', ['record' => $record->user])),
                Actions\DeleteAction::make()->icon('heroicon-m-trash')
                    ->successRedirectUrl(R::getUrl('index')),
            ])
                ->label(__('moderation.action.menu'))
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button(),
        ];
    }

    /** Everything the Blade view needs, computed once per render. */
    protected function getViewData(): array
    {
        /** @var Asset $asset */
        $asset = $this->record->loadMissing(['user', 'uploadSession', 'reviewer']);
        $owner = $asset->user;

        return [
            'asset' => $asset,
            'owner' => $owner,
            'ownerStats' => $owner ? [
                'files' => Asset::where('user_id', $owner->id)->count(),
                'bytes' => (int) Asset::where('user_id', $owner->id)->sum('size_bytes'),
                'rejected' => Asset::where('user_id', $owner->id)->where('moderation_status', 'rejected')->count(),
            ] : null,
            'archive' => $this->remoteArchive ?? ArchiveInspector::inspect($asset),
            'canInspectRemote' => ArchiveInspector::canInspectRemote($asset),
            'reports' => $asset->reports()->with('user')->limit(20)->get(),
            'history' => $asset->reviews()->with('actor')->limit(40)->get(),
        ];
    }
}
