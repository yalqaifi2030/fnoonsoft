<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shows the "closed for maintenance" page on the public site when the
 * maintenance toggle is on. Signed-in staff bypass it (so they can preview),
 * and the admin/upload panels are never affected (they run on Filament's own
 * middleware stack, not the web group).
 */
class EnsureSiteIsAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::get('maintenance_enabled')) {
            return $next($request);
        }

        // Never gate the admin/upload panels, their login, or Livewire. The
        // Filament login form POSTs to /livewire/update (registered in the web
        // group), and during that request the user isn't authenticated yet — so
        // without this exemption, signing in while maintenance is on returns the
        // maintenance page and the login silently breaks.
        if ($request->is('admin', 'admin/*', 'upload', 'upload/*', 'livewire/*', '*/livewire/*')) {
            return $next($request);
        }

        // Logged-in users (staff) keep full access to preview the live site.
        if ($request->user()) {
            return $next($request);
        }

        return response()
            ->view('maintenance', \App\Support\MaintenancePage::data(), 503)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
