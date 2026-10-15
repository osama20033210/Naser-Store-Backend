<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Cookie;

class AuthCookies
{
    public function jwt(string $token): Cookie
    {
        // Keep an expired access JWT available for the package's bounded refresh flow.
        return $this->cookie(config('security.jwt_cookie'), $token, (int) config('jwt.refresh_ttl'));
    }

    public function csrf(string $token): Cookie
    {
        return $this->cookie(config('security.csrf_cookie'), $token, (int) config('jwt.refresh_ttl'));
    }

    public function clearJwt(): Cookie
    {
        return $this->cookie(config('security.jwt_cookie'), '', -1);
    }

    public function clearCsrf(): Cookie
    {
        return $this->cookie(config('security.csrf_cookie'), '', -1);
    }

    private function cookie(string $name, string $value, int $minutes): Cookie
    {
        return new Cookie($name, $value, now()->addMinutes($minutes), '/', null, true, true, false, Cookie::SAMESITE_LAX);
    }
}
