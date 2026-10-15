<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()->account_type === 'customer', 403, 'A customer account is required.');
        $request->user()->loadMissing('customer');
        abort_unless($request->user()->customer, 403, 'Your customer profile is unavailable. Contact the store.');

        return $next($request);
    }
}
