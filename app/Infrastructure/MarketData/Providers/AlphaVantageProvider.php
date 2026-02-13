<?php

namespace App\Infrastructure\MarketData\Providers;

use App\Domain\MarketData\AssetType;
use App\Domain\MarketData\Quote;
use App\Domain\MarketData\Exceptions\ProviderRateLimitException;
use App\Domain\MarketData\Exceptions\ProviderUnavailableException;
use App\Domain\MarketData\Exceptions\QuoteNotFoundException;
use App\Domain\Contracts\MarketDataProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

/**
 * Adapter do Alpha Vantage para cotacoes de acoes, cripto e pares FX.
 */
class AlphaVantageProvider implements MarketDataProvider
{
    /**
     * Injeta configuracao de endpoint/chave para o provider.
     */
    public function __construct(private readonly array $config = []) {}

    /**
     * @throws QuoteNotFoundException
     * @throws ProviderUnavailableException
     * @throws ProviderRateLimitException
     */
    public function fetch(string $symbol, ?string $requestedAssetType = null): Quote
    {
        $normalizedSymbol = strtoupper(trim($symbol));
        $apiKey = $this->config['api_key'] ?? null;

        if (! $apiKey) {
            throw new ProviderUnavailableException($this->getName(), 'Alpha Vantage API key is missing.');
        }

        $resolvedAssetType = $this->resolveType($requestedAssetType);
        $alphabeticSymbol = strtoupper((string) preg_replace('/[^A-Z]/', '', $normalizedSymbol));

        if ($resolvedAssetType === AssetType::Crypto) {
            [$baseCurrency, $quoteCurrency] = $this->resolveCryptoPair($alphabeticSymbol);

            return $this->fetchCurrencyQuote(
                baseCurrency: $baseCurrency,
                quoteCurrency: $quoteCurrency,
                apiKey: $apiKey,
                type: AssetType::Crypto
            );
        }

        if ($resolvedAssetType === AssetType::Currency && strlen($alphabeticSymbol) === 6) {
            return $this->fetchCurrencyQuote(
                baseCurrency: substr($alphabeticSymbol, 0, 3),
                quoteCurrency: substr($alphabeticSymbol, 3, 3),
                apiKey: $apiKey,
                type: AssetType::Currency
            );
        }

        return $this->fetchGlobalQuote($normalizedSymbol, $resolvedAssetType, $apiKey);
    }

    /**
     * Retorna nome interno do provider para logs e respostas.
     */
    public function getName(): string
    {
        return 'alpha_vantage';
    }

    /**
     * Consulta o endpoint GLOBAL_QUOTE para simbolos do tipo acao.
     */
    private function fetchGlobalQuote(string $symbol, AssetType $resolvedAssetType, string $apiKey): Quote
    {
        $response = Http::baseUrl($this->config['base_uri'] ?? 'https://www.alphavantage.co')
            ->timeout(10)
            ->get('/query', [
                'function' => 'GLOBAL_QUOTE',
                'symbol' => $symbol,
                'apikey' => $apiKey,
            ]);

        $responsePayload = $response->json();
        $responsePayload = is_array($responsePayload) ? $responsePayload : [];
        $this->ensureSuccessfulResponse($response->status(), $responsePayload, $symbol);

        $quotePayload = $responsePayload['Global Quote'] ?? [];
        $price = $quotePayload['05. price'] ?? null;

        if (! $price) {
            throw new QuoteNotFoundException($symbol);
        }

        $quotedAt = $quotePayload['07. latest trading day'] ?? null;

        return new Quote(
            symbol: $quotePayload['01. symbol'] ?? $symbol,
            name: $quotePayload['01. symbol'] ?? $symbol,
            type: $resolvedAssetType->value,
            price: (float) $price,
            currency: $this->config['currency'] ?? 'USD',
            source: $this->getName(),
            quotedAt: $this->parseProviderTimestamp($quotedAt)
        );
    }

