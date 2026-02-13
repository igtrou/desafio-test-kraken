<?php

namespace App\Domain\Contracts;

interface ConfigCachePort
{
    public function clear(): void;
}
