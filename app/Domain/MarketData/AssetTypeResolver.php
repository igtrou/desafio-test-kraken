<?php

namespace App\Domain\MarketData;

/**
 * Resolve o tipo de ativo a partir de um simbolo quando o tipo nao eh informado.
 */
class AssetTypeResolver
{
    /**
     * @var array<int, string>
     */
    private array $cryptoSymbols;

    /**
     * @var array<int, string>
     */
    private array $currencyCodes;

    /**
     * Injeta listas auxiliares para heuristicas de cripto e pares de moeda.
     *
     * @param  array<int, string>  $cryptoSymbols
     * @param  array<int, string>  $currencyCodes
     */
    public function __construct(array $cryptoSymbols = [], array $currencyCodes = [])
    {
        $this->cryptoSymbols = array_values(array_unique(array_map('strtoupper', $cryptoSymbols)));
        $this->currencyCodes = array_values(array_unique(array_map('strtoupper', $currencyCodes)));
    }

    /**
     * Determina o tipo de ativo com base em heuristicas de simbolo.
     */
    public function resolve(string $symbol): AssetType
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', $symbol));

        if ($normalized === '') {
            return AssetType::Stock;
        }

        if (in_array($normalized, $this->cryptoSymbols, true)) {
            return AssetType::Crypto;
        }

        // Simbolos como BTCUSD sao tratados como cripto quando a base esta na lista cripto.
        if (strlen($normalized) === 6) {
            $baseSymbol = substr($normalized, 0, 3);

            if (in_array($baseSymbol, $this->cryptoSymbols, true)) {
                return AssetType::Crypto;
            }
        }

        // Simbolos alfabeticos de 6 letras sao FX apenas quando as duas moedas sao conhecidas.
        if (strlen($normalized) === 6 && ctype_alpha($normalized)) {
            $baseCurrency = substr($normalized, 0, 3);
            $quoteCurrency = substr($normalized, 3, 3);

            if (
                in_array($baseCurrency, $this->currencyCodes, true) &&
                in_array($quoteCurrency, $this->currencyCodes, true)
            ) {
                return AssetType::Currency;
            }
        }

        return AssetType::Stock;
    }
}