    /**
     * Consulta o endpoint CURRENCY_EXCHANGE_RATE para pares FX e cripto.
     */
    private function fetchCurrencyQuote(string $baseCurrency, string $quoteCurrency, string $apiKey, AssetType $type): Quote
    {
        $response = Http::baseUrl($this->config['base_uri'] ?? 'https://www.alphavantage.co')
            ->timeout(10)
            ->get('/query', [
                'function' => 'CURRENCY_EXCHANGE_RATE',
                'from_currency' => $baseCurrency,
                'to_currency' => $quoteCurrency,
                'apikey' => $apiKey,
            ]);

        $responsePayload = $response->json();
        $responsePayload = is_array($responsePayload) ? $responsePayload : [];
        $this->ensureSuccessfulResponse($response->status(), $responsePayload, "{$baseCurrency}{$quoteCurrency}");

        $quotePayload = $responsePayload['Realtime Currency Exchange Rate'] ?? [];
        $price = $quotePayload['5. Exchange Rate'] ?? null;

        if (! $price) {
            throw new QuoteNotFoundException("{$baseCurrency}{$quoteCurrency}");
        }

        $quotedAt = $quotePayload['6. Last Refreshed'] ?? now();

        return new Quote(
            symbol: $type === AssetType::Crypto ? $baseCurrency : "{$baseCurrency}{$quoteCurrency}",
            name: ($quotePayload['2. From_Currency Name'] ?? $baseCurrency).' / '.($quotePayload['4. To_Currency Name'] ?? $quoteCurrency),
            type: $type->value,
            price: (float) $price,
            currency: $quoteCurrency,
            source: $this->getName(),
            quotedAt: $this->parseProviderTimestamp($quotedAt)
        );
    }

    /**
     * Mapeia status/resposta do provider para excecoes de dominio da aplicacao.
     *
     * @param  array<string, mixed>  $payload
     */
    private function ensureSuccessfulResponse(int $httpStatus, array $payload, string $symbol): void
    {
        if ($httpStatus === 429 || isset($payload['Note'])) {
            throw new ProviderRateLimitException($this->getName(), 'Alpha Vantage rate limit exceeded.');
        }

        if ($httpStatus >= 500) {
            throw new ProviderUnavailableException($this->getName(), 'Alpha Vantage is unavailable at the moment.');
        }

        if ($httpStatus >= 400) {
            throw new QuoteNotFoundException($symbol);
        }

        if (isset($payload['Information']) && is_string($payload['Information'])) {
            $message = $payload['Information'];

            if (str_contains(strtolower($message), 'rate limit')) {
                throw new ProviderRateLimitException($this->getName(), $message);
            }

            throw new ProviderUnavailableException($this->getName(), $message);
        }

        if (isset($payload['Error Message'])) {
            throw new QuoteNotFoundException($symbol);
        }
    }

    /**
     * Resolve o tipo de ativo solicitado, assumindo `stock` por padrao.
     */
    private function resolveType(?string $requestedAssetType): AssetType
    {
        if ($requestedAssetType !== null && in_array($requestedAssetType, AssetType::values(), true)) {
            return AssetType::from($requestedAssetType);
        }

        // Mantem comportamento explicito: sem tipo informado, o simbolo eh tratado como stock.
        return AssetType::Stock;
    }

    /**
     * Resolve par base/cotacao para simbolos de criptoativos.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveCryptoPair(string $normalizedSymbol): array
    {
        $defaultQuote = strtoupper((string) ($this->config['currency'] ?? 'USD'));
        $currencyCodes = array_values(array_unique(array_map(
            'strtoupper',
            config('market-data.currency_codes', [])
        )));

        foreach ($currencyCodes as $quoteCurrency) {
            if (! is_string($quoteCurrency) || $quoteCurrency === '') {
                continue;
            }

            if (strlen($normalizedSymbol) <= strlen($quoteCurrency)) {
                continue;
            }

            if (str_ends_with($normalizedSymbol, $quoteCurrency)) {
                $baseSymbol = substr($normalizedSymbol, 0, -strlen($quoteCurrency));

                if (is_string($baseSymbol) && $baseSymbol !== '') {
                    return [$baseSymbol, $quoteCurrency];
                }
            }
        }

        return [$normalizedSymbol, $defaultQuote];
    }

    /**
     * O Alpha Vantage pode retornar datas sem timezone; faz parse com timezone configurado.
     */
    private function parseProviderTimestamp(mixed $timestamp): CarbonImmutable
    {
        if ($timestamp === null || $timestamp === '') {
            return CarbonImmutable::now('UTC');
        }

        $rawTimestamp = trim((string) $timestamp);

        if (preg_match('/(?:Z|[+\-]\d{2}:?\d{2})$/i', $rawTimestamp) === 1) {
            return CarbonImmutable::parse($rawTimestamp)->utc();
        }

        $timezone = (string) ($this->config['timezone'] ?? config('app.timezone', 'UTC'));

        return CarbonImmutable::parse($rawTimestamp, $timezone)->utc();
    }
}
