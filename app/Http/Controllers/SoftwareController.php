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
        ]);

        $software->increment('views_count');

        $related = Software::published()
            ->where('category_id', $software->category_id)
            ->whereKeyNot($software->id)
            ->limit(6)
            ->get();

        $fromDeveloper = $software->developer_id
            ? Software::published()
                ->where('developer_id', $software->developer_id)
                ->whereKeyNot($software->id)
                ->limit(6)
                ->get()
            : collect();

        return view('software.show', compact('software', 'related', 'fromDeveloper'));
    }
}
