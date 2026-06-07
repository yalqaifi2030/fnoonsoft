<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Software;
use Illuminate\Http\Response;

/**
 * Dynamic XML sitemap for search engines: static pages + every published
 * software, blog article and active category. Served at /sitemap.xml.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];
        $add = function (string $loc, string $priority = '0.6', string $changefreq = 'weekly', ?string $lastmod = null) use (&$urls): void {
            $urls[] = compact('loc', 'priority', 'changefreq', 'lastmod');
        };

        // Static high-value pages.
        $add(route('home'), '1.0', 'daily');
        $add(route('browse'), '0.9', 'daily');
        $add(route('blog.index'), '0.7', 'daily');
        $add(route('learn'), '0.6', 'weekly');
        $add(route('contact'), '0.3', 'monthly');

        // Published software.
        Software::published()->get(['id', 'slug', 'updated_at', 'published_at'])->each(function (Software $s) use ($add): void {
            $add(route('software.show', $s), '0.8', 'weekly', ($s->updated_at ?? $s->published_at)?->toAtomString());
        });

        // Published blog articles.
        Article::published()->get(['id', 'slug', 'updated_at', 'published_at'])->each(function (Article $a) use ($add): void {
            $add(route('blog.show', $a), '0.6', 'weekly', ($a->updated_at ?? $a->published_at)?->toAtomString());
        });

        // Active root categories.
        Category::where('is_active', true)->get(['slug'])->each(function (Category $c) use ($add): void {
            $add(route('browse', ['category' => $c->slug]), '0.5', 'weekly');
        });

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }
}
