<?php

namespace App\Services\Quotations;

/**
 * Resultado tipado da exclusao unitaria de cotacao.
 */
class DeleteSingleQuotationResult
{
    /**
     * Armazena metadados padronizados de sucesso da exclusao unitaria.
     */
    public function __construct(
        public readonly string $message,
        public readonly int $quotationId,
        public readonly int $statusCode,
    ) {}
}
