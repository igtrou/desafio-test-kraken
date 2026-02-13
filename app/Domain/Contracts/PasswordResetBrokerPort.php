<?php

namespace App\Domain\Contracts;

interface PasswordResetBrokerPort
{
    public function sendResetLink(string $email): string;

    /**
     * @param  array{
     *     token: string,
     *     email: string,
     *     password: string,
     *     password_confirmation: string
     * }  $payload
     */
    public function reset(array $payload, callable $callback): string;

    public function resetLinkSentStatus(): string;

    public function passwordResetStatus(): string;
}
