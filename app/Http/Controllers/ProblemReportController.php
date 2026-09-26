<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Public "report a problem" endpoint. A member OR a guest can describe an issue
 * (e.g. a failed download), auto-attach a page screenshot, and it lands as a
 * support ticket the staff already manage — with the full context attached.
 */
class ProblemReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'description' => 'nullable|string|max:5000',
            'email' => 'nullable|email|max:190',
            'screenshot' => 'nullable|image|max:6144',   // auto html2canvas capture
            'attachment' => 'nullable|image|max:6144',    // optional manual upload
            'url' => 'nullable|string|max:1000',
            'software' => 'nullable|string|max:190',
            'software_slug' => 'nullable|string|max:190',
            'error' => 'nullable|string|max:1000',
            'browser' => 'nullable|string|max:400',
            'os' => 'nullable|string|max:120',
            'screen' => 'nullable|string|max:40',
            'source' => 'nullable|string|max:40',
            'website' => 'nullable|string|max:0', // honeypot — must stay empty
        ]);

        // Honeypot tripped: pretend success, drop silently.
        if ($request->filled('website')) {
            return response()->json(['ok' => true]);
        }

        $hasShot = $request->hasFile('screenshot');
        $hasFile = $request->hasFile('attachment');

        if (blank($data['description'] ?? null) && ! $hasShot && ! $hasFile) {
            return response()->json(['message' => __('report.need_info')], 422);
        }

        // Guests can post images here: cap the reports per visitor per day so
        // the form can't be used to fill the disk (the minute throttle alone
        // still allowed ~100 GB/day from one IP).
        $dayKey = 'report:day:'.now()->format('Ymd').':'.md5(\App\Support\Security::clientIp($request));
        \Illuminate\Support\Facades\Cache::add($dayKey, 0, now()->addDay());
        if (\Illuminate\Support\Facades\Cache::increment($dayKey) > 20) {
            return response()->json(['message' => __('report.need_info')], 429);
        }

        $user = $request->user();
        $source = in_array($data['source'] ?? '', ['download', 'web', 'error'], true) ? $data['source'] : 'web';
        $software = $data['software'] ?? null;

        $subject = $software
            ? __('report.subject_download', ['name' => Str::limit($software, 120)])
            : __('report.subject_generic');

        $meta = [
            'url' => $data['url'] ?? $request->headers->get('referer'),
            'software' => $software,
            'software_slug' => $data['software_slug'] ?? null,
            'error' => $data['error'] ?? null,
            'browser' => $data['browser'] ?? $request->userAgent(),
            'os' => $data['os'] ?? null,
            'screen' => $data['screen'] ?? null,
            'referer' => $request->headers->get('referer'),
            'ip' => $request->ip(),
            'source' => $source,
        ];

        $ticket = SupportTicket::create([
            'user_id' => $user?->id,
            'guest_email' => $user ? null : ($data['email'] ?? null),
            'subject' => Str::limit($subject, 185, ''),
            'category' => $source === 'download' ? 'download' : 'technical',
            'priority' => $source === 'download' ? 'high' : 'normal',
            'status' => 'open',
            'source' => $source,
            'meta' => $meta,
            'last_reply_at' => now(),
        ]);

        // Store screenshots on the public disk (same place as ticket attachments).
        $shotPath = $hasShot ? $this->storeImage($request->file('screenshot')) : null;
        $filePath = $hasFile ? $this->storeImage($request->file('attachment')) : null;

        $ticket->messages()->create([
            'user_id' => $user?->id,
            'body' => $this->buildBody($data, $meta),
            'attachment' => $shotPath ?: $filePath,
            'is_staff' => false,
        ]);

        // If both a capture AND a manual image were sent, keep the second in the thread.
        if ($shotPath && $filePath) {
            $ticket->messages()->create([
                'user_id' => $user?->id,
                'body' => __('report.extra_image'),
                'attachment' => $filePath,
                'is_staff' => false,
            ]);
        }

        $ticket->notifyStaff('ticket.notify.new');

        return response()->json([
            'ok' => true,
            'ticket' => $ticket->number(),
        ]);
    }

    /**
     * Re-encode an uploaded image to a bounded JPEG (max 1600px wide): a few
     * hundred KB instead of up to 6 MB, and nothing but pixels survives — no
     * polyglot payload. Returns null if the file isn't a decodable image.
     */
    private function storeImage(\Illuminate\Http\UploadedFile $file): ?string
    {
        $src = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if ($src === false) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $nw = min($w, 1600);
        $nh = max(1, (int) round($h * $nw / $w));

        // White canvas so transparent PNG areas don't turn black in the JPEG.
        $out = imagecreatetruecolor($nw, $nh);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        ob_start();
        imagejpeg($out, null, 80);
        $jpeg = (string) ob_get_clean();
        imagedestroy($out);

        $path = 'ticket-attachments/'.Str::random(40).'.jpg';
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $jpeg);

        return $path;
    }

    /** A readable message body: the user's words + a compact context block. */
    private function buildBody(array $data, array $meta): string
    {
        $body = trim((string) ($data['description'] ?? ''));
        if ($body === '') {
            $body = __('report.no_desc');
        }

        $ctx = [];
        if ($meta['url']) {
            $ctx[] = __('report.ctx.url').': '.$meta['url'];
        }
        if ($meta['software']) {
            $ctx[] = __('report.ctx.software').': '.$meta['software'];
        }
        if ($meta['error']) {
            $ctx[] = __('report.ctx.error').': '.$meta['error'];
        }
        $ctx[] = __('report.ctx.browser').': '.trim(($meta['browser'] ?? '—').' · '.($meta['os'] ?? ''), ' ·');
        if ($meta['screen']) {
            $ctx[] = __('report.ctx.screen').': '.$meta['screen'];
        }
        if (! empty($data['email'])) {
            $ctx[] = __('report.ctx.email').': '.$data['email'];
        }

        return $body."\n\n——————\n".implode("\n", $ctx);
    }
}
