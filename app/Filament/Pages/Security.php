<?php

namespace App\Filament\Pages;

use App\Support\Totp;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;

/**
 * Self-service two-factor (authenticator app) management for the signed-in user.
 * Registered in the admin panel (discovered) and the member panel (subclass).
 */
class Security extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.security';

    protected static ?int $navigationSort = 95;

    public bool $showingSetup = false;

    public ?string $secret = null;

    public ?string $otpauthUri = null;

    public string $confirmCode = '';

    public string $disablePassword = '';

    /** @var array<int,string> shown once, right after enabling / regenerating */
    public array $recoveryCodes = [];

    public static function getNavigationLabel(): string
    {
        return __('security.nav');
    }

    public function getTitle(): string
    {
        return __('security.title');
    }

    public function getUser()
    {
        return auth()->user();
    }

    /** Begin setup: mint a secret (saved unconfirmed) and reveal the QR. */
    public function startEnable(): void
    {
        if ($this->getUser()->hasTwoFactorEnabled()) {
            return;
        }

        $this->secret = Totp::secret();
        $this->getUser()->forceFill([
            'two_factor_secret' => $this->secret,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->otpauthUri = Totp::uri($this->secret, (string) $this->getUser()->email, config('app.name'));
        $this->confirmCode = '';
        $this->recoveryCodes = [];
        $this->showingSetup = true;
    }

    /** Confirm setup by verifying a code from the app; issues recovery codes. */
    public function confirmEnable(): void
    {
        $this->validate(['confirmCode' => ['required', 'string']]);
        $user = $this->getUser();

        if (! $user->two_factor_secret || ! Totp::verify($user->two_factor_secret, $this->confirmCode)) {
            Notification::make()->danger()->title(__('security.bad_code'))->send();

            return;
        }

        $codes = $user->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $codes,
        ])->save();

        session(['2fa_passed' => true]); // they just proved possession

        $this->recoveryCodes = $codes;
        $this->showingSetup = false;
        $this->secret = null;
        $this->otpauthUri = null;
        $this->confirmCode = '';

        Notification::make()->success()->title(__('security.enabled_ok'))->send();
    }

    public function cancelEnable(): void
    {
        $user = $this->getUser();
        if (! $user->hasTwoFactorEnabled()) {
            $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
        }
        $this->reset(['showingSetup', 'secret', 'otpauthUri', 'confirmCode']);
    }

    public function disable(): void
    {
        $this->validate(['disablePassword' => ['required', 'string']]);

        if (! Hash::check($this->disablePassword, (string) $this->getUser()->password)) {
            Notification::make()->danger()->title(__('security.bad_password'))->send();

            return;
        }

        $this->getUser()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->reset(['disablePassword', 'recoveryCodes']);
        Notification::make()->success()->title(__('security.disabled_ok'))->send();
    }

    public function regenerateRecoveryCodes(): void
    {
        $user = $this->getUser();
        if (! $user->hasTwoFactorEnabled()) {
            return;
        }

        $codes = $user->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();
        $this->recoveryCodes = $codes;

        Notification::make()->success()->title(__('security.recovery_regenerated'))->send();
    }
}
