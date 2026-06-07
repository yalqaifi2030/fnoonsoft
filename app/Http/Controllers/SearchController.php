<?php

namespace App\Http\Controllers;

use App\Models\SearchQuery;
use App\Models\Software;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim($request->string('q')->toString());
        $results = $term ? $this->query($term)->paginate(24)->withQueryString() : null;

        if ($term) {
            $this->record($term, $results?->total() ?? 0);
        }

        return view('search', compact('term', 'results'));
    }

    /** Live search endpoint for the hero autocomplete (Alpine/fetch). */
    public function live(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());
        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $results = $this->query($term)->limit(8)->get()->map(fn (Software $s) => [
            'name' => $s->name,
            'slug' => $s->slug,
            'icon' => $s->icon,
            'type' => $s->content_type->value,
            'url' => route('software.show', $s),
        ]);

        return response()->json(['results' => $results]);
    }

    private function query(string $term)
    {
        $like = '%'.$term.'%';

        return Software::query()
            ->published()
            ->with(['developer'])
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            })
            ->orderByDesc('downloads_count');
    }

    private function record(string $term, int $count): void
    {
        $existing = SearchQuery::where('term', $term)->first();
        if ($existing) {
            $existing->increment('hits');
            $existing->update(['results_count' => $count]);
        } else {
            SearchQuery::create(['term' => $term, 'results_count' => $count, 'hits' => 1]);
        }
    }
}
