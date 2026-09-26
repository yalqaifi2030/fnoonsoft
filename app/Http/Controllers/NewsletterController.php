<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterConfirmMail;
use App\Models\NewsletterSubscriber;
use App\Rules\RealEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    /**
     * Double opt-in: a new (or previously unsubscribed) address gets a
     * confirmation email and is only mailed once its owner clicks it.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:160', new RealEmail],
        ]);

        $sub = NewsletterSubscriber::firstOrNew(['email' => $data['email']]);

        if ($sub->exists && $sub->is_confirmed) {
            return back()->with('status', __('newsletter.subscribed'));
        }

        $sub->fill([
            'locale' => app()->getLocale(),
            'is_confirmed' => false,
            'token' => $sub->token ?: Str::random(40),
        ])->save();

        // At most one confirmation email per address per 10 minutes (no mail-bombing).
        if (Cache::add('newsletter:confirm:'.md5(strtolower($sub->email)), 1, now()->addMinutes(10))) {
            try {
                Mail::to($sub->email)->send(new NewsletterConfirmMail(route('newsletter.confirm', $sub->token)));
            } catch (\Throwable $e) {
                Log::warning('[fnoon] newsletter confirm mail failed', ['msg' => $e->getMessage()]);
            }
        }

        return back()->with('status', __('newsletter.check_email'));
    }

    public function confirm(string $token): View
    {
        $sub = NewsletterSubscriber::where('token', $token)->first();

        $sub?->forceFill(['is_confirmed' => true, 'confirmed_at' => now()])->save();

        return view('newsletter.unsubscribed', ['ok' => (bool) $sub, 'mode' => 'confirmed']);
    }

    /** GET only shows a confirm button — mail link-scanners prefetch GETs. */
    public function unsubscribeForm(string $token): View
    {
        $sub = NewsletterSubscriber::where('token', $token)->first();

        return view('newsletter.unsubscribed', ['ok' => (bool) $sub, 'mode' => 'ask', 'token' => $token]);
    }

    /** POST: the button above, or a mail client's RFC 8058 one-click unsubscribe. */
    public function unsubscribe(Request $request, string $token): View|Response
    {
        $sub = NewsletterSubscriber::where('token', $token)->first();
        $sub?->forceFill(['is_confirmed' => false])->save();

        // One-click from the mail client (no browser) — just acknowledge.
        if ($request->input('List-Unsubscribe') === 'One-Click') {
            return response('', $sub ? 200 : 404);
        }

        return view('newsletter.unsubscribed', ['ok' => (bool) $sub, 'mode' => 'done']);
    }
}
