<?php

namespace App\Models;

use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'term', 'votes', 'note', 'contact', 'status', 'user_id', 'ip', 'last_requested_at',
    ];

    protected function casts(): array
    {
        return ['last_requested_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Ping all staff that a visitor asked for a program we don't have yet. */
    public function notifyStaff(): void
    {
        $staff = User::whereHas('roles')->get();

        if ($staff->isEmpty()) {
            return;
        }

        Notification::make()
            ->title(__('prog_requests.notify_title'))
            ->body('«'.$this->term.'»'.($this->contact ? ' · '.$this->contact : ''))
            ->icon('heroicon-o-inbox-arrow-down')
            ->warning()
            ->actions([
                Action::make('view')
                    ->label(__('prog_requests.notify_open'))
                    ->url(route('filament.admin.resources.program-requests.index'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($staff);
    }
}
