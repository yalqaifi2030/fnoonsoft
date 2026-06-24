{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}{{-- split <? and ?> so Blade's tokenizer doesn't choke when short_open_tag is On --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($urls as $u)
    <url>
        <loc>{{ $u['loc'] }}</loc>
@if (! empty($u['lastmod']))
        <lastmod>{{ $u['lastmod'] }}</lastmod>
@endif
        <changefreq>{{ $u['changefreq'] }}</changefreq>
        <priority>{{ $u['priority'] }}</priority>
@foreach ($u['images'] ?? [] as $img)
        <image:image><image:loc>{{ $img }}</image:loc></image:image>
@endforeach
    </url>
@endforeach
</urlset>
