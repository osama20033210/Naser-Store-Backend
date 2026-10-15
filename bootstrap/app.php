<?php

use App\Http\Middleware\CookieJwt;
use App\Http\Middleware\CustomerAccount;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\StaffAccount;
use App\Http\Middleware\VerifyCookieCsrf;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Spatie\Permission\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [SecurityHeaders::class]);
        $middleware->prependToPriorityList(AuthenticatesRequests::class, CookieJwt::class);
        $middleware->prependToPriorityList(CookieJwt::class, VerifyCookieCsrf::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, PermissionMiddleware::class);
        $middleware->prependToPriorityList(PermissionMiddleware::class, StaffAccount::class);
        $middleware->alias([
            'cookie.jwt' => CookieJwt::class,
            'csrf.cookie' => VerifyCookieCsrf::class,
            'permission' => PermissionMiddleware::class,
            'customer.account' => CustomerAccount::class,
            'staff.account' => StaffAccount::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));
        $exceptions->render(fn (JWTException $exception, Request $request) => response()->json(['message' => 'Authentication required.'], 401));
        $exceptions->render(function (QueryException $exception, Request $request) {
            if (in_array($exception->errorInfo[0] ?? null, ['23503', '23505'], true)) {
                return response()->json(['message' => 'This record conflicts with existing data or protected history.'], 409);
            }
        });
    })->create();
