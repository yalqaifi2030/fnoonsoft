<?php

namespace App\Http\Controllers;

use App\Enums\ContentType;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Feature;
use App\Models\Software;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $base = Software::query()->published()->with(['developer', 'category']);

        // Published count per content type (for the "browse by type" cards).
        $typeCounts = Software::published()
            ->selectRaw('content_type, COUNT(*) as aggregate')
            ->groupBy('content_type')
            ->pluck('aggregate', 'content_type');

        // Editor's-choice spotlight: admin-picked software (else auto), unless disabled.
        $spotlight = null;
        if (\App\Support\Spotlight::enabled()) {
            $picked = \App\Support\Spotlight::softwareId();
            $spotlight = ($picked ? (clone $base)->whereKey($picked)->first() : null)
                ?? (clone $base)->featured()->orderByDesc('downloads_count')->first()
                ?? (clone $base)->orderByDesc('downloads_count')->first();
        }

        $data = [
            'spotlight' => $spotlight,
            'mostDownloaded' => (clone $base)->withSum('downloadLinks as total_size_bytes', 'size_bytes')->orderByDesc('downloads_count')->limit(8)->get(),
            'recentlyAdded' => (clone $base)->latest('published_at')->limit(8)->get(),
            'editorChoice' => (clone $base)->where('is_editor_choice', true)->limit(8)->get(),
            'featured' => (clone $base)->featured()->limit(8)->get(),
            'mobileApps' => (clone $base)->where('content_type', ContentType::MobileApp->value)->latest('published_at')->limit(8)->get(),
            'categories' => Category::roots()->where('is_active', true)->orderBy('sort_order')->limit(12)->get(),
            'banners' => Banner::active()->where('position', 'home_top')->orderBy('sort_order')->get(),
            'features' => Feature::active()->orderBy('sort_order')->get(),
            'types' => ContentType::cases(),
            'typeCounts' => $typeCounts,
            'stats' => [
                'software' => Software::published()->count(),
                'downloads' => (int) Software::published()->sum('downloads_count'),
                'developers' => \App\Models\Developer::count(),
            ],
        ];

        return view('home', $data);
    }
}
