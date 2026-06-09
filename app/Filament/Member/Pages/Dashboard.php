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

        $stats['quota'] = $user?->storageQuotaBytes() ?? 0;
        $stats['remaining'] = $user?->storageRemainingBytes() ?? 0;

        return $stats;
    }
}
