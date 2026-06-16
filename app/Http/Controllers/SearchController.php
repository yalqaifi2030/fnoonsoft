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
        if (mb_strlen($term) < 1) {
            return response()->json(['results' => []]);
        }

        $results = $this->query($term)->limit(10)->get()->map(fn (Software $s) => [
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
        // Match each word separately (AND across words, OR across fields) so
        // "autodesk cad" finds "Autodesk AutoCAD …" — and a single letter works too.
        $words = preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [$term];
        $full = '%'.$term.'%';

        return Software::query()
            ->published()
            ->with(['developer'])
            ->where(function ($outer) use ($words) {
                foreach ($words as $w) {
                    $like = '%'.$w.'%';
                    $outer->where(function ($inner) use ($like) {
                        $inner->where('name', 'like', $like)
                            ->orWhere('short_description', 'like', $like)
                            ->orWhere('slug', 'like', $like);
                    });
                }
            })
            // Relevance: whole phrase in the name first, then in the description.
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 WHEN short_description LIKE ? THEN 1 ELSE 2 END', [$full, $full])
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
