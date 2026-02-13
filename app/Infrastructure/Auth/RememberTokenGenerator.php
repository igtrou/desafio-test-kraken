<?php

namespace App\Infrastructure\Auth;

use App\Domain\Contracts\RememberTokenGeneratorPort;
use Illuminate\Support\Str;

/**
 * Generates remember tokens for authentication flows.
 */
class RememberTokenGenerator implements RememberTokenGeneratorPort
{
    /**
     * Generates a random remember token.
     */
    /**
     * Executa a rotina principal do metodo generate.
     */
    public function generate(int $length = 60): string
    {
        return Str::random($length);
    }
}
