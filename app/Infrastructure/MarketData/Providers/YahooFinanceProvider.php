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
 * Adapter do Yahoo Finance usado como provider de fallback, principalmente para acoes.
 */
class YahooFinanceProvider implements MarketDataProvider
{
    private const DEFAULT_BASE_URI = 'https://query1.finance.yahoo.com';

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
        $normalizedSymbol = strtoupper(trim($symbol));
        $assetType = $this->resolveType($normalizedSymbol, $requestedAssetType);
        $querySymbol = $this->buildQuerySymbol($normalizedSymbol, $assetType);

        $response = Http::baseUrl($this->config['base_uri'] ?? self::DEFAULT_BASE_URI)
            ->timeout(10)
            ->get('/v7/finance/quote', [
                'symbols' => $querySymbol,
            ]);

        if ($response->status() === 429) {
            throw new ProviderRateLimitException($this->getName(), 'Yahoo Finance rate limit exceeded.');
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new ProviderUnavailableException($this->getName(), 'Yahoo Finance request was rejected (unauthorized).');
        }

        if ($response->serverError()) {
            throw new ProviderUnavailableException($this->getName(), 'Yahoo Finance is unavailable at the moment.');
        }

        if (! $response->successful()) {
            throw new ProviderUnavailableException($this->getName(), "Yahoo Finance request failed with status {$response->status()}.");
        }

        $payload = $response->json();
        $responseErrorCode = strtoupper((string) ($payload['finance']['error']['code'] ?? ''));
        if ($responseErrorCode === 'UNAUTHORIZED') {
            throw new ProviderUnavailableException($this->getName(), 'Yahoo Finance request was rejected (unauthorized).');
        }

        $result = $payload['quoteResponse']['result'][0] ?? null;

        if (! is_array($result)) {
            throw new QuoteNotFoundException($normalizedSymbol);
        }

        $price = $result['regularMarketPrice'] ?? null;

        if (! is_numeric($price)) {
            throw new QuoteNotFoundException($normalizedSymbol);
        }

        return new Quote(
            symbol: $this->normalizeOutputSymbol($normalizedSymbol, $assetType),
            name: (string) ($result['shortName'] ?? $result['longName'] ?? $result['displayName'] ?? $querySymbol),
            type: $assetType->value,
            price: (float) $price,
            currency: (string) ($result['currency'] ?? ($this->config['currency'] ?? 'USD')),
            source: $this->getName(),
            quotedAt: $this->parseTimestamp($result['regularMarketTime'] ?? null)
        );
    }

    /**
     * Retorna nome interno do provider para logs e respostas.
     */
    public function getName(): string
    {
        return 'yahoo_finance';
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
     * Monta simbolo no formato aceito pela API de cotacoes do Yahoo Finance.
     */
    private function buildQuerySymbol(string $symbol, AssetType $type): string
    {
        $alphabeticSymbol = strtoupper((string) preg_replace('/[^A-Z]/', '', $symbol));

        if ($type === AssetType::Currency && strlen($alphabeticSymbol) === 6) {
            return "{$alphabeticSymbol}=X";
        }

        if ($type === AssetType::Crypto) {
            [$baseSymbol, $quoteCurrency] = $this->resolveCryptoPair($alphabeticSymbol);

            return "{$baseSymbol}-{$quoteCurrency}";
        }

        return strtoupper($symbol);
    }

    /**
     * Resolve par base/cotacao para simbolos de criptoativos.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveCryptoPair(string $symbol): array
    {
        $defaultQuote = strtoupper((string) ($this->config['currency'] ?? 'USD'));
        $currencyCodes = array_values(array_unique(array_map(
            'strtoupper',
            config('market-data.currency_codes', [])
        )));

        foreach ($currencyCodes as $quoteCurrency) {
            if (strlen($symbol) <= strlen($quoteCurrency)) {
                continue;
            }

            if (str_ends_with($symbol, $quoteCurrency)) {
                $base = substr($symbol, 0, -strlen($quoteCurrency));

                if ($base !== '') {
                    return [$base, $quoteCurrency];
                }
            }
        }

        return [$symbol, $defaultQuote];
    }

    /**
     * Normaliza simbolo de retorno conforme o tipo do ativo.
     */
    private function normalizeOutputSymbol(string $symbol, AssetType $type): string
    {
        if ($type === AssetType::Currency) {
            return strtoupper((string) preg_replace('/[^A-Z]/', '', $symbol));
        }

        if ($type === AssetType::Crypto) {
            return strtoupper((string) preg_replace('/[^A-Z0-9]/', '', $symbol));
        }

        return strtoupper($symbol);
    }

    /**
     * Converte timestamp de mercado retornado pelo Yahoo para UTC.
     */
    private function parseTimestamp(mixed $marketTime): CarbonImmutable
    {
        if (is_numeric($marketTime)) {
            return CarbonImmutable::createFromTimestampUTC((int) $marketTime);
        }

        return CarbonImmutable::now('UTC');
    }
}
