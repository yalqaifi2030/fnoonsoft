<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\Upload\AssetService;
use App\Services\Upload\R2UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Public landing + download for a shared asset (/d/{slug}). Honours password
 * protection and expiry, counts views/downloads, and serves the file from
 * whichever disk it lives on (public / local / R2).
 */
class AssetController extends Controller
{
    public function __construct(private readonly AssetService $assets)
    {
    }

    public function show(Request $request, Asset $asset)
    {
        abort_unless($asset->is_active, 404);

        $expired = $asset->isExpired();
        if (! $expired) {
            $asset->increment('views_count');
        }

        $locked = $asset->hasPassword() && ! $this->unlocked($request, $asset);

        return view('assets.show', [
            'asset' => $asset,
            'expired' => $expired,
            'locked' => $locked,
            'kit' => $locked || $expired ? [] : $this->assets->shareKit($asset),
        ]);
    }

    public function unlock(Request $request, Asset $asset)
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! $asset->hasPassword() || ! Hash::check($request->string('password'), $asset->password)) {
            return back()->withErrors(['password' => __('asset.wrong_password')]);
        }

        $request->session()->put($this->unlockKey($asset), true);

        return redirect()->route('assets.show', $asset);
    }

    public function download(Request $request, Asset $asset)
    {
        abort_unless($asset->is_active, 404);
        abort_if($asset->isExpired(), 410, __('asset.expired'));

        if ($asset->hasPassword() && ! $this->unlocked($request, $asset)) {
            return redirect()->route('assets.show', $asset);
        }

        $asset->increment('downloads_count');
        $this->notifyMilestone($asset);

        // R2: hand off to a short-lived presigned URL.
        if ($asset->disk === 'r2') {
            return redirect()->away(
                app(R2UploadService::class)->temporaryDownloadUrl($asset->path, $asset->original_name)
            );
        }

        // public / local: stream from the local filesystem as an attachment.
        $absolute = \Illuminate\Support\Facades\Storage::disk($asset->disk)->path($asset->path);
        abort_unless(is_file($absolute), 404);

        return response()->download($absolute, $asset->original_name);
    }

    /** Congratulate the owner when their file crosses a download milestone. */
    private function notifyMilestone(Asset $asset): void
    {
        try {
            $count = (int) $asset->downloads_count;

            if (! $asset->user_id || ! in_array($count, [100, 500, 1000, 5000, 10000, 50000, 100000], true)) {
                return;
            }

            if ($owner = $asset->user) {
                \Filament\Notifications\Notification::make()
                    ->title(__('member.notify.milestone_title', ['count' => number_format($count)]))
                    ->body(__('member.notify.milestone_body', ['file' => $asset->original_name]))
                    ->icon('heroicon-o-trophy')
                    ->iconColor('warning')
                    ->success()
                    ->sendToDatabase($owner);
            }
        } catch (\Throwable $e) {
            // A notification must never break a download.
        }
    }

    private function unlocked(Request $request, Asset $asset): bool
    {
        return (bool) $request->session()->get($this->unlockKey($asset));
    }

    private function unlockKey(Asset $asset): string
    {
        return 'asset_unlock_'.$asset->id;
    }
}
