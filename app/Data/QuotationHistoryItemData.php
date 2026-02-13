<?php

namespace App\Data;

use Carbon\CarbonInterface;

/**
 * DTO da listagem historica de cotacoes retornada pela API.
 */
class QuotationHistoryItemData
{
    /**
     * Armazena os campos publicos de uma cotacao ja persistida.
     */
    /**
     * Executa a rotina principal do metodo __construct.
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
    ) {}
}
