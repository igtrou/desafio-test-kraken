<?php

namespace App\Infrastructure\Auth;

use App\Domain\Auth\UserIdentity;
use App\Domain\Contracts\AuthLifecycleEventsPort;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;

/**
 * Dispatches authentication lifecycle events.
 */
class AuthLifecycleEvents implements AuthLifecycleEventsPort
{
    /**
     * Emits user-registered event.
     */
    /**
     * Executa a rotina principal do metodo dispatchRegistered.
     */
    public function dispatchRegistered(UserIdentity $user): void
    {
        $resolvedUser = $this->resolveUser($user);

        if ($resolvedUser === null) {
            return;
        }

        event(new Registered($resolvedUser));
    }

    /**
     * Emits password-reset event.
     */
    /**
     * Executa a rotina principal do metodo dispatchPasswordReset.
     */
    public function dispatchPasswordReset(UserIdentity $user): void
    {
        $resolvedUser = $this->resolveUser($user);

        if ($resolvedUser === null) {
            return;
        }

        event(new PasswordReset($resolvedUser));
    }

    /**
     * Emits email-verified event.
     */
    /**
     * Executa a rotina principal do metodo dispatchVerified.
     */
    public function dispatchVerified(UserIdentity $user): void
    {
        $resolvedUser = $this->resolveUser($user);

        if ($resolvedUser === null) {
            return;
        }

        event(new Verified($resolvedUser));
    }

    /**
     * Resolve model de usuario para disparar eventos de autenticacao.
     */
    private function resolveUser(UserIdentity $user): ?User
    {
        return User::query()->find($user->id);
    }
}
