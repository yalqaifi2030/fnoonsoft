<x-filament-panels::page>
    {{-- Summary --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl text-lg text-white" style="background:#006C35">
                    <i class="fa-solid fa-box-open"></i>
                </span>
                <div>
                    <div class="text-2xl font-black" dir="ltr">{{ number_format($programs) }}</div>
                    <div class="text-xs text-gray-500">{{ __('member.downloads.programs') }}</div>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl text-lg text-white" style="background:#3b82f6">
                    <i class="fa-solid fa-arrow-down"></i>
                </span>
                <div>
                    <div class="text-2xl font-black" dir="ltr">{{ number_format($totalDownloads) }}</div>
                    <div class="text-xs text-gray-500">{{ __('member.downloads.total') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- History list --}}
    @if ($rows->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-200 bg-white py-16 text-center dark:border-white/10 dark:bg-gray-900">
            <i class="fa-solid fa-clock-rotate-left mb-3 text-4xl text-gray-300"></i>
            <p class="font-bold text-gray-600 dark:text-gray-300">{{ __('member.downloads.empty') }}</p>
            <a href="{{ url('/browse') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold text-white" style="background:#006C35">
                <i class="fa-solid fa-compass"></i> {{ __('member.downloads.browse') }}
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white dark:border-white/10 dark:bg-gray-900">
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($rows as $row)
                    @php($s = $softwares[$row->software_id] ?? null)
                    @continue(! $s)
                    <li class="flex items-center gap-4 p-4 transition hover:bg-gray-50 dark:hover:bg-white/5">
                        @if ($s->icon)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($s->icon) }}" alt="" loading="lazy"
                                 class="h-12 w-12 shrink-0 rounded-xl bg-white object-contain ring-1 ring-gray-100">
                        @else
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white" style="background:#006C35">
                                <i class="fa-solid fa-cube"></i>
                            </span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <a href="{{ route('software.show', $s) }}" target="_blank"
                               class="block truncate font-bold text-gray-800 hover:text-[#006C35] dark:text-gray-100">{{ $s->name }}</a>
                            <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-400">
                                <span><i class="fa-regular fa-clock"></i> {{ \Illuminate\Support\Carbon::parse($row->last_at)->diffForHumans() }}</span>
                                @if ($row->times > 1)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 font-semibold text-gray-500 dark:bg-white/10" dir="ltr">×{{ $row->times }}</span>
                                @endif
                            </div>
                        </div>

                        <a href="{{ route('software.show', $s) }}" target="_blank"
                           class="inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-bold text-white transition" style="background:#006C35">
                            <i class="fa-solid fa-arrow-down"></i> <span class="hidden sm:inline">{{ __('member.downloads.again') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-filament-panels::page>
