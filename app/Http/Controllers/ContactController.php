<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Faq;
use App\Support\Security;
use App\Support\SpamGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        $faqs = Faq::where('is_active', true)->orderBy('sort_order')->get();

        return view('contact', compact('faqs'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Certain bots (honeypot, missing/forged/too-fast token, no JS) and blocked
        // senders are dropped SILENTLY — they get the same "sent" answer as people.
        if ($bot = SpamGuard::botCheck($request)) {
            Log::info('[fnoon] contact bot dropped', ['reason' => $bot, 'ip' => Security::clientIp($request)]);

            return back()->with('status', __('contact.sent'));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $ip = Security::clientIp($request);

        if (SpamGuard::blocked($data['email'], $ip)) {
            return back()->with('status', __('contact.sent'));
        }

        [$score, $reasons] = SpamGuard::score($data, $ip);
        $country = strtoupper((string) $request->header('CF-IPCountry'));

        Contact::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'ip_address' => $ip,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'country' => preg_match('/^[A-Z]{2}$/', $country) ? $country : null,
            'spam_score' => $score,
            'spam_reasons' => $reasons ?: null,
            'is_spam' => $score >= SpamGuard::THRESHOLD,
        ]);

        return back()->with('status', __('contact.sent'));
    }
}
