<?php

namespace App\Infrastructure\Auth;

use App\Domain\Contracts\PasswordResetBrokerPort;
use Illuminate\Support\Facades\Password;

/**
 * Wraps password broker operations.
 */
class PasswordResetBroker implements PasswordResetBrokerPort
{
    /**
     * Sends reset link to a given email.
     */
    /**
     * Executa a rotina principal do metodo sendResetLink.
     */
    public function sendResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    /**
     * Performs password reset with broker callback.
     *
     * @param  array{
     *     token: string,
     *     email: string,
     *     password: string,
     *     password_confirmation: string
     * }  $payload
     */
    /**
     * Executa a rotina principal do metodo reset.
     */
    public function reset(array $payload, callable $callback): string
    {
        return Password::reset($payload, $callback);
    }

    /**
     * Returns broker status for successful reset-link dispatch.
     */
    /**
     * Executa a rotina principal do metodo resetLinkSentStatus.
     */
    public function resetLinkSentStatus(): string
    {
        return Password::RESET_LINK_SENT;
    }

    /**
     * Returns broker status for successful password reset.
     */
    /**
     * Executa a rotina principal do metodo passwordResetStatus.
     */
    public function passwordResetStatus(): string
    {
        return Password::PASSWORD_RESET;
    }
}
