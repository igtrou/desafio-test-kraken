<?php

namespace App\Data;

/**
 * DTO imutavel que representa metricas de exclusao em lote de cotacoes.
 */
class DeleteQuotationBatchData
{
    /**
     * Armazena quantas cotacoes foram removidas em uma operacao em lote.
     */
    public function __construct(
        public readonly int $deletedCount
    ) {}

    /**
     * @return array{deleted_count: int}
     */
    public function toArray(): array
    {
        return [
            'deleted_count' => $this->deletedCount,
        ];
    }
}
