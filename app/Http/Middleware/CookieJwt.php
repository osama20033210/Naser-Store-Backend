<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CookieJwt
{
    /** Accept JWTs only from the host-only HttpOnly cookie, never URLs or request bodies. */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie(config('security.jwt_cookie'));
        abort_unless(is_string($token) && $token !== '', 401, 'Authentication required.');
        Auth::guard('api')->setToken($token);

        return $next($request);
    }
}
