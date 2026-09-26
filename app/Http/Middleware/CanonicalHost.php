<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * One canonical address for SEO: https://<APP_URL host>. The site answered on
 * www. and over plain http as full duplicate copies (www even declared itself
 * canonical), splitting Google's ranking signals across three "sites".
 * Production only; GET/HEAD are 301-redirected, other methods pass through.
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction() || ! in_array($request->method(), ['GET', 'HEAD'], true) || $request->is('up')) {
            return $next($request);
        }

        $canonical = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($canonical === '') {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $wrongHost = $host === 'www.'.$canonical;
        $insecure = ! $request->isSecure();

        if (($host === $canonical || $wrongHost) && ($wrongHost || $insecure)) {
            return new \Illuminate\Http\RedirectResponse('https://'.$canonical.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
