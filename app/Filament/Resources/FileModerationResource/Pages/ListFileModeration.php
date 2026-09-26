<?php

namespace App\Filament\Resources\FileModerationResource\Pages;

use App\Filament\Resources\FileModerationResource;
use App\Support\FileModeration;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListFileModeration extends ListRecords
{
    protected static string $resource = FileModerationResource::class;

    public function getSubheading(): ?string
    {
        return __('moderation.badge_tooltip');
    }

    public function getTabs(): array
    {
        $base = FileModerationResource::getEloquentQuery();
        $count = fn (callable $scope) => (clone $base)->where($scope)->count();

        $pending = fn (Builder $query) => $query->where('moderation_status', FileModeration::PENDING);
        $reported = fn (Builder $query) => $query->where('open_reports', '>', 0);
        $approved = fn (Builder $query) => $query->where('moderation_status', FileModeration::APPROVED);
        $rejected = fn (Builder $query) => $query->where('moderation_status', FileModeration::REJECTED);

        return [
            'pending' => Tab::make(__('moderation.tab.pending'))
                ->icon('heroicon-m-clock')
                ->badge($count($pending) ?: null)->badgeColor('warning')
                ->modifyQueryUsing($pending),
            'reported' => Tab::make(__('moderation.tab.reported'))
                ->icon('heroicon-m-flag')
                ->badge($count($reported) ?: null)->badgeColor('danger')
                ->modifyQueryUsing($reported),
            'approved' => Tab::make(__('moderation.tab.approved'))
                ->icon('heroicon-m-check-badge')
                ->modifyQueryUsing($approved),
            'rejected' => Tab::make(__('moderation.tab.rejected'))
                ->icon('heroicon-m-no-symbol')
                ->modifyQueryUsing($rejected),
            'all' => Tab::make(__('moderation.tab.all'))
                ->icon('heroicon-m-queue-list'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'pending';
    }

    /** Friendlier empty state on the "needs review" tab. */
    protected function getTableEmptyStateHeading(): ?string
    {
        return $this->activeTab === 'pending' ? __('moderation.empty.pending') : __('moderation.empty.default');
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return $this->activeTab === 'pending' ? __('moderation.empty.pending_desc') : null;
    }
}
