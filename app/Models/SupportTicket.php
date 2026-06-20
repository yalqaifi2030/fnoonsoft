<?php

namespace App\Models;

use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id', 'guest_name', 'guest_email', 'subject', 'category', 'priority',
        'status', 'source', 'meta', 'last_reply_at', 'closed_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'closed_at' => 'datetime',
        'meta' => 'array',
    ];

    public const CATEGORIES = ['technical', 'download', 'dmca', 'abuse', 'account', 'upload', 'suggestion', 'other'];

    /** Best display name for the reporter (member or guest). */
    public function reporterName(): string
    {
        return $this->user?->name
            ?: ($this->guest_name ?: ($this->guest_email ?: __('ticket.guest')));
    }

    public function reporterEmail(): ?string
    {
        return $this->user?->email ?: $this->guest_email;
    }

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public const STATUSES = ['open', 'answered', 'closed'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at');
    }

    public function number(): string
    {
        return '#'.(1000 + (int) $this->id);
    }

    public static function statusColor(?string $s): string
    {
        return match ($s) {
            'open' => 'warning',
            'answered' => 'info',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    public static function statusIcon(?string $s): string
    {
        return match ($s) {
            'open' => 'heroicon-m-clock',
            'answered' => 'heroicon-m-chat-bubble-left-right',
            'closed' => 'heroicon-m-check-circle',
            default => 'heroicon-m-clock',
        };
    }

    public static function priorityColor(?string $p): string
    {
        return match ($p) {
            'urgent' => 'danger',
            'high' => 'warning',
            'normal' => 'primary',
            'low' => 'gray',
            default => 'gray',
        };
    }

    /** @param 'category'|'priority'|'status' $kind  @return array<string,string> */
    public static function options(string $kind): array
    {
        [$values, $group] = match ($kind) {
            'category' => [self::CATEGORIES, 'categories'],
            'priority' => [self::PRIORITIES, 'priorities'],
            'status' => [self::STATUSES, 'statuses'],
            default => [[], $kind],
        };

        $out = [];
        foreach ($values as $v) {
            $out[$v] = __("ticket.$group.$v");
        }

        return $out;
    }

    public static function label(string $kind, ?string $value): string
    {
        $group = ['category' => 'categories', 'priority' => 'priorities', 'status' => 'statuses'][$kind] ?? $kind;

        return $value ? __("ticket.$group.$value") : '—';
    }

    /** Notify all staff (e.g. a new ticket or a member reply). */
    public function notifyStaff(string $titleKey): void
    {
        $staff = User::whereHas('roles')->get();

        if ($staff->isEmpty()) {
            return;
        }

        Notification::make()
            ->title(__($titleKey))
            ->body($this->number().' · '.$this->subject)
            ->icon('heroicon-o-lifebuoy')
            ->info()
            ->actions([
                Action::make('view')
                    ->label(__('ticket.notify.open'))
                    ->url(route('filament.admin.resources.support-tickets.view', ['record' => $this->id]))
                    ->markAsRead(),
            ])
            ->sendToDatabase($staff);
    }

    /** Notify the ticket owner (e.g. a staff reply). */
    public function notifyOwner(string $titleKey): void
    {
        if (! $this->user) {
            return;
        }

        Notification::make()
            ->title(__($titleKey))
            ->body($this->number().' · '.$this->subject)
            ->icon('heroicon-o-lifebuoy')
            ->success()
            ->actions([
                Action::make('view')
                    ->label(__('ticket.notify.open'))
                    ->url(route('filament.member.resources.support-tickets.view', ['record' => $this->id]))
                    ->markAsRead(),
            ])
            ->sendToDatabase($this->user);
    }
}
