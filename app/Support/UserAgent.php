<?php

namespace App\Support;

/**
 * Tiny, dependency-free user-agent parser: enough to classify the browser, OS,
 * device type and bots for visitor analytics. Not exhaustive, but accurate for
 * the browsers/devices that matter.
 */
class UserAgent
{
    /** @return array{browser:string,browser_version:?string,os:string,device:string,is_bot:bool} */
    public static function parse(?string $ua): array
    {
        $u = strtolower((string) $ua);

        $isBot = $u === '' || (bool) preg_match(
            '/bot|crawl|spider|slurp|mediapartners|facebookexternalhit|embedly|quora|pinterest|whatsapp|telegram|discord|googlebot|bingbot|yandex|baidu|duckduck|semrush|ahrefs|mj12|dotbot|petalbot|bytespider|headless|phantom|python-requests|scrapy|curl|wget|go-http|node-fetch|axios|okhttp|java\//',
            $u
        );

        // --- Operating system ---
        $os = 'Unknown';
        if (preg_match('/windows nt 10/', $u)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/windows nt 6\.3/', $u)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/windows nt 6\.1/', $u)) {
            $os = 'Windows 7';
        } elseif (preg_match('/windows/', $u)) {
            $os = 'Windows';
        } elseif (preg_match('/iphone|ipad|ipod/', $u)) {
            $os = 'iOS';
        } elseif (preg_match('/mac os x|macintosh/', $u)) {
            $os = 'macOS';
        } elseif (preg_match('/android/', $u)) {
            $os = 'Android';
        } elseif (preg_match('/cros/', $u)) {
            $os = 'ChromeOS';
        } elseif (preg_match('/linux/', $u)) {
            $os = 'Linux';
        }

        // --- Device type ---
        if ($isBot) {
            $device = 'bot';
        } elseif (preg_match('/ipad|tablet|playbook|silk|(android(?!.*mobile))/', $u)) {
            $device = 'tablet';
        } elseif (preg_match('/mobile|iphone|ipod|windows phone|blackberry|opera mini/', $u)) {
            $device = 'mobile';
        } else {
            $device = 'desktop';
        }

        // --- Browser (order matters: more specific UAs first) ---
        $browser = $isBot ? 'Bot' : 'Unknown';
        $version = null;
        $patterns = [
            'Edge' => '/edg(?:e|ios|a)?\/([\d.]+)/',
            'Opera' => '/(?:opr|opera)\/([\d.]+)/',
            'Samsung Internet' => '/samsungbrowser\/([\d.]+)/',
            'UC Browser' => '/ucbrowser\/([\d.]+)/',
            'Yandex' => '/yabrowser\/([\d.]+)/',
            'Firefox' => '/(?:firefox|fxios)\/([\d.]+)/',
            'Chrome' => '/(?:chrome|crios|chromium)\/([\d.]+)/',
            'Safari' => '/version\/([\d.]+).*safari/',
            'Internet Explorer' => '/(?:msie |rv:)([\d.]+)/',
        ];

        foreach ($patterns as $name => $regex) {
            if (preg_match($regex, $u, $m)) {
                $browser = $name;
                $version = $m[1] ?? null;
                break;
            }
        }

        return [
            'browser' => $browser,
            'browser_version' => $version ? explode('.', $version)[0] : null, // major only
            'os' => $os,
            'device' => $device,
            'is_bot' => $isBot,
        ];
    }
}
