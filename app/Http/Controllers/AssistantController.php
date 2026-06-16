<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class AssistantController extends Controller
{
    /** Public chat endpoint for the site widget. */
    public function chat(Request $request): JsonResponse
    {
        if (! AssistantService::isLive()) {
            return response()->json(['error' => 'disabled'], 403);
        }

        $data = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:30'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:4000'],
        ]);

        // Per-visitor daily cap (cost control). 0 = unlimited.
        $limit = (int) Setting::get('assistant_daily_limit', 30);
        if ($limit > 0) {
            $key = 'assistant:limit:'.now()->toDateString().':'.$this->visitor($request);
            $used = (int) Cache::get($key, 0);
            if ($used >= $limit) {
                return response()->json([
                    'error' => 'limit',
                    'reply' => __('assistant.limit_reached'),
                ], 429);
            }
            Cache::put($key, $used + 1, now()->endOfDay());
        }

        $result = AssistantService::fromSettings()->reply($data['messages']);

        if (! empty($result['error'])) {
            return response()->json([
                'error' => $result['error'],
                'reply' => __('assistant.error'),
            ], 502);
        }

        return response()->json([
            'reply' => $result['reply'],
            'recommendations' => $result['recommendations'],
        ]);
    }

    /** Stable per-visitor identity: signed-in user, else session, else IP. */
    protected function visitor(Request $request): string
    {
        if ($id = optional($request->user())->id) {
            return 'u'.$id;
        }

        return 's'.substr(sha1($request->session()->getId().'|'.$request->ip()), 0, 16);
    }
}
