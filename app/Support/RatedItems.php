<?php

namespace App\Support;

use App\Models\Review;
use App\Models\Software;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * "Has this visitor already rated this item?" — the single source of truth for
 * the rate-before-download gate. Before, the page trusted localStorage while
 * the server checked the session: once the session expired (or the rating POST
 * failed) the gateway auto-redirected to /go and /go bounced back — forever.
 *
 * Remembered in the session + a year-long cookie (encrypted by Laravel, so it
 * can't be forged), and for members also by their stored review.
 */
class RatedItems
{
    private const COOKIE = 'fn_rated';

    private const MAX_ITEMS = 300;

    public static function has(Request $request, Software $software): bool
    {
        if ($request->session()->get('reviewed.'.$software->id)) {
            return true;
        }
        if (in_array($software->id, self::cookieIds($request), true)) {
            return true;
        }
        if ($user = $request->user()) {
            return Review::where('software_id', $software->id)->where('user_id', $user->id)->exists();
        }

        return false;
    }

    public static function remember(Request $request, Software $software): void
    {
        $request->session()->put('reviewed.'.$software->id, true);

        $ids = self::cookieIds($request);
        if (! in_array($software->id, $ids, true)) {
            $ids[] = $software->id;
        }
        $ids = array_slice($ids, -self::MAX_ITEMS);

        Cookie::queue(self::COOKIE, implode(',', $ids), 60 * 24 * 365);
    }

    /** @return array<int, int> */
    private static function cookieIds(Request $request): array
    {
        $raw = (string) $request->cookie(self::COOKIE, '');

        return array_values(array_filter(array_map('intval', explode(',', $raw))));
    }
}
