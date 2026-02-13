<?php

namespace App\Domain\Contracts;

interface RememberTokenGeneratorPort
{
    public function generate(int $length = 60): string;
}
