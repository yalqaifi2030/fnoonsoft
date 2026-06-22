<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetPanelLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
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
            ->registration(\App\Filament\Pages\Auth\Register::class)
            ->passwordReset()
            ->emailVerification()
            ->profile(\App\Filament\Pages\Auth\EditProfile::class, isSimple: false)
            ->brandName(fn () => \App\Support\Branding::name('admin', 'Fnoon').' · '.__('member.brand'))
            ->brandLogo(fn () => \App\Support\Branding::logo('admin'))
            ->brandLogoHeight(fn () => \App\Support\Branding::logoHeight())
            ->favicon(fn () => \App\Support\Branding::favicon())
            ->defaultThemeMode(\App\Support\Branding::themeMode())
            ->colors(\App\Support\Theme::panelColors())
            ->font('Tajawal')
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                // Reuse the upload panel's professional chrome (sidebar gradient,
                // nav items, gold topbar, hidden scrollbars) so both panels match,
                // then add the member-only register-button CTA on top.
                fn (): string => view('filament.upload.chrome-styles')->render()
                    .view('filament.member.chrome-styles')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.unsaved-guard')->render(),
            )
            ->discoverResources(in: app_path('Filament/Member/Resources'), for: 'App\\Filament\\Member\\Resources')
            ->discoverPages(in: app_path('Filament/Member/Pages'), for: 'App\\Filament\\Member\\Pages')
            ->discoverWidgets(in: app_path('Filament/Member/Widgets'), for: 'App\\Filament\\Member\\Widgets')
            ->navigationItems([
                NavigationItem::make('profile')
                    ->label(__('member.profile'))
                    ->icon('heroicon-o-user-circle')
                    ->url(fn () => route('filament.member.auth.profile'))
                    ->isActiveWhen(fn () => request()->routeIs('filament.member.auth.profile'))
                    ->sort(10),
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label(__('member.public_page'))
                    ->url(fn () => auth()->user()?->publicProfileUrl() ?? '#')
                    ->icon('heroicon-o-user-circle')
                    ->visible(fn () => (bool) auth()->user()?->hasPublicProfile())
                    ->openUrlInNewTab(),
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
