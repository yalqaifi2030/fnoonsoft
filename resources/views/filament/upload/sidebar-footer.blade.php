<div class="fc-side-footer">
    <a href="{{ url('/') }}" target="_blank" rel="noopener">
        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-5 w-5 shrink-0" />
        <span>{{ app()->getLocale() === 'ar' ? 'زيارة الموقع' : 'Visit site' }}</span>
    </a>
</div>
