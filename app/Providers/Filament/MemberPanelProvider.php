<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetPanelLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The member dashboard (/dashboard). Registered members upload, manage and
 * share their own files here — scoped strictly to the signed-in user, with a
 * per-member storage quota. Gated behind email verification + an admin toggle.
 */
class MemberPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('member')
            ->path('dashboard')
            ->login()
            ->registration()
            ->emailVerification()
            ->profile(\App\Filament\Pages\Auth\EditProfile::class)
            ->brandName(fn () => \App\Support\Branding::name('admin', 'Fnoon').' · '.__('member.brand'))
            ->brandLogo(fn () => \App\Support\Branding::logo('admin'))
            ->brandLogoHeight(fn () => \App\Support\Branding::logoHeight())
            ->favicon(fn () => \App\Support\Branding::favicon())
            ->defaultThemeMode(\App\Support\Branding::themeMode())
            ->colors([
                'primary' => Color::hex('#006C35'),
                'gold' => Color::hex('#C9A961'),
            ])
            ->font('Tajawal')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Member/Resources'), for: 'App\\Filament\\Member\\Resources')
            ->discoverPages(in: app_path('Filament/Member/Pages'), for: 'App\\Filament\\Member\\Pages')
            ->discoverWidgets(in: app_path('Filament/Member/Widgets'), for: 'App\\Filament\\Member\\Widgets')
            ->userMenuItems([
                MenuItem::make()
                    ->label(__('member.back_to_site'))
                    ->url(fn () => url('/'))
                    ->icon('heroicon-o-arrow-left-on-rectangle'),
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
