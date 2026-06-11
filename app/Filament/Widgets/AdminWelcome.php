<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactResource;
use App\Filament\Resources\ReviewResource;
use App\Filament\Resources\SoftwareResource;
use App\Filament\Resources\SupportTicketResource;
use App\Models\Contact;
use App\Models\Review;
use App\Models\SupportTicket;
use Filament\Widgets\Widget;

class AdminWelcome extends Widget
{
    protected static string $view = 'filament.widgets.admin-welcome';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        $user = auth()->user();

        $attention = array_values(array_filter([
            [
                'count' => Review::where('status', 'pending')->count(),
                'label' => __('dashboard.stat.reviews_pending'),
                'icon' => 'fa-star',
                'url' => ReviewResource::getUrl(),
            ],
            [
                'count' => Contact::where('is_read', false)->count(),
                'label' => __('dashboard.stat.messages'),
                'icon' => 'fa-envelope',
                'url' => ContactResource::getUrl(),
            ],
            [
                'count' => SupportTicket::where('status', 'open')->count(),
                'label' => __('ticket.nav'),
                'icon' => 'fa-life-ring',
                'url' => SupportTicketResource::getUrl(),
            ],
        ], fn ($a) => $a['count'] > 0));

        return [
            'name' => $user?->name,
            'roles' => $user?->getRoleNames()->implode(' · '),
            'date' => now()->translatedFormat('l، j F Y'),
            'attention' => $attention,
            'actions' => [
                ['label' => __('dashboard.quick.new_content'), 'icon' => 'fa-plus', 'url' => SoftwareResource::getUrl('create')],
                ['label' => __('ticket.nav'), 'icon' => 'fa-headset', 'url' => SupportTicketResource::getUrl()],
            ],
        ];
    }
}
