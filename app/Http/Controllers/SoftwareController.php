<?php

namespace App\Http\Controllers;

use App\Models\Software;
use Illuminate\View\View;

class SoftwareController extends Controller
{
    public function show(Software $software): View
    {
        abort_unless($software->status->value === 'published', 404);

        $software->loadMissing([
            'developer', 'category', 'versions', 'screenshots',
            'activeBeforeAfterSlides', 'fileFormats',
            'requirements', 'downloadLinks', 'tags',
            'approvedReviews.user', 'approvedComments.user',
            'addonFor',
        ]);

        $software->increment('views_count');

        // Addons/plugins published for this program (own pages + download links).
        $addons = Software::published()
            ->with(['developer', 'category'])
            ->withSum('downloadLinks as total_size_bytes', 'size_bytes')
            ->where('addon_for_id', $software->id)
            ->orderByDesc('downloads_count')
            ->get();

        $related = Software::published()
            ->with(['developer', 'category'])
            ->withSum('downloadLinks as total_size_bytes', 'size_bytes')
            ->where('category_id', $software->category_id)
            ->whereKeyNot($software->id)
            ->limit(6)
            ->get();

        $fromDeveloper = $software->developer_id
            ? Software::published()
                ->with(['developer', 'category'])
                ->withSum('downloadLinks as total_size_bytes', 'size_bytes')
                ->where('developer_id', $software->developer_id)
                ->whereKeyNot($software->id)
                ->limit(6)
                ->get()
            : collect();

        // Mobile apps get a dedicated, marketing-style landing page.
        $view = $software->content_type === \App\Enums\ContentType::MobileApp ? 'software.app-show' : 'software.show';

        return view($view, compact('software', 'related', 'fromDeveloper', 'addons'));
    }
}
