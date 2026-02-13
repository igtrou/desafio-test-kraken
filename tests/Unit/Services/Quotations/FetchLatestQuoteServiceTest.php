<?php

namespace Tests\Unit\Services\Quotations;

use App\Domain\Contracts\MarketDataProvider;
use App\Domain\MarketData\AssetTypeResolver;
use App\Domain\MarketData\Exceptions\ProviderRateLimitException;
use App\Domain\MarketData\Exceptions\ProviderUnavailableException;
use App\Domain\MarketData\Exceptions\QuoteNotFoundException;
use App\Domain\MarketData\Quote;
use App\Domain\MarketData\SymbolNormalizer;
use App\Infrastructure\Config\QuotationsConfig;
use App\Infrastructure\MarketData\MarketDataProviderManager;
use App\Infrastructure\MarketData\QuoteCache;
use App\Services\Quotations\FetchLatestQuoteService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class FetchLatestQuoteServiceTest extends TestCase
{
    /**
     * Prepara o cenario base para a execucao do teste.
     */
    protected function setUp(): void
    {
        parent::setUp();

        TestFakeMarketDataProvider::reset();

        config([
            'cache.default' => 'array',
            'quotations.cache_ttl' => 300,
        ]);

        Cache::flush();
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_uses_fallback_order_and_stops_at_first_successful_provider(): void
    {
        $service = $this->buildService([
            'first' => 'unavailable',
            'second' => 'success',
            'third' => 'success',
        ]);

        $quote = $service->handle('MSFT', null, 'stock');

        $this->assertSame('MSFT', $quote->symbol);
        $this->assertSame('second', $quote->source);
        $this->assertSame(['first', 'second'], array_column(TestFakeMarketDataProvider::$calls, 'provider'));
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_explicit_provider_is_fail_fast_without_fallback(): void
    {
        $service = $this->buildService([
            'first' => 'not_found',
            'second' => 'success',
        ]);

        $this->expectException(QuoteNotFoundException::class);

        try {
            $service->handle('MSFT', 'first', 'stock');
        } finally {
            $this->assertSame(['first'], array_column(TestFakeMarketDataProvider::$calls, 'provider'));
        }
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_caches_quote_by_provider_type_and_symbol(): void
    {
        $service = $this->buildService([
            'awesome' => 'success',
        ]);

        $first = $service->handle('BTC', null, 'crypto');
        $second = $service->handle('BTC', null, 'crypto');

        $this->assertSame('awesome', $first->source);
        $this->assertSame('awesome', $second->source);
        $this->assertCount(1, TestFakeMarketDataProvider::$calls);
        $this->assertTrue(Cache::has('quote:awesome:crypto:BTC'));
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_prioritizes_rate_limit_exception_over_other_provider_failures(): void
    {
        $service = $this->buildService([
            'first' => 'not_found',
            'second' => 'unavailable',
            'third' => 'rate_limited',
        ]);

        try {
            $service->handle('AAPL', null, 'stock');
            $this->fail('Expected a ProviderRateLimitException to be thrown.');
        } catch (ProviderRateLimitException $exception) {
            $this->assertSame('third', $exception->provider);
            $this->assertSame(['first', 'second', 'third'], array_column(TestFakeMarketDataProvider::$calls, 'provider'));
        }
    }

    /**
     * Monta os dados necessarios para a proxima etapa.
     */
    /**
     * @param  array<string, string>  $providerBehaviors
     */
    private function buildService(array $providerBehaviors): FetchLatestQuoteService
    {
        $providers = [];

        foreach ($providerBehaviors as $providerName => $behavior) {
            $providers[$providerName] = [
                'class' => TestFakeMarketDataProvider::class,
                'provider_name' => $providerName,
                'behavior' => $behavior,
            ];
        }

        $manager = new MarketDataProviderManager(app(), [
            'default' => array_key_first($providers),
            'providers' => $providers,
            'fallbacks' => [
                'stock' => array_keys($providers),
                'crypto' => array_keys($providers),
                'currency' => array_keys($providers),
            ],
        ]);

        $resolver = new AssetTypeResolver(
            cryptoSymbols: ['BTC', 'ETH'],
            currencyCodes: ['USD', 'BRL', 'EUR']
        );

        return new FetchLatestQuoteService(
            $manager,
            app(QuoteCache::class),
            app(QuotationsConfig::class),
            $resolver,
            new SymbolNormalizer
        );
    }
}

class TestFakeMarketDataProvider implements MarketDataProvider
{
    /**
     * @var array<int, array{provider: string, symbol: string, type: string|null}>
     */
    public static array $calls = [];

    /**
     * Inicializa a instancia com as dependencias necessarias.
     */
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config = []) {}

    /**
     * Restaura o estado padrao do fluxo.
     */
    public static function reset(): void
    {
        self::$calls = [];
    }

    /**
     * Busca dados na fonte configurada.
     */
    public function fetch(string $symbol, ?string $requestedAssetType = null): Quote
    {
        $providerName = $this->getName();
        self::$calls[] = [
            'provider' => $providerName,
            'symbol' => $symbol,
            'type' => $requestedAssetType,
        ];

        $behavior = (string) ($this->config['behavior'] ?? 'success');

        return match ($behavior) {
            'success' => new Quote(
                symbol: $symbol,
                name: "{$providerName}-{$symbol}",
                type: $requestedAssetType ?? 'stock',
                price: 100.0,
                currency: 'USD',
                source: $providerName,
                quotedAt: CarbonImmutable::parse('2026-02-08 12:00:00 UTC')
            ),
            'not_found' => throw new QuoteNotFoundException($symbol),
            'unavailable' => throw new ProviderUnavailableException($providerName, 'Provider unavailable'),
            'rate_limited' => throw new ProviderRateLimitException($providerName, 'Provider rate-limited'),
            'runtime_error' => throw new RuntimeException('Unexpected provider failure'),
            default => throw new RuntimeException("Unknown fake provider behavior [{$behavior}]"),
        };
    }

    /**
     * Obtem dados conforme os parametros informados.
     */
    public function getName(): string
    {
        return (string) ($this->config['provider_name'] ?? 'fake_provider');
    }
}
