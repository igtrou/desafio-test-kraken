<?php

namespace App\Infrastructure\Auth;

use App\Domain\Contracts\PasswordHasherPort;
use Illuminate\Support\Facades\Hash;

/**
 * Encapsulates password hashing and verification routines.
 */
class PasswordHasher implements PasswordHasherPort
{
    /**
     * Hashes a plaintext password.
     */
    /**
     * Executa a rotina principal do metodo make.
     */
    public function make(string $plain): string
    {
        return Hash::make($plain);
    }

    /**
     * Verifies a plaintext value against an existing hash.
     */
    /**
     * Executa a rotina principal do metodo check.
     */
    public function check(string $plain, string $hashed): bool
    {
        return Hash::check($plain, $hashed);
    }
}
