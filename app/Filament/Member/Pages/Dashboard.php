<?php

namespace App\Filament\Member\Pages;

use App\Filament\Upload\Pages\UploadCenter;

/**
 * The member's home: reuses the uploader + "my files" + stats from UploadCenter,
 * but lives in the member panel (/dashboard) and is scoped to the signed-in
 * member. Per-member storage quota is enforced in MultipartUploadController.
 */
class Dashboard extends UploadCenter
{
    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('member.nav.dashboard');
    }

    /** Add the member's quota to the stats the view already renders. */
    public function getStatsProperty(): array
    {
        $stats = parent::getStatsProperty();
        $user = auth()->user();

        // Always show the member storage allowance as a bar (even for staff
        // previewing the panel) — staff aren't actually capped at upload time.
        $gb = (float) (\App\Models\Setting::get('member_quota_gb', 10) ?: 10);
        $quota = (int) round($gb * 1024 ** 3);
        $used = $user ? $user->storageUsedBytes() : 0;

        $stats['quota'] = $quota;
        $stats['remaining'] = max(0, $quota - $used);

        return $stats;
    }
}
