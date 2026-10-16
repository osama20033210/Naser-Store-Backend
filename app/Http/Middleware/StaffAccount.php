<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()->account_type === 'staff', 404, 'Not found.');

        return $next($request);
    }
}
