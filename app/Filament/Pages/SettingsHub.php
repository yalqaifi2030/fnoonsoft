<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PageResource;
use App\Filament\Resources\UploadSessionResource;
use App\Filament\Resources\UserResource;
use Filament\Pages\Page;

/**
 * A card-grid landing that gathers every system/settings area in one place.
 */
class SettingsHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.settings-hub';

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.hub.nav');
    }

    public function getTitle(): string
    {
        return __('settings.hub.title');
    }

    /** @return array<int,array{icon:string,title:string,desc:string,url:string,color:string}> */
    public function getCards(): array
    {
        return [
            [
                'icon' => 'fa-solid fa-sliders', 'color' => '#006C35',
                'title' => __('settings.hub.general'), 'desc' => __('settings.hub.general_desc'),
                'url' => ManageSettings::getUrl(),
            ],
            [
                'icon' => 'fa-solid fa-database', 'color' => '#2563eb',
                'title' => __('settings.hub.storage'), 'desc' => __('settings.hub.storage_desc'),
                'url' => StorageSettings::getUrl(),
            ],
            [
                'icon' => 'fa-solid fa-paper-plane', 'color' => '#d97706',
                'title' => __('settings.hub.mail'), 'desc' => __('settings.hub.mail_desc'),
                'url' => EmailSettings::getUrl(),
            ],
            [
                'icon' => 'fa-solid fa-screwdriver-wrench', 'color' => '#dc2626',
                'title' => __('settings.hub.maintenance'), 'desc' => __('settings.hub.maintenance_desc'),
                'url' => MaintenanceSettings::getUrl(),
            ],
            [
                'icon' => 'fa-solid fa-palette', 'color' => '#9333ea',
                'title' => __('settings.hub.branding'), 'desc' => __('settings.hub.branding_desc'),
                'url' => BrandingSettings::getUrl(),
            ],
            [
                'icon' => 'fa-solid fa-shield-virus', 'color' => '#16a34a',
                'title' => __('settings.hub.scan'), 'desc' => __('settings.hub.scan_desc'),
                'url' => ScanSettings::getUrl(),
            ],
            [
                'icon' => 'fa-solid fa-file-lines', 'color' => '#7c3aed',
                'title' => __('settings.hub.pages'), 'desc' => __('settings.hub.pages_desc'),
                'url' => PageResource::getUrl('index'),
            ],
            [
                'icon' => 'fa-solid fa-cloud-arrow-up', 'color' => '#0891b2',
                'title' => __('settings.hub.uploads'), 'desc' => __('settings.hub.uploads_desc'),
                'url' => UploadSessionResource::getUrl('index'),
            ],
            [
                'icon' => 'fa-solid fa-user-shield', 'color' => '#be123c',
                'title' => __('settings.hub.admins'), 'desc' => __('settings.hub.admins_desc'),
                'url' => UserResource::getUrl('index'),
            ],
        ];
    }
}
