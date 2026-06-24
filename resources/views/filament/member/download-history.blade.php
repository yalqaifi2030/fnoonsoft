<x-filament-panels::page>
    {{-- Inline styles on purpose: Filament's panel CSS doesn't ship the custom
         Tailwind utilities used here, so we size everything explicitly. --}}

    {{-- Summary --}}
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:20px">
        <div style="display:flex;align-items:center;gap:12px;border:1px solid rgba(0,0,0,.08);border-radius:16px;background:#fff;padding:18px">
            <span style="display:inline-flex;width:44px;height:44px;flex:0 0 44px;align-items:center;justify-content:center;border-radius:12px;background:#006C35;color:#fff;font-size:18px">
                <i class="fa-solid fa-box-open"></i>
            </span>
            <div>
                <div style="font-size:24px;font-weight:800;line-height:1.1" dir="ltr">{{ number_format($programs) }}</div>
                <div style="font-size:12px;color:#6b7280">{{ __('member.downloads.programs') }}</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;border:1px solid rgba(0,0,0,.08);border-radius:16px;background:#fff;padding:18px">
            <span style="display:inline-flex;width:44px;height:44px;flex:0 0 44px;align-items:center;justify-content:center;border-radius:12px;background:#3b82f6;color:#fff;font-size:18px">
                <i class="fa-solid fa-arrow-down"></i>
            </span>
            <div>
                <div style="font-size:24px;font-weight:800;line-height:1.1" dir="ltr">{{ number_format($totalDownloads) }}</div>
                <div style="font-size:12px;color:#6b7280">{{ __('member.downloads.total') }}</div>
            </div>
        </div>
    </div>

    @if ($rows->isEmpty())
        <div style="border:1px dashed #e5e7eb;border-radius:16px;background:#fff;padding:56px 24px;text-align:center">
            <i class="fa-solid fa-clock-rotate-left" style="font-size:34px;color:#d1d5db"></i>
            <p style="margin:12px 0 0;font-weight:700;color:#4b5563">{{ __('member.downloads.empty') }}</p>
            <a href="{{ url('/browse') }}" target="_blank"
               style="display:inline-flex;align-items:center;gap:8px;margin-top:16px;border-radius:12px;background:#006C35;color:#fff;padding:9px 16px;font-size:14px;font-weight:700;text-decoration:none">
                <i class="fa-solid fa-compass"></i> {{ __('member.downloads.browse') }}
            </a>
        </div>
    @else
        <div style="border:1px solid rgba(0,0,0,.08);border-radius:16px;background:#fff;overflow:hidden">
            @foreach ($rows as $row)
                @php($s = $softwares[$row->software_id] ?? null)
                @continue(! $s)
                <div style="display:flex;align-items:center;gap:14px;padding:14px 16px;{{ ! $loop->last ? 'border-bottom:1px solid #f3f4f6' : '' }}">
                    @if ($s->icon)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($s->icon) }}" alt="" loading="lazy"
                             style="width:48px;height:48px;flex:0 0 48px;border-radius:12px;background:#fff;object-fit:contain;border:1px solid #f1f3f5">
                    @else
                        <span style="display:inline-flex;width:48px;height:48px;flex:0 0 48px;align-items:center;justify-content:center;border-radius:12px;background:#006C35;color:#fff">
                            <i class="fa-solid fa-cube"></i>
                        </span>
                    @endif

                    <div style="flex:1 1 auto;min-width:0">
                        <a href="{{ route('software.show', $s) }}" target="_blank"
                           style="display:block;font-weight:700;color:#1f2937;text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $s->name }}</a>
                        <div style="margin-top:3px;font-size:12px;color:#9ca3af">
                            <i class="fa-regular fa-clock"></i> {{ \Illuminate\Support\Carbon::parse($row->last_at)->diffForHumans() }}
                            @if ($row->times > 1)
                                · <span dir="ltr">&times;{{ $row->times }}</span>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('software.show', $s) }}" target="_blank"
                       style="flex:0 0 auto;display:inline-flex;align-items:center;gap:6px;border-radius:12px;background:#006C35;color:#fff;padding:8px 14px;font-size:14px;font-weight:700;text-decoration:none">
                        <i class="fa-solid fa-arrow-down"></i> {{ __('member.downloads.again') }}
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
