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
        return '<span style="display:inline-flex;align-items:center;gap:.3rem;border-radius:9999px;padding:.22rem .65rem;font-size:.7rem;font-weight:700;background:'.$hex.'1f;color:'.$hex.';">'.e($label).'</span>';
    };
@endphp

<div style="margin-inline:auto; width:100%; max-width:46rem;">

    {{-- Ticket meta --}}
    <div class="bg-white dark:bg-gray-900" style="border:1px solid rgba(128,128,128,.16); border-radius:1.1rem; padding:1.15rem 1.3rem; margin-bottom:1.5rem; box-shadow:0 6px 18px -14px rgba(0,0,0,.3);">
        <div style="display:flex; align-items:flex-start; gap:.9rem; flex-wrap:wrap;">
            <span style="display:flex; height:2.6rem; width:2.6rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:.85rem; color:#fff; font-size:1.05rem; background:linear-gradient(135deg,#006C35,#00a050);">
                <i class="fa-solid fa-headset"></i>
            </span>
            <div style="min-width:0; flex:1 1 auto;">
                <div class="text-gray-900 dark:text-white" style="font-size:1.05rem; font-weight:800; line-height:1.3;">{{ $ticket->subject }}</div>
                <div class="text-gray-400" style="font-size:.72rem; margin-top:.25rem;" dir="ltr">
                    {{ $ticket->number() }} · {{ $ticket->created_at->format('Y-m-d') }}@if ($isStaff && $ticket->user) · {{ $ticket->user->name }}@elseif ($isStaff && ($ticket->guest_email || $ticket->guest_name)) · {{ $ticket->guest_email ?: $ticket->guest_name }} ({{ __('ticket.guest') }})@endif
                </div>
            </div>
            <div style="display:flex; gap:.4rem; flex-wrap:wrap; align-items:center;">
                {!! $pill(\App\Models\SupportTicket::label('status', $ticket->status), $statusHex[$ticket->status] ?? '#6b7280') !!}
                {!! $pill(\App\Models\SupportTicket::label('priority', $ticket->priority), $prioHex[$ticket->priority] ?? '#6b7280') !!}
                {!! $pill(\App\Models\SupportTicket::label('category', $ticket->category), '#6b7280') !!}
            </div>
        </div>
    </div>

    {{-- Conversation thread --}}
    <div style="display:flex; flex-direction:column; gap:1.25rem;">
        @foreach ($messages as $m)
            @php
                $mine = ((bool) $m->is_staff) === $isStaff;
                $bg = $m->is_internal ? '#fff7ed' : ($m->is_staff ? '#ecfdf5' : '#ffffff');
                $border = $m->is_internal ? '#fdba74' : ($m->is_staff ? '#a7f3d0' : '#e8eaed');
                $sender = $m->is_staff ? __('ticket.support_team') : ($m->user?->name ?? __('ticket.you'));
                $avatar = $m->is_staff ? null : ($m->user?->avatarUrl());
                $ext = $m->attachment ? strtolower(pathinfo($m->attachment, PATHINFO_EXTENSION)) : null;
            @endphp
            <div style="display:flex; justify-content:{{ $mine ? 'flex-start' : 'flex-end' }};">
                <div style="max-width:80%; border-radius:1.1rem; padding:.85rem 1.05rem; background:{{ $bg }}; border:1px solid {{ $border }}; box-shadow:0 4px 14px -10px rgba(0,0,0,.25);">
                    <div style="display:flex; align-items:center; gap:.55rem; margin-bottom:.5rem;">
                        @if ($m->is_staff)
                            <span style="display:inline-flex; height:1.7rem; width:1.7rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:50%; background:#006C35; color:#fff; font-size:.7rem;"><i class="fa-solid fa-headset"></i></span>
                        @elseif ($avatar)
                            <img src="{{ $avatar }}" alt="" style="height:1.7rem; width:1.7rem; flex:0 0 auto; border-radius:50%; object-fit:cover;">
                        @else
                            <span style="display:inline-flex; height:1.7rem; width:1.7rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:50%; background:#e5e7eb; color:#374151; font-size:.72rem; font-weight:800;">{{ mb_substr($sender, 0, 1) }}</span>
                        @endif
                        <span style="font-size:.8rem; font-weight:800; color:#111827;">{{ $sender }}</span>
                        @if ($m->is_internal)
                            <span style="border-radius:9999px; padding:.08rem .5rem; font-size:.6rem; font-weight:700; background:#fdba74; color:#7c2d12;">{{ __('ticket.internal_badge') }}</span>
                        @endif
                        <span style="margin-inline-start:auto; font-size:.65rem; color:#9ca3af; white-space:nowrap;" dir="ltr">{{ $m->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div style="font-size:.88rem; color:#374151; white-space:pre-wrap; line-height:1.7;">{{ $m->body }}</div>
                    @if ($m->attachment)
                        <div style="margin-top:.7rem;">
                            @if (in_array($ext, $imgExt))
                                <a href="{{ $m->attachmentUrl() }}" target="_blank" rel="noopener">
                                    <img src="{{ $m->attachmentUrl() }}" alt="" style="max-height:14rem; max-width:100%; border-radius:.7rem; border:1px solid rgba(0,0,0,.08);">
                                </a>
                            @else
                                <a href="{{ $m->attachmentUrl() }}" target="_blank" rel="noopener"
                                   style="display:inline-flex; align-items:center; gap:.45rem; border-radius:.6rem; background:#f8fafc; border:1px solid #e5e7eb; padding:.5rem .8rem; font-size:.78rem; font-weight:600; color:#006C35;">
                                    <i class="fa-solid fa-paperclip"></i> {{ __('ticket.attachment') }}
                                    <span style="color:#9ca3af; text-transform:uppercase; font-size:.65rem;" dir="ltr">{{ $ext }}</span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($ticket->status === 'closed')
        <div style="margin-top:1.5rem; text-align:center;">
            <span class="text-gray-400" style="display:inline-flex; align-items:center; gap:.4rem; border-radius:9999px; background:rgba(128,128,128,.1); padding:.4rem .9rem; font-size:.75rem;">
                <i class="fa-solid fa-lock"></i> {{ __('ticket.closed_notice') }}
            </span>
        </div>
    @endif

    {{-- Inline reply box --}}
    <div class="bg-white dark:bg-gray-900" style="margin-top:1.5rem; border:1px solid rgba(128,128,128,.16); border-radius:1.1rem; padding:1rem 1.1rem; box-shadow:0 8px 22px -16px rgba(0,0,0,.35);">
        <textarea wire:model="replyBody" rows="3" placeholder="{{ __('ticket.reply_hint') }}"
                  class="text-gray-900 dark:text-white"
                  style="width:100%; resize:vertical; border:1px solid rgba(128,128,128,.22); border-radius:.7rem; padding:.7rem .85rem; font-size:.88rem; background:transparent; outline:none;"></textarea>
        @error('replyBody') <p style="color:#ef4444; font-size:.72rem; margin-top:.35rem;">{{ $message }}</p> @enderror
        @error('replyFile') <p style="color:#ef4444; font-size:.72rem; margin-top:.35rem;">{{ $message }}</p> @enderror

        <div style="display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-top:.8rem; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:1.1rem; flex-wrap:wrap;">
                <label style="display:inline-flex; align-items:center; gap:.4rem; cursor:pointer; font-size:.78rem; color:#6b7280;">
                    <i class="fa-solid fa-paperclip"></i>
                    <span>{{ __('ticket.attachment') }}</span>
                    <input type="file" wire:model="replyFile" style="display:none;">
                </label>
                <span wire:loading wire:target="replyFile" style="font-size:.7rem; color:#9ca3af;">…</span>
                @if ($replyFile)
                    <span style="font-size:.72rem; color:#006C35;"><i class="fa-solid fa-circle-check"></i></span>
                @endif

                @if ($isStaff)
                    <label style="display:inline-flex; align-items:center; gap:.4rem; cursor:pointer; font-size:.78rem; color:#b45309;">
                        <input type="checkbox" wire:model="replyInternal"> {{ __('ticket.internal_note') }}
                    </label>
                @endif
            </div>

            <button type="button" wire:click="submitReply" wire:target="submitReply" wire:loading.attr="disabled"
                    style="display:inline-flex; align-items:center; gap:.45rem; border-radius:.7rem; background:#006C35; color:#fff; padding:.6rem 1.2rem; font-size:.82rem; font-weight:700; border:0; cursor:pointer;">
                <span wire:loading.remove wire:target="submitReply"><i class="fa-solid fa-paper-plane"></i> {{ __('ticket.reply') }}</span>
                <span wire:loading wire:target="submitReply">…</span>
            </button>
        </div>
    </div>

</div>
</x-filament-panels::page>
