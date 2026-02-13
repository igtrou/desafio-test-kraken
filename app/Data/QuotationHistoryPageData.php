<?php

namespace App\Data;

/**
 * DTO imutavel de output para historico paginado de cotacoes.
 */
class QuotationHistoryPageData
{
    /**
     * @param  array<int, QuotationHistoryItemData>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $total,
    ) {}
}
