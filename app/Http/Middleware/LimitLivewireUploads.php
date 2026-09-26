<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on Livewire's temporary-upload endpoint. The 1.5 GB ceiling exists for
 * staff learning videos; members/guests only ever upload avatars, covers and
 * ticket images, so they get 20 MB — otherwise any member could push 1.5 GB
 * files into livewire-tmp (kept 24h) until the disk filled up.
 */
class LimitLivewireUploads
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isStaff()) {
            config(['livewire.temporary_file_upload.rules' => ['required', 'file', 'max:20480']]);
        }

        return $next($request);
    }
}
