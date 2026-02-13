<?php

namespace App\Domain\Contracts;

interface WebSessionStatePort
{
    public function regenerate(): void;

    public function invalidate(): void;

    public function regenerateToken(): void;
}
