@php
    use App\Support\Theme;
    use App\Support\SiteBranding;
    use App\Models\Setting;

    $locale = app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
    $align = $dir === 'rtl' ? 'right' : 'left';

    $primary = Theme::valid(Theme::primary()) ? Theme::primary() : '#006C35';
    $primaryDark = Theme::darken($primary, 0.18);
    $accent = Theme::secondary();

    $site = Setting::text('site_name', config('app.name'));
    $logo = SiteBranding::logo();
    $base = rtrim((string) config('app.url'), '/');

    $social = array_filter([
        'X / Twitter' => Setting::get('social_twitter'),
        'Facebook' => Setting::get('social_facebook'),
        'Instagram' => Setting::get('social_instagram'),
        'YouTube' => Setting::get('social_youtube'),
    ]);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject ?? $site }}</title>
</head>
<body style="margin:0; padding:0; background:#f1f1f4; -webkit-text-size-adjust:100%; font-family:'Tahoma',Arial,Helvetica,sans-serif;">
    {{-- Hidden preheader --}}
    @isset($preheader)
        <div style="display:none; max-height:0; overflow:hidden; opacity:0;">{{ $preheader }}</div>
    @endisset

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f1f4;">
        <tr>
            <td align="center" style="padding:28px 14px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                       style="width:600px; max-width:600px; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 8px 30px -16px rgba(0,0,0,.25);">

                    {{-- Header band --}}
                    <tr>
                        <td bgcolor="{{ $primary }}" align="center"
                            style="background:{{ $primary }}; background:linear-gradient(135deg,{{ $primary }},{{ $primaryDark }}); padding:30px 24px;">
                            @if ($logo)
                                <img src="{{ $logo }}" alt="{{ $site }}" height="40" style="height:40px; max-height:40px; border:0; display:block; margin:0 auto;">
                            @else
                                <span style="color:#ffffff; font-size:24px; font-weight:800; letter-spacing:.5px;">{{ $site }}</span>
                            @endif
                        </td>
                    </tr>
                    {{-- Gold accent line --}}
                    <tr><td style="height:4px; background:{{ $accent }}; line-height:4px; font-size:0;">&nbsp;</td></tr>

                    {{-- Content --}}
                    <tr>
                        <td align="{{ $align }}" style="padding:36px 36px 28px; color:#1a1a1a;">
                            <h1 style="margin:0 0 16px; font-size:22px; font-weight:800; color:#111827;">{{ $heading }}</h1>

                            <div style="font-size:15px; line-height:1.8; color:#4b5563;">{!! $bodyHtml !!}</div>

                            @if (! empty($buttonUrl) && ! empty($buttonLabel))
                                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0;">
                                    <tr>
                                        <td bgcolor="{{ $primary }}" style="border-radius:10px;">
                                            <a href="{{ $buttonUrl }}" target="_blank"
                                               style="display:inline-block; padding:13px 30px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:10px; background:{{ $primary }};">
                                                {{ $buttonLabel }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <p style="font-size:12px; line-height:1.7; color:#9ca3af; margin:0 0 4px;">
                                    {{ __('mailtpl.fallback') }}
                                </p>
                                <p style="font-size:12px; line-height:1.6; word-break:break-all; margin:0;">
                                    <a href="{{ $buttonUrl }}" style="color:{{ $primary }};" dir="ltr">{{ $buttonUrl }}</a>
                                </p>
                            @endif

                            @if (! empty($footer))
                                <div style="margin-top:26px; padding-top:18px; border-top:1px solid #eef0f2; font-size:13px; line-height:1.7; color:#9ca3af;">{!! $footer !!}</div>
                            @endif
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="background:#0b1220; padding:24px; color:#9aa4b2; font-size:12px;">
                            @if ($social)
                                <div style="margin-bottom:10px;">
                                    @foreach ($social as $label => $href)
                                        <a href="{{ $href }}" target="_blank" style="color:#cbd5e1; text-decoration:none; margin:0 6px;">{{ $label }}</a>
                                    @endforeach
                                </div>
                            @endif
                            <div style="color:#6b7280;">© {{ now()->year }} {{ $site }}</div>
                            <div style="margin-top:4px;"><a href="{{ $base }}" style="color:#6b7280; text-decoration:none;">{{ str_replace(['https://','http://'], '', $base) }}</a></div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
