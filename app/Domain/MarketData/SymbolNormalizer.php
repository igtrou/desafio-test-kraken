<?php

namespace App\Domain\MarketData;

use App\Domain\MarketData\Exceptions\InvalidSymbolException;

/**
 * Normaliza simbolos de entrada para um formato estavel usado internamente.
 */
class SymbolNormalizer
{
    private const ALLOWED_PATTERN = '/^[A-Z0-9.\-\/_]+$/';

    /**
     * Valida e converte o simbolo para o padrao aceito pela aplicacao.
     *
     * @throws InvalidSymbolException
     */
    public function normalize(string $symbol): string
    {
        $candidate = strtoupper(trim($symbol));

        if ($candidate === '' || preg_match(self::ALLOWED_PATTERN, $candidate) !== 1) {
            throw new InvalidSymbolException($symbol);
        }

        if (preg_match('/^[A-Z]{3}[-\/_]?[A-Z]{3}$/', $candidate) === 1) {
            // Pares de moeda sao persistidos sem separadores (ex.: USD-BRL -> USDBRL).
            return (string) preg_replace('/[^A-Z]/', '', $candidate);
        }

        return str_replace('_', '-', $candidate);
    }
}
