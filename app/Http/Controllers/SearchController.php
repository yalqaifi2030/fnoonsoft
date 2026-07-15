<?php

namespace App\Http\Controllers;

use App\Models\ProgramRequest;
use App\Models\SearchQuery;
use App\Models\Software;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim($request->string('q')->toString());
        $results = $term ? $this->query($term)->paginate(24)->withQueryString() : null;

        if ($term && (int) $request->integer('page', 1) === 1) {
            $this->record($term, $results?->total() ?? 0);
        }

        // Trending (fulfilled) searches to help visitors discover popular programs.
        $trending = SearchQuery::where('results_count', '>', 0)
            ->where('hits', '>', 1)
            ->orderByDesc('hits')
            ->limit(8)
            ->pluck('term');

        return view('search', compact('term', 'results', 'trending'));
    }

    /**
     * A visitor asks us to add a program we don't have yet (from a zero-result
     * search). Lands as an actionable ProgramRequest the staff work through, plus
     * bumps the aggregate demand counter on the search term.
     */
    public function requestProgram(Request $request): JsonResponse
    {
        // Honeypot — a filled hidden field means a bot; pretend success, drop it.
        if ($request->filled('website')) {
            return response()->json(['ok' => true]);
        }

        $data = $request->validate([
            'q' => 'required|string|min:2|max:100',
            'note' => 'nullable|string|max:1000',
            'contact' => 'nullable|string|max:190',
            'website' => 'nullable|string|max:0',
        ]);

        $term = trim($data['q']);
        $note = trim((string) ($data['note'] ?? '')) ?: null;
        $contact = trim((string) ($data['contact'] ?? '')) ?: null;

        // Aggregate demand signal on the search term.
        $sq = SearchQuery::firstOrCreate(['term' => $term], ['results_count' => 0, 'hits' => 0]);
        $sq->increment('request_count');
        $sq->update(['last_searched_at' => now()]);

        // One actionable request per program: repeat asks bump the vote count.
        $req = ProgramRequest::firstOrNew(['term' => Str::limit($term, 190, '')]);
        $isNew = ! $req->exists;

        if ($isNew) {
            $req->fill([
                'votes' => 1,
                'status' => 'new',
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
            ]);
        } else {
            $req->votes = (int) $req->votes + 1;
            // A completed/rejected request that people keep asking for reopens.
            if (in_array($req->status, ['rejected', 'available'], true)) {
                $req->status = 'new';
            }
        }

        // Keep the most recent details/contact a visitor bothered to leave.
        if ($note) {
            $req->note = $note;
        }
        if ($contact) {
            $req->contact = $contact;
        }
        $req->last_requested_at = now();
        $req->save();

        // Ping staff only on a brand-new program (the board's vote count carries the rest).
        if ($isNew) {
            $req->notifyStaff();
        }

        return response()->json(['ok' => true]);
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
            $existing->update(['results_count' => $count, 'last_searched_at' => now()]);
        } else {
            SearchQuery::create(['term' => $term, 'results_count' => $count, 'hits' => 1, 'last_searched_at' => now()]);
        }
    }
}
