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
 * Adapter da Stooq usado como fallback resiliente para cotacoes de mercado.
 */
class StooqProvider implements MarketDataProvider
{
    private const DEFAULT_BASE_URI = 'https://stooq.com';

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
        $resolvedType = $this->resolveType($normalizedSymbol, $requestedAssetType);
        $querySymbol = $this->buildQuerySymbol($normalizedSymbol, $resolvedType);

        $response = Http::baseUrl($this->config['base_uri'] ?? self::DEFAULT_BASE_URI)
            ->timeout(10)
            ->get('/q/l/', [
                's' => $querySymbol,
                'i' => 'd',
            ]);

        if ($response->status() === 429) {
            throw new ProviderRateLimitException($this->getName(), 'Stooq rate limit exceeded.');
        }

        if ($response->serverError()) {
            throw new ProviderUnavailableException($this->getName(), 'Stooq is unavailable at the moment.');
        }

        if (! $response->successful()) {
            throw new QuoteNotFoundException($normalizedSymbol);
        }

        $line = trim((string) $response->body());

        if ($line === '') {
            throw new QuoteNotFoundException($normalizedSymbol);
        }

        $parts = str_getcsv($line);

        if (! is_array($parts) || count($parts) < 7) {
            throw new QuoteNotFoundException($normalizedSymbol);
        }

        $closePrice = $parts[6] ?? null;

        if (! is_string($closePrice) || $closePrice === '' || strtoupper($closePrice) === 'N/D' || ! is_numeric($closePrice)) {
            throw new QuoteNotFoundException($normalizedSymbol);
        }

        $quotedAt = $this->parseTimestamp($parts[1] ?? null, $parts[2] ?? null);

        return new Quote(
            symbol: $this->normalizeOutputSymbol($normalizedSymbol, $resolvedType),
            name: $normalizedSymbol,
            type: $resolvedType->value,
            price: (float) $closePrice,
            currency: $this->resolveCurrency($normalizedSymbol, $resolvedType),
            source: $this->getName(),
            quotedAt: $quotedAt
        );
    }

    /**
     * Retorna nome interno do provider para logs e respostas.
     */
    public function getName(): string
    {
        return 'stooq';
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
     * Monta simbolo no formato esperado pela query da Stooq.
     */
    private function buildQuerySymbol(string $symbol, AssetType $type): string
    {
        $clean = strtolower(trim($symbol));

        if ($type !== AssetType::Stock) {
            return strtolower((string) preg_replace('/[^a-z0-9]/', '', $clean));
        }

        if (str_contains($clean, '.')) {
            return $clean;
        }

        if (preg_match('/^[a-z0-9]{1,6}$/', $clean) === 1) {
            return "{$clean}.us";
        }

        return $clean;
    }

    /**
     * Normaliza simbolo de saida conforme o tipo de ativo consultado.
     */
    private function normalizeOutputSymbol(string $symbol, AssetType $type): string
    {
        if ($type === AssetType::Currency || $type === AssetType::Crypto) {
            return strtoupper((string) preg_replace('/[^A-Z0-9]/', '', $symbol));
        }

        return strtoupper($symbol);
    }

    /**
     * Resolve moeda da cotacao com fallback para configuracao padrao.
     */
    private function resolveCurrency(string $symbol, AssetType $type): string
    {
        $defaultCurrency = strtoupper((string) ($this->config['currency'] ?? 'USD'));

        if ($type === AssetType::Currency && strlen($symbol) >= 6) {
            return substr(strtoupper((string) preg_replace('/[^A-Z]/', '', $symbol)), 3, 3) ?: $defaultCurrency;
        }

        return $defaultCurrency;
    }

    /**
     * Interpreta data/hora da Stooq e converte para `CarbonImmutable` em UTC.
     */
    private function parseTimestamp(mixed $date, mixed $time): CarbonImmutable
    {
        if (! is_string($date) || strtoupper($date) === 'N/D' || $date === '') {
            return CarbonImmutable::now('UTC');
        }

        if (! is_string($time) || strtoupper($time) === 'N/D' || $time === '') {
            return CarbonImmutable::parse($date, 'UTC')->startOfDay();
        }

        return CarbonImmutable::createFromFormat('Ymd His', "{$date} {$time}", 'UTC')
            ?: CarbonImmutable::now('UTC');
    }
}
