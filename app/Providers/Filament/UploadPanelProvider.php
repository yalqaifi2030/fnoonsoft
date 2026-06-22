<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetPanelLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The uploader-facing panel (/upload). Authors and editors land here to push
 * builds up to 30GB to R2 and attach metadata. Separate from the admin panel
 * so uploaders never see site-wide management screens.
 */
class UploadPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('upload')
            ->path('upload')
            ->login()
            ->profile(\App\Filament\Pages\Auth\EditProfile::class, isSimple: false)
            ->brandName(fn () => \App\Support\Branding::name('upload', 'Fnoon Upload'))
            ->brandLogo(fn () => \App\Support\Branding::logo('upload'))
            ->brandLogoHeight(fn () => \App\Support\Branding::logoHeight())
            ->favicon(fn () => \App\Support\Branding::favicon())
            ->defaultThemeMode(\App\Support\Branding::themeMode())
            ->colors(\App\Support\Theme::panelColors())
            ->font('Tajawal')
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.upload.chrome-styles')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.unsaved-guard')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): string => view('filament.upload.sidebar-footer')->render(),
            )
            ->discoverResources(in: app_path('Filament/Upload/Resources'), for: 'App\\Filament\\Upload\\Resources')
            ->discoverPages(in: app_path('Filament/Upload/Pages'), for: 'App\\Filament\\Upload\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Upload/Widgets'), for: 'App\\Filament\\Upload\\Widgets')
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn () => app()->getLocale() === 'ar' ? 'English' : 'العربية')
                    ->url(fn () => route('panel.locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar'))
                    ->icon('heroicon-o-language'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetPanelLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
