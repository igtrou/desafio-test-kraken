<?php

namespace App\Infrastructure\Quotations;

use App\Domain\Contracts\QuotationDeletionRepositoryPort;
use App\Models\Quotation;

/**
 * Encapsula operacoes de exclusao de cotacoes persistidas.
 */
class QuotationDeletionRepository implements QuotationDeletionRepositoryPort
{
    /**
     * Exclui logicamente uma cotacao por id.
     */
    public function deleteByIdOrFail(int $quotationId): void
    {
        $quotation = Quotation::query()->findOrFail($quotationId);
        $quotation->delete();
    }
}
