<?php

namespace App\Domain\Contracts;

interface LoginRateLimiterPort
{
    public function tooManyAttempts(string $key, int $maxAttempts): bool;

    public function hit(string $key): void;

    public function clear(string $key): void;

    public function availableIn(string $key): int;
}
