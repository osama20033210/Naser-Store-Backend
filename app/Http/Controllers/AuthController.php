<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthCookies;
use App\Services\CsrfToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;

class AuthController extends Controller
{
    public function csrf(CsrfToken $csrf, AuthCookies $cookies): JsonResponse
    {
        $token = $csrf->issue();

        return response()->json(['csrf_token' => $token])->withCookie($cookies->csrf($token));
    }

    public function login(LoginRequest $request, AuthCookies $cookies): JsonResponse
    {
        $token = Auth::guard('api')->attempt($request->validated());
        abort_unless($token, 401, 'Invalid credentials.');

        return $this->authenticated($token, $cookies);
    }

    public function me(): UserResource
    {
        return new UserResource(Auth::guard('api')->user());
    }

    public function profile(ProfileRequest $request): UserResource
    {
        $request->user()->update($request->validated());

        return new UserResource($request->user());
    }

    public function refresh(Request $request, AuthCookies $cookies): JsonResponse
    {
        // Deliberately outside auth:api: expired tokens can refresh inside refresh_ttl.
        $token = Cache::lock('jwt-rotation:'.hash('sha256', $request->cookie(config('security.jwt_cookie'))), 10)
            ->block(5, fn () => Auth::guard('api')->refresh());
        Auth::guard('api')->forgetUser()->setToken($token);
        abort_unless(Auth::guard('api')->user(), 401, 'Authentication required.');

        return $this->authenticated($token, $cookies);
    }

    public function logout(Request $request, AuthCookies $cookies): JsonResponse
    {
        // A recently refreshed access token may outlive the absolute refresh window.
        // Revoke it directly first; refresh only when access has already expired.
        try {
            Cache::lock('jwt-rotation:'.hash('sha256', $request->cookie(config('security.jwt_cookie'))), 10)->block(5, function () {
                try {
                    Auth::guard('api')->invalidate();
                } catch (TokenExpiredException) {
                    $replacement = Auth::guard('api')->refresh();
                    Auth::guard('api')->setToken($replacement)->invalidate();
                }
            });
        } catch (JWTException) {
            // Already revoked, invalid, or outside its refresh window: no usable JWT remains.
        }

        return response()->json(['message' => 'Logged out.'])
            ->withCookie($cookies->clearJwt())
            ->withCookie($cookies->clearCsrf());
    }

    private function authenticated(string $token, AuthCookies $cookies): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(Auth::guard('api')->user()),
            'expires_in' => (int) config('jwt.ttl') * 60,
        ])->withCookie($cookies->jwt($token));
    }
}
