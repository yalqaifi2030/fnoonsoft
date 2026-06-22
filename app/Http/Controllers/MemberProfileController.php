<?php

namespace App\Http\Controllers;

use App\Models\User;

/**
 * Public "creator" page at /u/{username}: the member's avatar, bio and links,
 * with a grid of their public (active, unexpired) shared files. Read-only.
 */
class MemberProfileController extends Controller
{
    public function show(User $user)
    {
        abort_unless($user->is_active && filled($user->username), 404);

        // Files are PRIVATE by default — only the owner sees them, unless the
        // member opted in to a public showcase.
        $isOwner = auth()->check() && auth()->id() === $user->id;
        $showFiles = $isOwner || (bool) $user->show_files_publicly;

        $assets = $showFiles
            ? $user->assets()
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->latest()
                ->get()
            : collect();

        $stats = [
            'files' => $assets->count(),
            'downloads' => (int) $assets->sum('downloads_count'),
            'views' => (int) $assets->sum('views_count'),
        ];

        return view('members.show', compact('user', 'assets', 'stats', 'showFiles', 'isOwner'));
    }
}
