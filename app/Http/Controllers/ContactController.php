<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
            // simple honeypot — bots fill this, humans never see it
            'website' => ['nullable', 'size:0'],
        ]);

        Contact::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', __('contact.sent'));
    }
}
