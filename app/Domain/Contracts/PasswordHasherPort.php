<?php

namespace App\Domain\Contracts;

interface PasswordHasherPort
{
    public function make(string $plain): string;

    public function check(string $plain, string $hashed): bool;
}
