<?php

namespace App\Data;

use Carbon\CarbonInterface;

/**
 * DTO interno que carrega o payload da cotacao persistida com codigo de status da resposta.
 */
class StoredQuotationData
{
    /**
     * Armazena dados da cotacao persistida com o status HTTP usado na resposta.
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
        public readonly ?CarbonInterface $invalidatedAt,
        public readonly ?CarbonInterface $quotedAt,
        public readonly ?CarbonInterface $createdAt,
        public readonly int $statusCode
    ) {}
}
