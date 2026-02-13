<?php

namespace App\Domain\Contracts;

interface WebSessionAuthenticatorPort
{
    public function attempt(string $email, string $password, bool $remember = false): bool;

    public function loginById(int $userId): void;

    public function logoutWeb(): void;
}
