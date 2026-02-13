<?php

namespace App\Domain\MarketData\Exceptions;

use RuntimeException;

/**
 * Excecao lancada quando o simbolo informado nao atende ao formato esperado.
 */
class InvalidSymbolException extends RuntimeException
{
    /**
     * Monta a mensagem de erro contextualizada para simbolo invalido.
     */
    public function __construct(string $symbol = '')
    {
        $message = $symbol !== ''
            ? "Symbol [{$symbol}] is invalid."
            : 'Symbol is invalid.';

        parent::__construct($message);
    }
}
