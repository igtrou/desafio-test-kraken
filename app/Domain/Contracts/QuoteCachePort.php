<?php

namespace App\Domain\Contracts;

use Closure;
use DateTimeInterface;
use App\Domain\MarketData\Quote;

interface QuoteCachePort
{
    /**
     * @param  Closure(): Quote  $resolver
     */
    public function remember(string $key, DateTimeInterface $expiresAt, Closure $resolver): Quote;
}
