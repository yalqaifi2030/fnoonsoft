<?php

namespace App\Http\Controllers;

use App\Enums\ContentStatus;
use App\Models\DownloadLink;
use App\Models\DownloadLog;
use App\Models\Software;
use App\Services\Upload\R2UploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadController extends Controller
{
    public function __construct(private readonly R2UploadService $r2)
    {
    }

    /** Intermediate gateway page with a short countdown before the download. */
    public function gateway(Software $software, DownloadLink $link): View|RedirectResponse
    {
        abort_unless($software->status === ContentStatus::Published
            && $link->software_id === $software->id && $link->is_active, 404);

        if ($redirect = $this->guardPrivate($software)) {
            return $redirect;
        }

        return view('download.gateway', compact('software', 'link'));
    }

    /**
     * True when the rating gate is on and this visitor hasn't rated the item yet
     * (the session key is set by the quick-rate + review endpoints). Staff are
     * exempt so they can always fetch files.
     */
    private function rateGateBlocks(Request $request, Software $software): bool
    {
        if (! (bool) \App\Models\Setting::get('download_rating_gate', true)) {
            return false;
        }
        if ($request->user()?->isStaff()) {
            return false;
        }

        return ! $request->session()->get('reviewed.'.$software->id);
    }

    /** Private items hide downloads behind login — block guests at the source. */
    private function guardPrivate(Software $software): ?RedirectResponse
    {
        if ($software->download_requires_login && ! auth()->check()) {
            return redirect('/dashboard/login?redirect='.urlencode(route('software.show', $software)));
        }

        return null;
    }

    /**
     * Resolve the actual file: a short-lived presigned R2 URL (or the external
     * mirror), log the hit, bump counters, then redirect the browser to it.
     */
    public function start(Request $request, Software $software, DownloadLink $link): RedirectResponse|BinaryFileResponse
    {
        abort_unless($software->status === ContentStatus::Published
            && $link->software_id === $software->id && $link->is_active, 404);

        if ($redirect = $this->guardPrivate($software)) {
            return $redirect;
        }

        // Mandatory rate-before-download gate: without a rating in this session,
        // send the visitor to the gateway (which enforces the one-tap rating).
        // Download endpoints are robots-disallowed, so this never affects SEO.
        if ($this->rateGateBlocks($request, $software)) {
            return redirect()->route('download.gateway', [$software, $link]);
        }

        $this->log($request, $software, $link);

        $software->increment('downloads_count');
        $link->increment('downloads_count');

        // The same physical file is often ALSO a member's shareable asset
        // (/dashboard/assets). Count the hit there too, so "My files" reflects
        // real downloads even when they go through the software gateway.
        if ($link->r2_key) {
            try {
                \App\Models\Asset::where('path', $link->r2_key)->increment('downloads_count');
            } catch (\Throwable $e) {
                // a counter must never break a download
            }
        }

        if ($link->isExternal()) {
            return redirect()->away($link->external_url);
        }

        // A clean, DISTINCT filename per link (parts must not collide) that keeps the
        // real extension. Prefer the uploaded name, then the link label, then a slug.
        $ext = pathinfo($link->r2_key, PATHINFO_EXTENSION);
        $base = $link->original_filename
            ?: ($link->label ?: $software->slug.($software->current_version ? '-'.$software->current_version : ''));
        $filename = ($ext && ! str_ends_with(mb_strtolower($base), '.'.mb_strtolower($ext)))
            ? $base.'.'.$ext
            : $base;

        // Proxy uploads may still live on the local disk until ProcessUploadedFile
        // migrates them to S3 (needs the queue worker). Serve straight from there so
        // the download works either way — no more "NoSuchKey" while it's pending.
        if (Storage::disk('local')->exists($link->r2_key)) {
            // BinaryFileResponse supports HTTP range requests (resumable downloads).
            return response()->download(Storage::disk('local')->path($link->r2_key), $filename);
        }

        return redirect()->away($this->r2->temporaryDownloadUrl($link->r2_key, $filename));
    }

    private function log(Request $request, Software $software, DownloadLink $link): void
    {
        DownloadLog::create([
            'software_id' => $software->id,
            'download_link_id' => $link->id,
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'country' => $request->header('CF-IPCountry'), // Cloudflare geo header
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'referer' => substr((string) $request->headers->get('referer'), 0, 255),
            'created_at' => now(),
        ]);
    }
}
