<?php

namespace App\Support;

use App\Models\Article;
use App\Models\InteractiveLab;
use App\Models\Page;
use App\Models\Software;
use Illuminate\Support\Str;

/**
 * Turns a stored visit path (e.g. "/software/autodesk-alias-2026") into a
 * human page name (the content's actual title) for the analytics screens.
 * Resolves in batches so a list of paths costs only a few queries.
 */
class PageTitle
{
    /** @return array<string,string> normalised path => title */
    public static function map(iterable $paths): array
    {
        $set = [];
        foreach ($paths as $p) {
            $set[self::norm($p)] = true;
        }
        $paths = array_keys($set);

        $software = [];
        $blog = [];
        $labs = [];
        $pages = [];

        foreach ($paths as $p) {
            $seg = explode('/', ltrim($p, '/'));
            $count = count($seg);

            if ($seg[0] === 'software' && $count >= 2) {
                $software[$seg[1]][] = $p;
            } elseif ($seg[0] === 'blog' && $count >= 2) {
                $blog[$seg[1]][] = $p;
            } elseif ($seg[0] === 'learn' && ($seg[1] ?? '') === 'lab' && $count >= 3) {
                $labs[$seg[2]][] = $p;
            } elseif ($count === 1 && $seg[0] !== '' && ! in_array($seg[0], self::$reserved, true)) {
                $pages[$seg[0]][] = $p;
            }
        }

        $out = [];
        self::lookup($out, Software::class, 'name', $software);
        self::lookup($out, Article::class, 'title', $blog);
        self::lookup($out, InteractiveLab::class, 'title', $labs);
        self::lookup($out, Page::class, 'title', $pages);

        foreach ($paths as $p) {
            if (! isset($out[$p])) {
                $out[$p] = self::fallback($p);
            }
        }

        return $out;
    }

    public static function for(string $path): string
    {
        $path = self::norm($path);

        return self::map([$path])[$path] ?? $path;
    }

    /**
     * @param  array<string,string>  $out
     * @param  array<string,array<int,string>>  $bySlug
     */
    private static function lookup(array &$out, string $model, string $attr, array $bySlug): void
    {
        if (! $bySlug) {
            return;
        }

        foreach ($model::whereIn('slug', array_keys($bySlug))->get(['slug', $attr]) as $record) {
            $title = trim((string) $record->{$attr});
            if ($title === '') {
                continue;
            }
            foreach ($bySlug[$record->slug] ?? [] as $path) {
                $out[$path] = $title;
            }
        }
    }

    private static function fallback(string $path): string
    {
        if ($path === '/' || $path === '') {
            return __('analytics.home');
        }

        $statics = [
            '/browse' => __('site.nav.browse'),
            '/blog' => __('site.nav.blog'),
            '/contact' => __('site.nav.contact'),
            '/learn' => __('analytics.page.learn'),
            '/learn/videos' => __('analytics.page.learn_videos'),
            '/search' => __('analytics.page.search'),
        ];

        if (isset($statics[$path])) {
            return $statics[$path];
        }

        $segments = explode('/', ltrim($path, '/'));

        return Str::headline(str_replace('-', ' ', (string) end($segments)));
    }

    private static function norm(string $path): string
    {
        $path = '/'.ltrim(trim($path), '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    /** First path segments that are NOT standalone CMS pages. */
    private static array $reserved = [
        'browse', 'blog', 'learn', 'search', 'contact', 'software',
        'd', 'u', 'go', 'admin', 'upload', 'dashboard',
    ];
}
