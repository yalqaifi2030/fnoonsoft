<?php

namespace App\Filament\Upload\Pages;

use App\Services\Upload\UploadCleanup;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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

    /** Manually remove incomplete/abandoned uploads (bytes + chunks + rows). */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('cleanIncomplete')
                ->label(__('upload.cleanup.action'))
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading(__('upload.cleanup.action'))
                ->modalDescription(__('upload.cleanup.confirm'))
                ->action(function (): void {
                    $userId = auth()->user()?->hasRole('super_admin') ? null : auth()->id();
                    $cleanup = app(UploadCleanup::class);
                    $n = $cleanup->purgeIncomplete($userId);
                    $cleanup->purgeOrphanTmp();

                    Notification::make()->success()->title(__('upload.cleanup.done', ['n' => $n]))->send();
                    $this->dispatch('$refresh');
                }),
        ];
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
            'maxBytes' => (int) env('UPLOAD_MAX_BYTES', 42949672960),
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
