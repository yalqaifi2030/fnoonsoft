<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetReport;
use App\Models\AssetReview;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Review workflow for member uploads (post-moderation: a file is available
 * right away and queued for a human check). Every decision is written to the
 * append-only asset_reviews history, and the owner is told when a file is
 * rejected (panel bell + email, with the reason).
 */
class FileModeration
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    /** Rejection / report reasons (labels in lang/*\/moderation.php → reason.*). */
    public const REASONS = ['copyright', 'malware', 'illegal', 'adult', 'violence', 'privacy', 'spam', 'other'];

    /** Rejected files are kept this long as evidence, then purged. */
    public const RETENTION_DAYS = 30;

    // --- Intake ----------------------------------------------------------

    /** Initial status for a new asset, decided by who uploaded it. */
    public static function initialStatus(Asset $asset): string
    {
        $owner = $asset->user_id ? User::find($asset->user_id) : null;

        if (! $owner || $owner->isStaff() || $owner->uploads_trusted) {
            return self::APPROVED;
        }

        return self::PENDING;
    }

    /** After an asset is created: history for auto-approvals, a ping to moderators for the queue. */
    public static function afterCreated(Asset $asset): void
    {
        $owner = $asset->user;
        if (! $owner) {
            return;
        }

        if ($asset->moderation_status === self::APPROVED && ! $owner->isStaff()) {
            self::log($asset, 'auto_approved', null, null, null, ['by' => 'trusted_owner']);

            return;
        }

        if ($asset->moderation_status === self::PENDING) {
            // One bell per uploader per 10 minutes — a batch upload isn't 50 pings.
            if (Cache::add('moderation:ping:'.$owner->id, 1, now()->addMinutes(10))) {
                self::notifyModerators(
                    'moderation.notify.new_title',
                    'moderation.notify.new_body',
                    ['name' => $owner->displayName(), 'file' => $asset->original_name],
                    $asset,
                    'warning',
                );
            }
        }
    }

    // --- Decisions --------------------------------------------------------

    public static function approve(Asset $asset, ?User $actor, ?string $note = null): void
    {
        $wasRejected = $asset->moderation_status === self::REJECTED;

        DB::transaction(function () use ($asset, $actor, $note, $wasRejected) {
            $asset->forceFill([
                'moderation_status' => self::APPROVED,
                'reviewed_by' => $actor?->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'rejection_note' => null,
                'is_active' => $wasRejected ? true : $asset->is_active,
            ])->save();

            self::resolveReports($asset, $actor, 'dismissed');
            self::log($asset, $wasRejected ? 'restored' : 'approved', $actor, null, $note);
        });

        if ($wasRejected) {
            self::notifyOwner($asset, 'restored');
        }
    }

    public static function reject(Asset $asset, ?User $actor, string $reason, ?string $note = null, bool $notify = true): void
    {
        $reason = in_array($reason, self::REASONS, true) ? $reason : 'other';

        DB::transaction(function () use ($asset, $actor, $reason, $note) {
            $asset->forceFill([
                'moderation_status' => self::REJECTED,
                'reviewed_by' => $actor?->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
                'rejection_note' => $note,
                'is_active' => false, // the share link stops working right away
            ])->save();

            self::resolveReports($asset, $actor, 'resolved');
            self::log($asset, 'rejected', $actor, $reason, $note);
        });

        if ($notify) {
            self::notifyOwner($asset, 'rejected');
        }
    }

    // --- Owner controls --------------------------------------------------

    public static function setTrusted(User $owner, bool $trusted, ?User $actor): void
    {
        $owner->forceFill(['uploads_trusted' => $trusted])->save();
        self::logOwner($owner, $trusted ? 'owner_trusted' : 'owner_untrusted', $actor);
    }

    public static function ban(User $owner, ?User $actor, ?string $reason = null): void
    {
        $owner->forceFill([
            'uploads_banned_at' => now(),
            'uploads_ban_reason' => $reason,
            'uploads_trusted' => false,
        ])->save();
        self::logOwner($owner, 'owner_banned', $actor, $reason);

        try {
            $owner->notifyNow(Notification::make()
                ->title(__('moderation.notify.banned_title'))
                ->body($reason ?: __('moderation.notify.banned_body'))
                ->icon('heroicon-o-no-symbol')->iconColor('danger')
                ->toDatabase());
        } catch (\Throwable $e) {
            Log::warning('[fnoon] ban notification failed', ['msg' => $e->getMessage()]);
        }
    }

    public static function unban(User $owner, ?User $actor): void
    {
        $owner->forceFill(['uploads_banned_at' => null, 'uploads_ban_reason' => null])->save();
        self::logOwner($owner, 'owner_unbanned', $actor);
    }

    // --- Reports ----------------------------------------------------------

    public static function report(Asset $asset, array $data, ?User $user, string $ip): AssetReport
    {
        $report = DB::transaction(function () use ($asset, $data, $user, $ip) {
            $report = AssetReport::create([
                'asset_id' => $asset->id,
                'reason' => in_array($data['reason'] ?? '', self::REASONS, true) ? $data['reason'] : 'other',
                'message' => $data['message'] ?? null,
                'reporter_email' => $data['email'] ?? $user?->email,
                'user_id' => $user?->id,
                'ip' => $ip,
                'status' => 'open',
            ]);

            $asset->increment('reports_count');
            $asset->increment('open_reports');
            self::log($asset, 'reported', null, $report->reason, $report->message, ['report_id' => $report->id]);

            return $report;
        });

        if (Cache::add('moderation:report-ping:'.$asset->id, 1, now()->addMinutes(30))) {
            self::notifyModerators(
                'moderation.notify.report_title',
                'moderation.notify.report_body',
                ['file' => $asset->original_name, 'reason' => __('moderation.reason.'.$report->reason)],
                $asset,
                'danger',
            );
        }

        return $report;
    }

    private static function resolveReports(Asset $asset, ?User $actor, string $status): void
    {
        $asset->reports()->where('status', 'open')->update([
            'status' => $status,
            'resolved_by' => $actor?->id,
            'resolved_at' => now(),
        ]);
        $asset->forceFill(['open_reports' => 0])->save();
    }

    // --- Housekeeping -----------------------------------------------------

    /** Delete files rejected more than RETENTION_DAYS ago (history is kept). */
    public static function purgeExpired(): int
    {
        $n = 0;
        Asset::where('moderation_status', self::REJECTED)
            ->where('reviewed_at', '<', now()->subDays(self::RETENTION_DAYS))
            ->each(function (Asset $asset) use (&$n) {
                self::log($asset, 'purged');
                $asset->delete(); // model hook removes the stored file + variants
                $n++;
            });

        return $n;
    }

    // --- History ----------------------------------------------------------

    public static function log(Asset $asset, string $action, ?User $actor = null, ?string $reason = null, ?string $note = null, array $meta = []): AssetReview
    {
        return AssetReview::create([
            'asset_id' => $asset->id,
            'asset_name' => $asset->original_name,
            'owner_id' => $asset->user_id,
            'checksum_sha256' => $asset->checksum_sha256,
            'actor_id' => $actor?->id,
            'action' => $action,
            'reason' => $reason,
            'note' => $note,
            'meta' => $meta ?: null,
        ]);
    }

    private static function logOwner(User $owner, string $action, ?User $actor, ?string $note = null): void
    {
        AssetReview::create([
            'owner_id' => $owner->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'note' => $note,
        ]);
    }

    // --- Notifications ----------------------------------------------------

    /** Tell the uploader their file was rejected (bell + email) or restored (bell). */
    public static function notifyOwner(Asset $asset, string $event): void
    {
        $owner = $asset->user;
        if (! $owner || $owner->isStaff()) {
            return;
        }

        $locale = $owner->locale ?: config('app.locale');
        $reason = $asset->rejection_reason ? __('moderation.reason.'.$asset->rejection_reason, [], $locale) : '';

        try {
            $n = Notification::make()
                ->title(__('moderation.notify.'.$event.'_title', ['file' => $asset->original_name], $locale))
                ->body($event === 'rejected'
                    ? __('moderation.notify.rejected_body', ['reason' => $reason], $locale).($asset->rejection_note ? "\n".$asset->rejection_note : '')
                    : __('moderation.notify.restored_body', [], $locale))
                ->icon($event === 'rejected' ? 'heroicon-o-shield-exclamation' : 'heroicon-o-check-badge')
                ->iconColor($event === 'rejected' ? 'danger' : 'success');

            $owner->notifyNow($n->toDatabase());
        } catch (\Throwable $e) {
            Log::warning('[fnoon] moderation bell failed', ['msg' => $e->getMessage()]);
        }

        if ($event === 'rejected') {
            try {
                Mail::to($owner->email)->locale($locale)->send(new \App\Mail\FileRejectedMail($asset, $reason));
            } catch (\Throwable $e) {
                Log::warning('[fnoon] rejection email failed', ['msg' => $e->getMessage()]);
            }
        }
    }

    private static function notifyModerators(string $titleKey, string $bodyKey, array $vars, Asset $asset, string $status): void
    {
        try {
            $moderators = User::permission('moderate files')->get()
                ->merge(User::role('super_admin')->get())
                ->unique('id');

            foreach ($moderators as $m) {
                $m->notifyNow(Notification::make()
                    ->title(__($titleKey, $vars))
                    ->body(__($bodyKey, $vars))
                    ->icon('heroicon-o-shield-check')
                    ->status($status)
                    ->actions([
                        Action::make('review')
                            ->label(__('moderation.action.review'))
                            ->url(\App\Filament\Resources\FileModerationResource::getUrl('view', ['record' => $asset], panel: 'admin'))
                            ->markAsRead(),
                    ])
                    ->toDatabase());
            }
        } catch (\Throwable $e) {
            Log::warning('[fnoon] moderator notification failed', ['msg' => $e->getMessage()]);
        }
    }
}
