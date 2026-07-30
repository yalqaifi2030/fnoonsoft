<?php

namespace App\Filament\Pages;

use App\Support\SiteInfo;
use App\Models\Setting;
use Filament\Pages\Page;

/**
 * "About this site" — version, the technology stack (mostly resolved live), and
 * the developer credit. A read-only info page under the System group.
 */
class About extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.about';

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('about.nav');
    }

    public function getTitle(): string
    {
        return __('about.title');
    }

    public function getViewData(): array
    {
        return [
            'siteName' => Setting::text('site_name', config('app.name')),
            'version' => SiteInfo::version(),
            'released' => SiteInfo::RELEASED,
            'codename' => SiteInfo::CODENAME,
            'stack' => SiteInfo::stack(),
            'developer' => SiteInfo::developer(),
            'phpEnv' => app()->environment(),
        ];
    }
}
