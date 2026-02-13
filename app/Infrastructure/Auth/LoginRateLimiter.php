<?php

namespace App\Infrastructure\Auth;

use App\Domain\Contracts\LoginRateLimiterPort;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Encapsulates login throttle operations.
 */
class LoginRateLimiter implements LoginRateLimiterPort
{
    /**
     * Checks if too many attempts were made for a throttle key.
     */
    /**
     * Executa a rotina principal do metodo tooManyAttempts.
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return RateLimiter::tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Registers a failed attempt for the throttle key.
     */
    /**
     * Executa a rotina principal do metodo hit.
     */
    public function hit(string $key): void
    {
        RateLimiter::hit($key);
    }

    /**
     * Clears attempts for the throttle key.
     */
    /**
     * Executa a rotina principal do metodo clear.
     */
    public function clear(string $key): void
    {
        RateLimiter::clear($key);
    }

    /**
     * Returns remaining lockout seconds for the throttle key.
     */
    /**
     * Executa a rotina principal do metodo availableIn.
     */
    public function availableIn(string $key): int
    {
        return RateLimiter::availableIn($key);
    }
}
