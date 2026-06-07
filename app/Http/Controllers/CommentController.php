<?php

namespace App\Http\Controllers;

use App\Filament\Resources\CommentResource;
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

        $software->comments()->create([
            'user_id' => $request->user()?->id,
            'author_name' => $data['author_name'],
            'author_email' => $data['author_email'] ?? null,
            'body' => $data['body'],
            'status' => 'approved',
        ]);

        $this->notifyAdmins($software, $data['author_name']);

        return back()->with('comment_status', __('comment.posted'))->withFragment('comments');
    }

    /** Push a database notification (shows in the admin topbar bell) to staff. */
    private function notifyAdmins(Software $software, string $author): void
    {
        $admins = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'editor', 'moderator']))->get();

        if ($admins->isEmpty()) {
            return;
        }

        $notification = Notification::make()
            ->title(__('admin.notify.new_comment_title'))
            ->body(__('admin.notify.new_comment_body', ['item' => $software->name, 'name' => $author]))
            ->icon('heroicon-o-chat-bubble-left-right')
            ->success()
            ->actions([
                Action::make('view')->url(CommentResource::getUrl('index'))->markAsRead(),
            ])
            ->toDatabase();

        // notifyNow (not sendToDatabase) so it persists immediately despite the
        // database queue connection — there's no worker in this setup.
        $admins->each(fn (User $admin) => $admin->notifyNow($notification));
    }
}
