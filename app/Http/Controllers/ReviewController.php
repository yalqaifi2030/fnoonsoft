<?php

namespace App\Http\Controllers;

use App\Enums\ContentStatus;
use App\Models\Setting;
use App\Models\Software;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Software $software): RedirectResponse
    {
        abort_unless($software->status === ContentStatus::Published, 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'author_name' => ['required', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:2000'],
            // honeypot: bots fill this, humans never see it
            'website' => ['nullable', 'size:0'],
        ]);

        // Always land on the product page reviews (works from the gateway too).
        $dest = fn () => redirect()->to(route('software.show', $software).'#reviews');

        // One review per visitor per software (soft guard via the session).
        $key = 'reviewed.'.$software->id;
        if ($request->session()->get($key)) {
            return $dest()->with('review_status', __('review.already'));
        }

        // Moderation-first (same as comments): wait for approval by default.
        $autoApprove = (bool) Setting::get('reviews_auto_approve', false);
        $status = ($autoApprove && ! $this->looksSpammy((string) ($data['body'] ?? ''))) ? 'approved' : 'pending';

        $review = $software->reviews()->create([
            'user_id' => $request->user()?->id,
            'author_name' => $request->user()?->displayName() ?: $data['author_name'],
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'status' => $status,
        ]);

        $request->session()->put($key, true);

        // The average is recomputed automatically by the Review model hook.
        $this->notifyAdmins($software, $review->authorName(), $status);

        return $dest()->with('review_status', $status === 'approved'
            ? __('review.thanks')
            : __('review.submitted_pending'));
    }

    private function looksSpammy(string $body): bool
    {
        $links = preg_match_all('~https?://|www\.~i', $body) ?: 0;

        return $links >= 3 || mb_strlen($body) > 1800;
    }

    /** Notify moderators of a new review (shows in the admin bell). */
    private function notifyAdmins(Software $software, string $author, string $status): void
    {
        $admins = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'editor', 'moderator']))->get();

        if ($admins->isEmpty()) {
            return;
        }

        $pending = $status !== 'approved';

        $notification = Notification::make()
            ->title(__($pending ? 'admin.notify.review_pending_title' : 'admin.notify.review_title'))
            ->body(__('admin.notify.review_body', ['item' => $software->name, 'name' => $author]))
            ->icon('heroicon-o-star')
            ->status($pending ? 'warning' : 'success')
            ->actions([
                Action::make('view')->url(\App\Filament\Resources\ReviewResource::getUrl('index'))->markAsRead(),
            ])
            ->toDatabase();

        $admins->each(fn (User $admin) => $admin->notifyNow($notification));
    }
}
