<?php

namespace App\Domain\MarketData\Exceptions;

use RuntimeException;

/**
 * Excecao lancada quando nao existe cotacao para o simbolo solicitado.
 */
class QuoteNotFoundException extends RuntimeException
{
    /**
     * Monta a mensagem de erro, incluindo o simbolo quando disponivel.
     */
    public function __construct(string $symbol = '')
    {
        $message = $symbol !== ''
            ? "Quote not found for symbol [{$symbol}]."
            : 'Quote not found for the requested symbol.';

        parent::__construct($message);
    }
}
