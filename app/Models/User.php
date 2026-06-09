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
        'name', 'email', 'password', 'avatar', 'country', 'locale', 'is_active',
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

        // Admin & upload panels: any active user (unchanged).
        return true;
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

        $gb = (float) (Setting::get('member_quota_gb', 10) ?: 10);

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

    // --- Relationships ---------------------------------------------------

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
