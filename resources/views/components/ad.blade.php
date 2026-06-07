@props(['placement' => 'incontent', 'format' => 'auto', 'label' => true])

@php($ads = app(\App\Support\Ads::class))

@if ($ads->showUnit($placement))
    <div {{ $attributes->merge(['class' => 'fc-ad my-6']) }}>
        @if ($label)
            <div class="mb-1 text-center text-[10px] uppercase tracking-widest text-gray-300 dark:text-gray-600">
                {{ __('site.ads.label') }}
            </div>
        @endif
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="{{ $ads->publisherId() }}"
             data-ad-slot="{{ $ads->slot($placement) }}"
             data-ad-format="{{ $format }}"
             data-full-width-responsive="true"></ins>
        <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    </div>
@endif
