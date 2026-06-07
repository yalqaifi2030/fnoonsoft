<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:160'],
        ]);

        NewsletterSubscriber::firstOrCreate(
            ['email' => $data['email']],
            ['locale' => app()->getLocale(), 'token' => Str::random(40)],
        );

        return back()->with('status', __('newsletter.subscribed'));
    }
}
