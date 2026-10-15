<?php

namespace App\Http\Middleware;

use App\Services\CsrfToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCookieCsrf
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            $cookie = $request->cookie(config('security.csrf_cookie'));
            $header = $request->header('X-CSRF-TOKEN');
            abort_unless(
                $request->header('Origin') === config('security.frontend_origin')
                && is_string($cookie) && is_string($header)
                && hash_equals($cookie, $header) && app(CsrfToken::class)->valid($cookie),
                419, 'CSRF validation failed. Reload and try again.'
            );
        }

        return $next($request);
    }
}
