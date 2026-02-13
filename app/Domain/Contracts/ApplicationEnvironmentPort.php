<?php

namespace App\Domain\Contracts;

interface ApplicationEnvironmentPort
{
    public function isTesting(): bool;

    public function isLocalOrTesting(): bool;
}
