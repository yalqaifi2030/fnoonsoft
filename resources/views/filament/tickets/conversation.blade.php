<x-filament-panels::page>
@php
    $ticket = $this->record;
    $isStaff = (bool) (auth()->user()?->isStaff());
    $messages = $ticket->messages()->with('user')->get();
    if (! $isStaff) {
        $messages = $messages->reject(fn ($m) => $m->is_internal);
    }
    $imgExt = ['jpg','jpeg','png','gif','webp','svg'];
    $statusHex = ['open' => '#f59e0b', 'answered' => '#3b82f6', 'closed' => '#6b7280'];
    $prioHex = ['low' => '#6b7280', 'normal' => '#3b82f6', 'high' => '#f59e0b', 'urgent' => '#ef4444'];
    $pill = function ($label, $hex) {
        return '<span style="display:inline-flex;align-items:center;border-radius:9999px;padding:.2rem .6rem;font-size:.7rem;font-weight:700;background:'.$hex.'1f;color:'.$hex.';">'.e($label).'</span>';
    };
@endphp

<div style="margin-inline:auto; width:100%; max-width:48rem;">

    {{-- Ticket meta --}}
    <div class="bg-white dark:bg-gray-900" style="border:1px solid rgba(128,128,128,.16); border-radius:1rem; padding:1.1rem 1.25rem; margin-bottom:1.25rem;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div style="min-width:0;">
                <div class="text-gray-900 dark:text-white" style="font-size:1.05rem; font-weight:800;">{{ $ticket->subject }}</div>
                <div class="text-gray-400" style="font-size:.72rem; margin-top:.25rem;" dir="ltr">
                    {{ $ticket->number() }} · {{ $ticket->created_at->format('Y-m-d') }}@if ($isStaff && $ticket->user) · {{ $ticket->user->name }}@endif
                </div>
            </div>
            <div style="display:flex; gap:.4rem; flex-wrap:wrap;">
                {!! $pill(\App\Models\SupportTicket::label('status', $ticket->status), $statusHex[$ticket->status] ?? '#6b7280') !!}
                {!! $pill(\App\Models\SupportTicket::label('priority', $ticket->priority), $prioHex[$ticket->priority] ?? '#6b7280') !!}
                {!! $pill(\App\Models\SupportTicket::label('category', $ticket->category), '#6b7280') !!}
            </div>
        </div>
    </div>

    {{-- Conversation thread --}}
    <div style="display:flex; flex-direction:column; gap:1rem;">
        @foreach ($messages as $m)
            @php
                $mine = ((bool) $m->is_staff) === $isStaff;
                $bg = $m->is_internal ? '#fff7ed' : ($m->is_staff ? '#ecfdf5' : '#f8fafc');
                $border = $m->is_internal ? '#fdba74' : ($m->is_staff ? '#a7f3d0' : '#e5e7eb');
                $sender = $m->is_staff ? __('ticket.support_team') : ($m->user?->name ?? __('ticket.you'));
                $ext = $m->attachment ? strtolower(pathinfo($m->attachment, PATHINFO_EXTENSION)) : null;
            @endphp
            <div style="display:flex; justify-content:{{ $mine ? 'flex-start' : 'flex-end' }};">
                <div style="max-width:82%; border-radius:1rem; padding:.8rem 1rem; background:{{ $bg }}; border:1px solid {{ $border }};">
                    <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:.4rem;">
                        @if ($m->is_staff)
                            <i class="fa-solid fa-headset" style="color:#006C35; font-size:.78rem;"></i>
                        @endif
                        <span style="font-size:.78rem; font-weight:800; color:#111827;">{{ $sender }}</span>
                        @if ($m->is_internal)
                            <span style="border-radius:9999px; padding:.05rem .45rem; font-size:.6rem; font-weight:700; background:#fdba74; color:#7c2d12;">{{ __('ticket.internal_badge') }}</span>
                        @endif
                        <span style="font-size:.65rem; color:#9ca3af;" dir="ltr">{{ $m->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div style="font-size:.85rem; color:#374151; white-space:pre-wrap; line-height:1.65;">{{ $m->body }}</div>
                    @if ($m->attachment)
                        <div style="margin-top:.65rem;">
                            @if (in_array($ext, $imgExt))
                                <a href="{{ $m->attachmentUrl() }}" target="_blank" rel="noopener">
                                    <img src="{{ $m->attachmentUrl() }}" alt="" style="max-height:13rem; border-radius:.6rem; border:1px solid rgba(0,0,0,.08);">
                                </a>
                            @else
                                <a href="{{ $m->attachmentUrl() }}" target="_blank" rel="noopener"
                                   style="display:inline-flex; align-items:center; gap:.4rem; border-radius:.5rem; background:#fff; border:1px solid #e5e7eb; padding:.4rem .7rem; font-size:.75rem; font-weight:600; color:#006C35;">
                                    <i class="fa-solid fa-paperclip"></i> {{ __('ticket.attachment') }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($ticket->status === 'closed')
        <p class="text-gray-400" style="margin-top:1.25rem; text-align:center; font-size:.78rem;">
            <i class="fa-solid fa-lock"></i> {{ __('ticket.closed_notice') }}
        </p>
    @endif

</div>
</x-filament-panels::page>
