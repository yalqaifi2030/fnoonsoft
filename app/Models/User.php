<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
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
        // Any active user may reach both the admin and upload panels.
        return $this->is_active;
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
