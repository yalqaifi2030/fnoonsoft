<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:160'],
        ]);

        // Instant subscribe (no double opt-in): re-subscribing reactivates a
        // previously unsubscribed address and keeps its token.
        $sub = NewsletterSubscriber::firstOrNew(['email' => $data['email']]);
        $sub->fill([
            'locale' => app()->getLocale(),
            'is_confirmed' => true,
            'confirmed_at' => $sub->confirmed_at ?: now(),
            'token' => $sub->token ?: Str::random(40),
        ])->save();

        return back()->with('status', __('newsletter.subscribed'));
    }

    public function unsubscribe(string $token): View
    {
        $sub = NewsletterSubscriber::where('token', $token)->first();

        if ($sub) {
            $sub->forceFill(['is_confirmed' => false])->save();
        }

        return view('newsletter.unsubscribed', ['ok' => (bool) $sub]);
    }
}
