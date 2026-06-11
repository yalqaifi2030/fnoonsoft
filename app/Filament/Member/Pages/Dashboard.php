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
    /** Live at the panel root: /dashboard (not /dashboard/dashboard). */
    protected static string $routePath = '/';

    public static function getRoutePath(): string
    {
        return static::$routePath;
    }

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

        // Show the member's ACTUAL allowance (manual quota → tier → default), so
        // raising a member's space in the admin reflects on their dashboard.
        $quota = $user ? $user->displayQuotaBytes() : 0;
        $used = $user ? $user->storageUsedBytes() : 0;

        $stats['quota'] = $quota;
        $stats['remaining'] = max(0, $quota - $used);

        return $stats;
    }
}
