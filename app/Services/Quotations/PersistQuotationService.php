<?php

namespace App\Services\Quotations;

use App\Domain\Contracts\QuotationPersistencePort;
use App\Domain\MarketData\Quote;

/**
 * Persiste uma cotacao no historico garantindo consistencia de ativo e qualidade.
 */
class PersistQuotationService
{
    /**
     * Injeta gateway de persistencia para encapsular Eloquent e transacoes.
     */
    public function __construct(
        private readonly QuotationPersistencePort $persistenceGateway,
    ) {}

    /**
     * Persiste dados de cotacao mantendo regras de qualidade e deduplicacao.
     *
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
    public function handle(Quote $quote, ?string $typeOverride = null): array
    {
        return $this->persistenceGateway->persist($quote, $typeOverride);
    }
}
