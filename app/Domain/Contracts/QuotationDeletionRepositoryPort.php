<?php

namespace App\Domain\Contracts;

interface QuotationDeletionRepositoryPort
{
    public function deleteByIdOrFail(int $quotationId): void;
}
