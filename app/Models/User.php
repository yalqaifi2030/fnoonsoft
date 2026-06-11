<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmailContract
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use MustVerifyEmailTrait;
    use Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'avatar', 'cover', 'bio', 'website',
        'twitter', 'github', 'country', 'locale', 'is_active', 'quota_gb',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Who may sign into a Filament panel.
     * Admin panel: staff roles. Upload panel: anyone who can upload.
     */
    /** Avatar shown in the Filament topbar/profile; falls back to initials when unset. */
    public function getFilamentAvatarUrl(): ?string
    {
        if (blank($this->avatar)) {
            return null;
        }

        // Already an absolute URL (e.g. social login) — use as-is.
        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        return Storage::disk('public')->url($this->avatar);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Member dashboard (/dashboard): only when the feature is enabled.
        // Email verification is enforced separately by the panel's
        // ->emailVerification() middleware.
        if ($panel->getId() === 'member') {
            return (bool) Setting::get('member_uploads_enabled', false);
        }

        // Admin & upload panels are STAFF ONLY (users with an assigned role).
        // Members have no role, so they are denied here — they belong strictly
        // in the member dashboard (/dashboard). This closes the privilege-
        // escalation hole where any registered member could open /admin.
        return $this->isStaff();
    }

    // --- Member storage quota -------------------------------------------

    /** True for staff (any assigned role); members have no role. */
    public function isStaff(): bool
    {
        try {
            return $this->roles()->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Storage quota in bytes — staff are unlimited, members get the configured GB. */
    public function storageQuotaBytes(): int
    {
        if ($this->isStaff()) {
            return PHP_INT_MAX;
        }

        // A per-member quota (quota_gb) overrides the global default.
        $gb = $this->quota_gb !== null
            ? (float) $this->quota_gb
            : (float) (Setting::get('member_quota_gb', 10) ?: 10);

        return (int) round($gb * 1024 * 1024 * 1024);
    }

    public function storageUsedBytes(): int
    {
        return (int) Asset::where('user_id', $this->id)->sum('size_bytes');
    }

    public function storageRemainingBytes(): int
    {
        return max(0, $this->storageQuotaBytes() - $this->storageUsedBytes());
    }

    // --- Public profile --------------------------------------------------

    /** A public avatar URL (stored path or absolute social URL), or null. */
    public function avatarUrl(): ?string
    {
        if (blank($this->avatar)) {
            return null;
        }

        return str_starts_with($this->avatar, 'http')
            ? $this->avatar
            : Storage::disk('public')->url($this->avatar);
    }

    /** A public cover/banner image URL, or null. */
    public function coverUrl(): ?string
    {
        if (blank($this->cover)) {
            return null;
        }

        return str_starts_with($this->cover, 'http')
            ? $this->cover
            : Storage::disk('public')->url($this->cover);
    }

    /** The name shown publicly. */
    public function displayName(): string
    {
        return $this->name ?: ('@'.$this->username);
    }

    /** True once the member has claimed a username (required for the public page). */
    public function hasPublicProfile(): bool
    {
        return filled($this->username);
    }

    /** Link to the public creator page, or null if no username yet. */
    public function publicProfileUrl(): ?string
    {
        return $this->hasPublicProfile() ? route('members.show', $this->username) : null;
    }

    // --- Relationships ---------------------------------------------------

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function software(): HasMany
    {
        return $this->hasMany(Software::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function downloadLogs(): HasMany
    {
        return $this->hasMany(DownloadLog::class);
    }

    public function uploadSessions(): HasMany
    {
        return $this->hasMany(UploadSession::class);
    }
}
