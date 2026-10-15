<?php

namespace App\Services;

class CsrfToken
{
    /** Signed double-submit tokens prevent injection of attacker-chosen cookie values. */
    public function issue(): string
    {
        $nonce = bin2hex(random_bytes(32));

        return $nonce.'.'.hash_hmac('sha256', $nonce, $this->key());
    }

    public function valid(string $token): bool
    {
        $parts = explode('.', $token);

        return count($parts) === 2 && strlen($parts[0]) === 64
            && hash_equals(hash_hmac('sha256', $parts[0], $this->key()), $parts[1]);
    }

    private function key(): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            throw new \LogicException('APP_KEY must be configured.');
        }

        return $key;
    }
}
