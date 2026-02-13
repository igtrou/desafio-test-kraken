<?php

namespace App\Domain\Quotations;

enum QuotationInvalidReason: string
{
    case DuplicateQuote = 'duplicate_quote';
    case OutlierPrice = 'outlier_price';
    case NonPositivePrice = 'non_positive_price';

    /**
     * Retorna os motivos de invalidacao aceitos no dominio.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
