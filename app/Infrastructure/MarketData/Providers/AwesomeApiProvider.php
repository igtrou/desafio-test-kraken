<?php

namespace App\Infrastructure\MarketData\Providers;

use App\Domain\MarketData\AssetTypeResolver;
use App\Domain\MarketData\AssetType;
use App\Domain\MarketData\Quote;
use App\Domain\MarketData\Exceptions\ProviderRateLimitException;
use App\Domain\MarketData\Exceptions\ProviderUnavailableException;
use App\Domain\MarketData\Exceptions\QuoteNotFoundException;
use App\Domain\Contracts\MarketDataProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

/**
 * Adapter da AwesomeAPI para acoes, cripto e pares FX.
 */
class AwesomeApiProvider implements MarketDataProvider
{
    private const DEFAULT_BASE_URI = 'https://economia.awesomeapi.com.br/json/last';

    private readonly AssetTypeResolver $assetTypeResolver;

    /**
     * Injeta configuracao do provider e resolvedor de tipo de ativo.
     */
    public function __construct(
        private readonly array $config = [],
        ?AssetTypeResolver $assetTypeResolver = null
    ) {
        $this->assetTypeResolver = $assetTypeResolver ?? new AssetTypeResolver(
            config('market-data.crypto_symbols', []),
            config('market-data.currency_codes', [])
        );
    }

    /**
     * @throws QuoteNotFoundException
     * @throws ProviderUnavailableException
     * @throws ProviderRateLimitException
     */
    public function fetch(string $symbol, ?string $requestedAssetType = null): Quote
    {
        $normalizedSymbol = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', $symbol));
        $quoteCurrency = strtoupper($this->config['quote_currency'] ?? 'USD');
        $resolvedAssetType = $this->resolveType($normalizedSymbol, $requestedAssetType);
        $pair = $this->buildPair($normalizedSymbol, $resolvedAssetType);

        $response = Http::baseUrl($this->config['base_uri'] ?? self::DEFAULT_BASE_URI)
            ->timeout(10)
            ->get("/{$pair}");

        if ($response->status() === 429) {
            throw new ProviderRateLimitException($this->getName(), 'AwesomeAPI rate limit exceeded.');
        }

        if ($response->serverError()) {
            throw new ProviderUnavailableException($this->getName(), 'AwesomeAPI is unavailable at the moment.');
        }

        if (! $response->successful()) {
            throw new QuoteNotFoundException($normalizedSymbol);
        }

        $payload = $response->json();
        $pairKey = str_replace('-', '', $pair);
        $quotePayload = $payload[$pairKey] ?? null;

        if (! $quotePayload || ! isset($quotePayload['bid'])) {
            throw new QuoteNotFoundException($normalizedSymbol);
        }

        return new Quote(
            symbol: $this->quoteSymbol($quotePayload, $resolvedAssetType, $normalizedSymbol),
            name: $quotePayload['name'] ?? $normalizedSymbol,
            type: $resolvedAssetType->value,
            price: (float) $quotePayload['bid'],
            currency: $quotePayload['codein'] ?? $quoteCurrency,
            source: $this->getName(),
            quotedAt: $this->parseProviderTimestamp($quotePayload['create_date'] ?? null)
        );
    }

    /**
     * Retorna nome interno do provider para logs e respostas.
     */
    public function getName(): string
    {
        return 'awesome_api';
    }

    /**
     * Monta representacao de par exigida pela AwesomeAPI.
     */
    private function buildPair(string $symbol, AssetType $type): string
    {
        $quoteCurrency = strtoupper($this->config['quote_currency'] ?? 'USD');

        // A AwesomeAPI espera notacao direta de par para simbolos FX.
        if ($type === AssetType::Currency && strlen($symbol) === 6) {
            return substr($symbol, 0, 3).'-'.substr($symbol, 3, 3);
        }

        return "{$symbol}-{$quoteCurrency}";
    }

    /**
     * Resolve tipo de ativo por parametro explicito ou inferencia do simbolo.
     */
    private function resolveType(string $symbol, ?string $requestedAssetType): AssetType
    {
        if ($requestedAssetType !== null && in_array($requestedAssetType, AssetType::values(), true)) {
            return AssetType::from($requestedAssetType);
        }

        return $this->assetTypeResolver->resolve($symbol);
    }

    /**
     * Resolve simbolo de saida com base no payload retornado pelo provider.
     *
     * @param  array<string, mixed>  $quotePayload
     */
    private function quoteSymbol(array $quotePayload, AssetType $type, string $fallbackSymbol): string
    {
        $code = strtoupper((string) ($quotePayload['code'] ?? ''));
        $codeIn = strtoupper((string) ($quotePayload['codein'] ?? ''));

        if ($type === AssetType::Currency && $code !== '' && $codeIn !== '') {
            return "{$code}{$codeIn}";
        }

        return $code !== '' ? $code : $fallbackSymbol;
    }

    /**
     * A AwesomeAPI retorna timestamps sem timezone; faz parse com timezone do provider.
     */
    private function parseProviderTimestamp(mixed $timestamp): CarbonImmutable
    {
        if ($timestamp === null || $timestamp === '') {
            return CarbonImmutable::now('UTC');
        }

        $rawTimestamp = trim((string) $timestamp);

        // Preserva offsets explicitos de timezone caso o provider passe a retorna-los.
        if (preg_match('/(?:Z|[+\-]\d{2}:?\d{2})$/i', $rawTimestamp) === 1) {
            return CarbonImmutable::parse($rawTimestamp)->utc();
        }

        $timezone = (string) ($this->config['timezone'] ?? config('app.timezone', 'UTC'));

        return CarbonImmutable::parse($rawTimestamp, $timezone)->utc();
    }
}
