<?php

namespace App\Http\Controllers;

use App\Filament\Resources\CommentResource;
use App\Models\Setting;
use App\Models\Software;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Software $software): RedirectResponse
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:80'],
            'author_email' => ['nullable', 'email', 'max:160'],
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            // honeypot: bots fill this, humans never see it
            'website' => ['nullable', 'size:0'],
        ]);

        // Moderation-first: comments wait for admin approval by default. Auto-approve
        // only when the admin opted in AND the comment doesn't look spammy.
        $autoApprove = (bool) Setting::get('comments_auto_approve', false);
        $status = ($autoApprove && ! $this->looksSpammy($data['body'])) ? 'approved' : 'pending';

        $software->comments()->create([
            'user_id' => $request->user()?->id,
            'author_name' => $data['author_name'],
            'author_email' => $data['author_email'] ?? null,
            'body' => $data['body'],
            'status' => $status,
        ]);

        $this->notifyAdmins($software, $data['author_name'], $status);

        $message = $status === 'approved' ? __('comment.posted') : __('comment.submitted_pending');

        return back()->with('comment_status', $message)->withFragment('comments');
    }

    /** Lightweight spam heuristic — link-heavy bodies always go to moderation. */
    private function looksSpammy(string $body): bool
    {
        $links = preg_match_all('~https?://|www\.~i', $body) ?: 0;

        return $links >= 3 || mb_strlen($body) > 1800;
    }

    /** Push a database notification (shows in the admin topbar bell) to staff. */
    private function notifyAdmins(Software $software, string $author, string $status = 'pending'): void
    {
        $admins = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'editor', 'moderator']))->get();

        if ($admins->isEmpty()) {
            return;
        }

        $pending = $status !== 'approved';

        $notification = Notification::make()
            ->title(__($pending ? 'admin.notify.comment_pending_title' : 'admin.notify.new_comment_title'))
            ->body(__('admin.notify.new_comment_body', ['item' => $software->name, 'name' => $author]))
            ->icon('heroicon-o-chat-bubble-left-right')
            ->status($pending ? 'warning' : 'success')
            ->actions([
                Action::make('view')->url(CommentResource::getUrl('index'))->markAsRead(),
            ])
            ->toDatabase();

        // notifyNow (not sendToDatabase) so it persists immediately despite the
        // database queue connection — there's no worker in this setup.
        $admins->each(fn (User $admin) => $admin->notifyNow($notification));
    }
}
