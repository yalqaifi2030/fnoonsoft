<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Software;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * AI "describe what you need" finder: the visitor writes their need in natural
 * language and Claude recommends the best-matching programs from our catalog.
 */
class AssistantController extends Controller
{
    public function index(): View
    {
        abort_unless((bool) Setting::get('assistant_enabled'), 404);

        return view('assistant');
    }

    public function recommend(Request $request): JsonResponse
    {
        if (! Setting::get('assistant_enabled')) {
            return response()->json(['error' => __('assistant.unavailable')], 503);
        }

        $q = trim($request->string('q')->toString());

        if (mb_strlen($q) < 3) {
            return response()->json(['error' => __('assistant.too_short')], 422);
        }
        $q = mb_substr($q, 0, 500);

        $key = config('services.anthropic.key');
        if (! $key) {
            return response()->json(['error' => __('assistant.unavailable')], 503);
        }

        $locale = app()->getLocale();
        $cacheKey = 'assistant:'.$locale.':'.md5(mb_strtolower($q));

        $data = Cache::remember($cacheKey, now()->addHours(12), function () use ($q, $key, $locale) {
            return $this->ask($q, $key, $locale);
        });

        if ($data === null) {
            Cache::forget($cacheKey); // don't cache failures
            return response()->json(['error' => __('assistant.unavailable')], 502);
        }

        return response()->json($data);
    }

    /** Ask Claude to pick the best programs from our catalog. Returns null on failure. */
    private function ask(string $q, string $key, string $locale): ?array
    {
        // Condensed catalog (one line per program) so the model can match by meaning.
        $catalog = Software::published()->with('category')
            ->get(['id', 'slug', 'name', 'short_description', 'content_type', 'license_type', 'category_id']);

        $lines = $catalog->map(function (Software $s) {
            $cat = $s->category?->name;

            return '- '.$s->slug.' | '.$s->name
                .($cat ? ' | '.$cat : '')
                .' | '.($s->content_type?->label() ?? '')
                .' | '.\Illuminate\Support\Str::limit((string) $s->short_description, 120);
        })->implode("\n");

        $langName = $locale === 'ar' ? 'Arabic' : 'English';
        $prompt = "You are the friendly search assistant for a software-download website. "
            ."A visitor describes what they need; recommend the 3–5 best-matching programs FROM THE CATALOG ONLY. "
            ."Reply by calling the recommend_programs tool. Use the EXACT slug from the catalog. "
            ."Write the intro and each reason in {$langName}, short and helpful. "
            ."If nothing fits well, return an empty recommendations array.\n\n"
            ."VISITOR NEED:\n\"{$q}\"\n\n"
            ."CATALOG (slug | name | category | type | description):\n{$lines}";

        $model = config('services.anthropic.model', 'claude-opus-4-8');

        try {
            $resp = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(45)->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 1024,
                'tools' => [$this->tool()],
                'tool_choice' => ['type' => 'tool', 'name' => 'recommend_programs'],
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);
        } catch (\Throwable $e) {
            Log::warning('[fnoon] assistant request failed', ['msg' => $e->getMessage()]);

            return null;
        }

        if ($resp->failed()) {
            Log::warning('[fnoon] assistant API error', ['status' => $resp->status(), 'body' => mb_substr($resp->body(), 0, 180)]);

            return null;
        }

        $out = null;
        foreach ((array) $resp->json('content', []) as $block) {
            if (($block['type'] ?? '') === 'tool_use') {
                $out = $block['input'] ?? null;
                break;
            }
        }
        if (! is_array($out)) {
            return null;
        }

        // Map the recommended slugs back to real software (preserve the model's order).
        $bySlug = $catalog->keyBy('slug');
        $results = [];
        foreach (($out['recommendations'] ?? []) as $rec) {
            $slug = $rec['slug'] ?? null;
            $s = $slug ? $bySlug->get($slug) : null;
            if (! $s) {
                continue;
            }
            $results[] = [
                'name' => (string) $s->name,
                'category' => $s->category?->name,
                'icon' => $s->icon ? Storage::disk('public')->url($s->icon) : null,
                'url' => route('software.show', $s),
                'reason' => (string) ($rec['reason'] ?? ''),
            ];
            if (count($results) >= 5) {
                break;
            }
        }

        return [
            'intro' => (string) ($out['intro'] ?? ''),
            'results' => $results,
        ];
    }

    private function tool(): array
    {
        return [
            'name' => 'recommend_programs',
            'description' => 'Recommend the best-matching programs for the visitor from the provided catalog.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'intro' => ['type' => 'string', 'description' => 'One short, friendly sentence summarizing the recommendation, in the visitor language.'],
                    'recommendations' => [
                        'type' => 'array',
                        'description' => '3 to 5 programs, best first. Empty if nothing fits.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'slug' => ['type' => 'string', 'description' => 'The EXACT slug copied from the catalog.'],
                                'reason' => ['type' => 'string', 'description' => 'A short reason why it fits the need, in the visitor language.'],
                            ],
                            'required' => ['slug', 'reason'],
                        ],
                    ],
                ],
                'required' => ['intro', 'recommendations'],
            ],
        ];
    }
}
