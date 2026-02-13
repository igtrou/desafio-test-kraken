<?php

namespace App\Domain\MarketData;

enum AssetType: string
{
    case Stock = 'stock';
    case Crypto = 'crypto';
    case Currency = 'currency';

    /**
     * Retorna os tipos de ativo suportados para validacao de entrada.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
