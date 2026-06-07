<?php

namespace App\Providers;

use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Filters\SelectFilter;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Apply storage (S3/iDrive) + mail settings saved from the admin panel,
        // overriding .env so they take effect immediately without a redeploy.
        $this->applyDynamicConfig();

        // Use Filament's polished JS-powered dropdown (themed with the green/gold
        // primary) instead of the plain native <select> across BOTH panels.
        Select::configureUsing(function (Select $select): void {
            $select->native(false);
        });

        SelectFilter::configureUsing(function (SelectFilter $filter): void {
            $filter->native(false);
        });

        // Clipboard helper that also works on insecure (http) origins where the
        // browser's navigator.clipboard API is unavailable.
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => self::clipboardScript(),
        );

        // Brand polish for the select dropdowns in both Filament panels.
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => <<<'HTML'
            <style>
                /* Rounder, premium controls */
                .fi-fo-select-input button,
                .fi-input-wrp { border-radius: .65rem; }
                /* Themed dropdown panel: soft gold border + deeper shadow */
                .fi-dropdown-panel,
                .fi-fo-select-options {
                    border: 1px solid rgba(201,169,97,.30) !important;
                    border-radius: .85rem !important;
                    box-shadow: 0 18px 45px -18px rgba(0,108,53,.35) !important;
                }
                /* Selected option accent */
                .fi-fo-select-option-label[aria-selected="true"],
                .fi-dropdown-list-item-label:hover { color: #006C35; }
            </style>
            HTML
        );
    }

    /** A copy-to-clipboard helper that falls back to execCommand on http origins. */
    public static function clipboardScript(): string
    {
        return <<<'HTML'
        <script>
            window.fnoonCopy = function (text) {
                text = (text === null || text === undefined) ? '' : String(text);
                var legacy = function () {
                    return new Promise(function (resolve) {
                        var ta = document.createElement('textarea');
                        ta.value = text;
                        ta.setAttribute('readonly', '');
                        // A tiny, transparent, IN-VIEWPORT box copies more reliably than an off-screen one.
                        ta.style.cssText = 'position:fixed;top:0;left:0;width:1px;height:1px;padding:0;margin:0;border:0;opacity:0;z-index:2147483647;';
                        document.body.appendChild(ta);
                        var sel = document.getSelection();
                        var prev = (sel && sel.rangeCount) ? sel.getRangeAt(0) : null;
                        ta.focus({ preventScroll: true });
                        ta.select();
                        try { ta.setSelectionRange(0, text.length); } catch (e) {}
                        try { document.execCommand('copy'); } catch (e) {}
                        document.body.removeChild(ta);
                        if (prev && sel) { try { sel.removeAllRanges(); sel.addRange(prev); } catch (e) {} }
                        resolve();
                    });
                };
                if (navigator.clipboard && window.isSecureContext) {
                    return navigator.clipboard.writeText(text).catch(legacy);
                }
                return legacy();
            };
        </script>
        HTML;
    }

    /** Override filesystem (r2 disk) + mail config from DB-stored settings. */
    private function applyDynamicConfig(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return; // DB not ready (e.g. during install) — keep .env defaults
        }

        // ---- Storage (S3-compatible: Cloudflare R2 / AWS S3 / iDrive e2) ----
        if (filled(Setting::get('storage_key')) && filled(Setting::get('storage_bucket'))) {
            config([
                'filesystems.disks.r2.driver' => 's3',
                'filesystems.disks.r2.key' => Setting::get('storage_key'),
                'filesystems.disks.r2.secret' => Setting::get('storage_secret'),
                'filesystems.disks.r2.region' => Setting::get('storage_region') ?: 'auto',
                'filesystems.disks.r2.bucket' => Setting::get('storage_bucket'),
                'filesystems.disks.r2.endpoint' => Setting::get('storage_endpoint') ?: null,
                'filesystems.disks.r2.use_path_style_endpoint' => (bool) Setting::get('storage_path_style', true),
                'filesystems.disks.r2.url' => Setting::get('storage_public_url') ?: null,
            ]);
        }

        // ---- Mail (SMTP) ----
        if (filled(Setting::get('mail_host'))) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => Setting::get('mail_host'),
                'mail.mailers.smtp.port' => (int) (Setting::get('mail_port') ?: 587),
                'mail.mailers.smtp.username' => Setting::get('mail_username') ?: null,
                'mail.mailers.smtp.password' => Setting::get('mail_password') ?: null,
                'mail.mailers.smtp.encryption' => Setting::get('mail_encryption') ?: null,
            ]);

            if (filled(Setting::get('mail_from_address'))) {
                config(['mail.from.address' => Setting::get('mail_from_address')]);
            }
            if (filled(Setting::get('mail_from_name'))) {
                config(['mail.from.name' => Setting::get('mail_from_name')]);
            }
        }
    }
}
