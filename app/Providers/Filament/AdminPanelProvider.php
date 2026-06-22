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
 * The central control panel (/admin). Manages ALL content, taxonomy,
 * publishing, users/roles, uploads and system settings.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile(\App\Filament\Pages\Auth\EditProfile::class, isSimple: false)
            ->brandName(fn () => \App\Support\Branding::name('admin', 'Fnoon Admin'))
            ->brandLogo(fn () => \App\Support\Branding::logo('admin'))
            ->brandLogoHeight(fn () => \App\Support\Branding::logoHeight())
            ->favicon(fn () => \App\Support\Branding::favicon())
            ->defaultThemeMode(\App\Support\Branding::themeMode())
            ->colors(\App\Support\Theme::panelColors())
            ->font('Tajawal')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['ctrl+k', 'command+k'])
            ->navigationGroups([
                __('nav.group.content'),
                __('nav.group.publishing'),
                __('nav.group.engagement'),
                __('nav.group.analytics'),
                __('nav.group.users'),
                __('nav.group.system'),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.admin.chrome-styles')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.unsaved-guard')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): string => view('filament.admin.sidebar-footer')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => view('filament.admin.topbar-actions')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_BEFORE,
                fn (): string => \App\Models\Setting::get('maintenance_enabled')
                    ? view('filament.admin.maintenance-banner')->render()
                    : '',
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            // The dashboard home is App\Filament\Pages\Dashboard (auto-discovered),
            // which curates its widgets; full visitor detail is VisitorAnalytics.
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
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
