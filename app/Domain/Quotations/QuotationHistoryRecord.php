<?php

namespace App\Domain\Quotations;

use DateTimeInterface;

/**
 * Item tipado do historico de cotacoes retornado por portas de consulta.
 */
class QuotationHistoryRecord
{
    /**
     * Armazena campos da cotacao normalizados para o fluxo de aplicacao.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $symbol,
        public readonly string $name,
        public readonly string $type,
        public readonly float $price,
        public readonly string $currency,
        public readonly string $source,
        public readonly string $status,
        public readonly ?string $invalidReason,
        public readonly ?DateTimeInterface $invalidatedAt,
        public readonly ?DateTimeInterface $quotedAt,
        public readonly ?DateTimeInterface $createdAt,
    ) {}
}
