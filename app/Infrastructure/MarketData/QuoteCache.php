<?php

namespace App\Infrastructure\MarketData;

use App\Domain\Contracts\QuoteCachePort;
use App\Domain\MarketData\Quote;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Wraps cache access used by market data quote retrieval.
 */
class QuoteCache implements QuoteCachePort
{
    /**
     * Remembers a value until the provided expiration instant.
     *
     * @param  Closure(): Quote  $resolver
     */
    /**
     * Executa a rotina principal do metodo remember.
     */
    public function remember(string $key, DateTimeInterface $expiresAt, Closure $resolver): Quote
    {
        /** @var Quote $quote */
        $quote = Cache::remember($key, $expiresAt, $resolver);

        return $quote;
    }
}
