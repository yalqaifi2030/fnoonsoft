<?php

namespace App\Policies;

use App\Models;
use App\Models\User;

/**
 * One policy for every admin-managed model: maps the model to the Spatie
 * permission a staff member needs to see/change it. Before this, having ANY
 * role opened the whole admin panel (an author could edit roles, reset the
 * super admin's password, read storage secrets…).
 *
 *  • super_admin → everything.
 *  • other staff → only models whose permission their role grants.
 *  • members (no role) → allowed here; the member panel scopes its own
 *    queries to the owner, and members can't enter /admin or /upload at all.
 */
class StaffPermissionPolicy
{
    /** @var array<class-string, string> */
    public const MAP = [
        Models\Software::class => 'manage software',
        Models\Feature::class => 'manage software',
        Models\FileFormat::class => 'manage software',
        Models\Tag::class => 'manage software',
        Models\InteractiveLab::class => 'manage software',
        Models\LearningCategory::class => 'manage software',
        Models\Category::class => 'manage categories',
        Models\Developer::class => 'manage developers',
        Models\Review::class => 'manage reviews',
        Models\Comment::class => 'manage reviews',
        Models\Article::class => 'manage articles',
        Models\Faq::class => 'manage articles',
        Models\Page::class => 'manage pages',
        Models\Banner::class => 'manage pages',
        Models\User::class => 'manage users',
        Models\SupportTicket::class => 'manage support',
        Models\Contact::class => 'manage support',
        Models\ProgramRequest::class => 'manage support',
        Models\NewsletterSubscriber::class => 'manage support',
        Models\BlockedIp::class => 'manage settings',
        Models\SecurityEvent::class => 'manage settings',
        Models\SearchQuery::class => 'manage settings',
        Models\UploadSession::class => 'manage settings',
    ];

    /** Decides every ability up-front, so the per-ability methods never run. */
    public function before(User $user, string $ability, mixed $subject = null): bool
    {
        if (! $user->isStaff()) {
            return true;
        }

        $class = is_object($subject) ? $subject::class : (string) $subject;

        return self::allows($user, self::MAP[$class] ?? 'manage settings');
    }

    public static function allows(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasRole('super_admin')) {
            return true;
        }

        try {
            return $user->hasPermissionTo($permission);
        } catch (\Throwable $e) {
            return false; // permission not seeded yet → deny, never 500
        }
    }

    // Filament only consults the policy for abilities that exist as real methods
    // (method_exists), so each one is declared; before() has already decided.

    public function viewAny(User $user): bool { return false; }

    public function view(User $user): bool { return false; }

    public function create(User $user): bool { return false; }

    public function update(User $user): bool { return false; }

    public function delete(User $user): bool { return false; }

    public function deleteAny(User $user): bool { return false; }

    public function restore(User $user): bool { return false; }

    public function restoreAny(User $user): bool { return false; }

    public function forceDelete(User $user): bool { return false; }

    public function forceDeleteAny(User $user): bool { return false; }

    public function replicate(User $user): bool { return false; }

    public function reorder(User $user): bool { return false; }
}
