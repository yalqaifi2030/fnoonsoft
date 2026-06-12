<?php

namespace App\Filament\Upload\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The uploader's home: a resumable, chunked uploader (Uppy → presigned R2
 * multipart) plus a live list of this user's recent upload sessions.
 */
class UploadCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static string $view = 'filament.upload.pages.upload-center';

    protected static ?int $navigationSort = 1;

    public function getTitle(): string|Htmlable
    {
        return __('upload.center.title');
    }

    /** Hide the default page heading — the view renders a custom hero instead. */
    public function getHeading(): string
    {
        return '';
    }

    public static function getNavigationLabel(): string
    {
        return __('upload.center.nav');
    }

    /** Recent shareable assets (files + images + PDF) for the signed-in uploader. */
    public function getRecentAssetsProperty()
    {
        return \App\Models\Asset::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(12)
            ->get();
    }

    /** Headline stats for the signed-in uploader (asset-based: covers every upload). */
    public function getStatsProperty(): array
    {
        $base = \App\Models\Asset::query()->where('user_id', auth()->id());

        return [
            'total' => (clone $base)->count(),
            'images' => (clone $base)->where('kind', 'image')->count(),
            'downloads' => (int) (clone $base)->sum('downloads_count'),
            'bytes' => (int) (clone $base)->sum('size_bytes'),
        ];
    }

    protected function getViewData(): array
    {
        $configured = app(\App\Services\Upload\R2UploadService::class)->isConfigured();
        $provider = \App\Models\Setting::get('storage_provider');

        return [
            'maxBytes' => (int) env('UPLOAD_MAX_BYTES', 32212254720),
            'partSize' => (int) env('UPLOAD_PART_SIZE', 33554432),
            'concurrency' => max(1, (int) env('UPLOAD_CONCURRENCY', 6)),
            'assets' => $this->recentAssets,
            'stats' => $this->stats,
            'storage' => $configured ? 'cloud' : 'local',
            'storageLabel' => $configured ? match ($provider) {
                'idrive' => 'iDrive e2',
                'aws' => 'Amazon S3',
                'custom' => 'S3',
                default => 'Cloudflare R2',
            } : null,
            'storageBucket' => $configured ? config('filesystems.disks.r2.bucket') : null,
        ];
    }
}
