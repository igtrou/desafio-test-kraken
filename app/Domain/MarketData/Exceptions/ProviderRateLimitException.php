<?php

namespace App\Domain\MarketData\Exceptions;

use RuntimeException;

/**
 * Excecao lancada quando o provider externo retorna limite de requisicoes.
 */
class ProviderRateLimitException extends RuntimeException
{
    /**
     * Monta a excecao com provider opcional para facilitar rastreabilidade.
     */
    public function __construct(
        public readonly ?string $provider = null,
        string $message = 'The market data provider rate limit was reached.'
    ) {
        parent::__construct($message);
    }
}
