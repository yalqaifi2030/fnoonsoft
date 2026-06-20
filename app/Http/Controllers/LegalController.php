<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Legal report pages: DMCA copyright takedowns and content/abuse reports.
 * Each page has its info + a form that lands as a (high-priority) support
 * ticket the staff already manage — guests welcome.
 */
class LegalController extends Controller
{
    public function dmca(): View
    {
        return view('legal.report', ['type' => 'dmca']);
    }

    public function abuse(): View
    {
        return view('legal.report', ['type' => 'abuse']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:dmca,abuse'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'url' => ['required', 'string', 'max:1000'],
            'details' => ['required', 'string', 'max:5000'],
            'agree' => ['accepted'],
            'website' => ['nullable', 'max:0'], // honeypot
        ]);

        if ($request->filled('website')) {
            return back()->with('status', __('legal.sent')); // silently drop bots
        }

        $type = $data['type'];

        $body = $data['details']
            ."\n\n——————\n"
            .__('legal.field.url').': '.$data['url']
            ."\n".__('legal.field.name').': '.$data['name']
            ."\n".__('legal.field.email').': '.$data['email'];

        $ticket = SupportTicket::create([
            'user_id' => $request->user()?->id,
            'guest_name' => $request->user() ? null : $data['name'],
            'guest_email' => $request->user() ? null : $data['email'],
            'subject' => Str::limit(__('legal.'.$type.'.subject').': '.$data['url'], 185, ''),
            'category' => $type,
            'priority' => 'high',
            'status' => 'open',
            'source' => $type,
            'meta' => [
                'url' => $data['url'],
                'name' => $data['name'],
                'email' => $data['email'],
                'ip' => $request->ip(),
            ],
            'last_reply_at' => now(),
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()?->id,
            'body' => $body,
            'is_staff' => false,
        ]);

        $ticket->notifyStaff('ticket.notify.new');

        return back()->with('status', __('legal.sent'));
    }
}
