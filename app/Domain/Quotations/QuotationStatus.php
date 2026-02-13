<?php

namespace App\Domain\Quotations;

enum QuotationStatus: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';

    /**
     * Retorna os status de cotacao aceitos no dominio.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
