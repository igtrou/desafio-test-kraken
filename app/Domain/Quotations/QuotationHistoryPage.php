<?php

namespace App\Domain\Quotations;

/**
 * Pagina tipada de historico de cotacoes transportada entre servicos.
 */
class QuotationHistoryPage
{
    /**
     * @param  array<int, QuotationHistoryRecord>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $total,
    ) {}
}
