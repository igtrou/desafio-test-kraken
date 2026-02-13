<?php

namespace App\Domain\Contracts;

use App\Domain\Auth\IssuedAuthToken;
use App\Domain\Auth\UserAccount;

interface UserRepositoryPort
{
    public function findByEmail(string $email): ?UserAccount;

    public function findById(int $userId): ?UserAccount;

    public function create(string $name, string $email, string $hashedPassword): UserAccount;

    public function issueToken(int $userId, string $deviceName): IssuedAuthToken;

    public function revokeTokenById(?int $tokenId): void;
}
