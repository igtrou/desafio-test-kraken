<?php

namespace App\Data;

/**
 * DTO imutavel que consolida os resultados da execucao de coleta de cotacoes.
 */
class CollectedQuotationsResultData
{
    /**
     * Consolida contadores de execucao e resultados por simbolo.
     *
     * @param  array<int, CollectedQuotationItemData>  $items
     */
    public function __construct(
        public readonly int $total,
        public readonly int $success,
        public readonly int $failed,
        public readonly array $items,
        public readonly bool $canceled = false
    ) {}

    /**
     * @return array{
     *     total: int,
     *     success: int,
     *     failed: int,
     *     items: array<int, array{
     *         symbol: string,
     *         status: 'ok'|'error',
     *         source?: string,
     *         price?: float,
     *         quotation_id?: int|null,
     *         message?: string
     *     }>,
     *     canceled: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'success' => $this->success,
            'failed' => $this->failed,
            'items' => array_map(
                static fn (CollectedQuotationItemData $collectedItem): array => $collectedItem->toArray(),
                $this->items
            ),
            'canceled' => $this->canceled,
        ];
    }
}
