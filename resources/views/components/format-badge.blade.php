@props(['format', 'size' => 'md'])

@php
    $c = $format->badgeColor();
    $pad = $size === 'lg' ? 'px-3 py-2' : 'px-2.5 py-1.5';
@endphp

<span class="inline-flex items-center gap-2 rounded-xl border {{ $pad }} transition"
      style="border-color: {{ $c }}33; background: {{ $c }}0f;"
      title="{{ $format->name }}">
    <span class="rounded-md px-1.5 py-0.5 text-[11px] font-extrabold text-white" style="background: {{ $c }}" dir="ltr">{{ $format->ext() }}</span>
    <span class="text-sm font-medium text-gray-700">{{ $format->name }}</span>
</span>
