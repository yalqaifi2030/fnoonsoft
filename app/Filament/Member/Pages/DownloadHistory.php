<?php

namespace App\Filament\Member\Pages;

use App\Models\DownloadLog;
use App\Models\Software;
use Filament\Pages\Page;

/**
 * The member's personal download history: every program they've downloaded
 * (grouped, most-recent first) — scoped to the signed-in member's account, so
 * it follows them across devices.
 */
class DownloadHistory extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.member.download-history';

    public static function getNavigationLabel(): string
    {
        return __('member.downloads.nav');
    }

    public function getTitle(): string
    {
        return __('member.downloads.title');
    }

    protected function getViewData(): array
    {
        $uid = auth()->id();

        // One row per program, with how many times + the latest time.
        $rows = DownloadLog::where('user_id', $uid)
            ->selectRaw('software_id, COUNT(*) as times, MAX(created_at) as last_at')
            ->groupBy('software_id')
            ->orderByDesc('last_at')
            ->limit(300)
            ->get();

        $softwares = Software::whereIn('id', $rows->pluck('software_id'))
            ->get()
            ->keyBy('id');

        $totalDownloads = (int) DownloadLog::where('user_id', $uid)->count();
        $programs = $rows->count();

        return compact('rows', 'softwares', 'totalDownloads', 'programs');
    }
}
