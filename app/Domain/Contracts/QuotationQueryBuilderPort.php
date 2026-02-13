<?php

namespace App\Domain\Contracts;

use App\Domain\Quotations\QuotationHistoryPage;

interface QuotationQueryBuilderPort
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): QuotationHistoryPage;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function delete(array $filters): int;
}
