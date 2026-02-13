<?php

namespace App\Infrastructure\Auth;

use App\Domain\Contracts\WebSessionAuthenticatorPort;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Wraps web guard authentication operations.
 */
class WebSessionAuthenticator implements WebSessionAuthenticatorPort
{
    /**
     * Attempts web authentication using email/password credentials.
     */
    /**
     * Executa a rotina principal do metodo attempt.
     */
    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        return Auth::attempt([
            'email' => $email,
            'password' => $password,
        ], $remember);
    }

    /**
     * Logs a user into the default web guard.
     */
    /**
     * Executa a rotina principal do metodo loginById.
     */
    public function loginById(int $userId): void
    {
        $user = User::query()->find($userId);

        if (! $user instanceof Authenticatable) {
            throw new RuntimeException("Cannot login unknown user [{$userId}].");
        }

        Auth::login($user);
    }

    /**
     * Logs out current user from web guard.
     */
    /**
     * Executa a rotina principal do metodo logoutWeb.
     */
    public function logoutWeb(): void
    {
        Auth::guard('web')->logout();
    }
}
