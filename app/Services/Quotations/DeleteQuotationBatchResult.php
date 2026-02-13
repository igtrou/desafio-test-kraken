<?php

namespace App\Services\Quotations;

/**
 * Resultado tipado da exclusao em lote de cotacoes.
 */
class DeleteQuotationBatchResult
{
    /**
     * Armazena metadados padronizados de sucesso da exclusao em lote.
     */
    public function __construct(
        public readonly string $message,
        public readonly int $deletedCount,
        public readonly int $statusCode,
    ) {}
}
