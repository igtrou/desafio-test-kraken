<?php

namespace App\Domain\MarketData\Exceptions;

use RuntimeException;

/**
 * Excecao lancada quando o provider externo esta indisponivel.
 */
class ProviderUnavailableException extends RuntimeException
{
    /**
     * Monta a excecao com provider opcional para facilitar diagnostico.
     */
    public function __construct(
        public readonly ?string $provider = null,
        string $message = 'The market data provider is unavailable.'
    ) {
        parent::__construct($message);
    }
}
