<?php

namespace App\Domain\Contracts;

use App\Domain\MarketData\Quote;

/**
 * Define o contrato para integracoes externas de dados de mercado.
 */
interface MarketDataProvider
{
    /**
     * Busca a cotacao mais recente para um simbolo e tipo de ativo opcional.
     */
    public function fetch(string $symbol, ?string $requestedAssetType = null): Quote;

    /**
     * Retorna um identificador estavel do provider para resposta e persistencia.
     */
    public function getName(): string;
}
