<?php

namespace App\Domain\Contracts;

use App\Domain\MarketData\Quote;

interface QuotationPersistencePort
{
    /**
     * @return array{
     *     id: int,
     *     symbol: string,
     *     name: string,
     *     type: string,
     *     price: float,
     *     currency: string,
     *     source: string,
     *     status: string,
     *     invalid_reason: string|null,
     *     invalidated_at: \DateTimeInterface|null,
     *     quoted_at: \DateTimeInterface|null,
     *     created_at: \DateTimeInterface|null,
     *     was_recently_created: bool
     * }
     */
    public function persist(Quote $quote, ?string $typeOverride = null): array;
}
