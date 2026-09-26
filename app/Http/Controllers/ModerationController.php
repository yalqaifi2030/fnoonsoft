<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Policies\StaffPermissionPolicy;
use App\Services\Upload\R2UploadService;
use App\Support\FileModeration;
use App\Support\Security;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * File-moderation endpoints outside the Filament panel:
 *  • download — a moderator fetches ANY member file (even rejected/locked) to
 *    inspect it; every download is written to the review history.
 *  • report   — a visitor flags a shared file from its /d/{slug} page.
 */
class ModerationController extends Controller
{
    public function download(Request $request, Asset $asset): Response
    {
        abort_unless(StaffPermissionPolicy::allows($request->user(), 'moderate files'), 403);

        FileModeration::log($asset, 'downloaded', $request->user());

        if ($asset->disk === 'r2') {
            return redirect()->away(app(R2UploadService::class)->temporaryDownloadUrl($asset->path, $asset->original_name));
        }

        $disk = in_array($asset->kind, ['image', 'pdf'], true) ? $asset->mediaDisk() : $asset->disk;
        $absolute = Storage::disk($disk)->path($asset->path);
        abort_unless(is_file($absolute), 404);

        return response()->download($absolute, $asset->original_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function report(Request $request, Asset $asset): RedirectResponse
    {
        abort_unless($asset->is_active && ! $asset->isRejected(), 404);

        // Honeypot: bots fill it, humans never see it — pretend success.
        if ($request->filled('website')) {
            return back()->with('report_status', __('moderation.public.thanks'));
        }

        $data = $request->validate([
            'reason' => ['required', 'in:'.implode(',', FileModeration::REASONS)],
            'message' => ['nullable', 'string', 'max:2000'],
            'email' => ['nullable', 'email', 'max:190'],
        ]);

        // One report per visitor per file per day (no report-flooding).
        $key = 'asset-report:'.$asset->id.':'.md5(Security::clientIp($request));
        if (\Illuminate\Support\Facades\Cache::add($key, 1, now()->addDay())) {
            FileModeration::report($asset, $data, $request->user(), Security::clientIp($request));
        }

        return back()->with('report_status', __('moderation.public.thanks'));
    }
}
