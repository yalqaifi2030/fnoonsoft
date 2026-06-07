<?php

namespace App\Http\Controllers;

use App\Enums\ContentType;
use App\Models\Category;
use App\Models\Software;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrowseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Software::query()->published()->with(['developer', 'category']);

        // --- Filters -----------------------------------------------------
        $activeType = null;
        if ($type = $request->string('type')->toString()) {
            $query->where('content_type', $type);
            $activeType = ContentType::tryFrom($type);
        }

        $activeCategory = null;
        if ($categorySlug = $request->string('category')->toString()) {
            $activeCategory = Category::where('slug', $categorySlug)->first();
            if ($activeCategory) {
                $ids = $activeCategory->children()->pluck('id')->push($activeCategory->id);
                $query->whereIn('category_id', $ids);
            }
        }

        if ($os = $request->string('os')->toString()) {
            $query->whereJsonContains('os_support', $os);
        }

        if ($license = $request->string('license')->toString()) {
            $query->where('license_type', $license);
        }

        // --- Sorting -----------------------------------------------------
        match ($request->string('sort')->toString()) {
            'downloads' => $query->orderByDesc('downloads_count'),
            'rating' => $query->orderByDesc('rating_avg'),
            'name' => $query->orderBy('slug'),
            default => $query->latest('published_at'),
        };

        $software = $query->paginate(24)->withQueryString();

        // Counts per type for the tab bar.
        $typeCounts = Software::published()
            ->selectRaw('content_type, COUNT(*) as aggregate')
            ->groupBy('content_type')
            ->pluck('aggregate', 'content_type');

        // Categories relevant to the active type (or all roots).
        $categories = Category::roots()
            ->where('is_active', true)
            ->when($activeType, fn ($q) => $q->where(function ($q) use ($activeType) {
                $q->whereNull('content_type')->orWhere('content_type', $activeType->value);
            }))
            ->orderBy('sort_order')
            ->get();

        return view('browse', [
            'software' => $software,
            'categories' => $categories,
            'types' => ContentType::cases(),
            'typeCounts' => $typeCounts,
            'activeType' => $activeType,
            'activeCategory' => $activeCategory,
            'totalCount' => Software::published()->count(),
            'filters' => $request->only(['type', 'category', 'os', 'license', 'sort']),
        ]);
    }
}
