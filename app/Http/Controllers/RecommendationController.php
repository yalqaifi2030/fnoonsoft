<?php

namespace App\Http\Controllers;

use App\Models\DownloadLog;
use App\Models\Software;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * "Recommended for you" — first-party personalization. Interests come from the
 * visitor's own on-site behaviour: the categories / content types / tags they
 * browse (sent from the browser, gated on analytics consent) plus, for signed-in
 * members, their own download history. No device, file or third-party data is ever
 * touched — this only reads what the visitor did inside this site.
 */
class RecommendationController extends Controller
{
    public function index(Request $request): Response
    {
        $catIds = $this->ints($request->query('cat'), 12);
        $types = $this->strings($request->query('type'), 6);
        $tagIds = $this->ints($request->query('tag'), 12);
        $exclude = $this->strings($request->query('exclude'), 60);

        // Members: blend in their own account history (works even on a new device).
        if ($user = $request->user()) {
            [$mCats, $mTypes] = $this->memberSignals($user);
            $catIds = array_slice(array_values(array_unique([...$catIds, ...$mCats])), 0, 12);
            $types = array_slice(array_values(array_unique([...$types, ...$mTypes])), 0, 6);
        }

        if (! $catIds && ! $types && ! $tagIds) {
            return response()->noContent();   // nothing to personalize on yet
        }

        $candidates = Software::query()->published()
            ->with(['developer', 'category', 'tags'])
            ->withSum('downloadLinks as total_size_bytes', 'size_bytes')
            ->when($exclude, fn ($q) => $q->whereNotIn('slug', $exclude))
            ->where(function ($q) use ($catIds, $types, $tagIds) {
                if ($catIds) {
                    $q->orWhereIn('category_id', $catIds);
                }
                if ($types) {
                    $q->orWhereIn('content_type', $types);
                }
                if ($tagIds) {
                    $q->orWhereHas('tags', fn ($t) => $t->whereIn('tags.id', $tagIds));
                }
            })
            ->limit(60)
            ->get();

        if ($candidates->isEmpty()) {
            return response()->noContent();
        }

        $catSet = array_flip($catIds);
        $typeSet = array_flip($types);
        $tagSet = array_flip($tagIds);

        // Rank by how many of the visitor's interests each item matches
        // (category strongest, then type, then each shared tag), popularity breaks ties.
        $items = $candidates
            ->map(function (Software $s) use ($catSet, $typeSet, $tagSet) {
                $score = 0;
                if ($s->category_id && isset($catSet[$s->category_id])) {
                    $score += 3;
                }
                if (isset($typeSet[$s->content_type->value])) {
                    $score += 2;
                }
                foreach ($s->tags as $tag) {
                    if (isset($tagSet[$tag->id])) {
                        $score++;
                    }
                }

                return ['s' => $s, 'score' => $score];
            })
            ->filter(fn ($x) => $x['score'] > 0)
            ->sortByDesc(fn ($x) => $x['score'] * 1_000_000 + min((int) $x['s']->downloads_count, 999_999))
            ->take(8)
            ->pluck('s')
            ->values();

        if ($items->isEmpty()) {
            return response()->noContent();
        }

        return response()->view('partials.recommended-section', ['items' => $items]);
    }

    /** Derive category + content-type interests from a member's recent downloads. */
    private function memberSignals(User $user): array
    {
        $softwareIds = DownloadLog::where('user_id', $user->id)
            ->latest('created_at')
            ->limit(50)
            ->pluck('software_id')
            ->unique();

        if ($softwareIds->isEmpty()) {
            return [[], []];
        }

        $software = Software::whereIn('id', $softwareIds)->get(['id', 'category_id', 'content_type']);

        return [
            $software->pluck('category_id')->filter()->unique()->values()->all(),
            $software->pluck('content_type')->map(fn ($t) => $t->value)->unique()->values()->all(),
        ];
    }

    /** Parse a comma list into a capped, de-duped list of positive ints. */
    private function ints(?string $raw, int $max): array
    {
        return collect(explode(',', (string) $raw))
            ->map(fn ($v) => (int) trim($v))
            ->filter(fn ($v) => $v > 0)
            ->unique()->take($max)->values()->all();
    }

    /** Parse a comma list into a capped, de-duped list of short strings. */
    private function strings(?string $raw, int $max): array
    {
        return collect(explode(',', (string) $raw))
            ->map(fn ($v) => trim($v))
            ->filter(fn ($v) => $v !== '' && mb_strlen($v) <= 190)
            ->unique()->take($max)->values()->all();
    }
}
