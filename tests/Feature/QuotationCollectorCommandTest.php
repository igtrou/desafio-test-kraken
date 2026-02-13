<?php

namespace Tests\Feature;

use App\Domain\MarketData\AssetType;
use App\Models\Asset;
use App\Models\Quotation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers end-to-end behavior of the quotations:collect console command.
 */
class QuotationCollectorCommandTest extends TestCase
{
    use RefreshDatabase;

    private CarbonImmutable $fixedNow;

    /**
     * Prepara o cenario base para a execucao do teste.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Freeze time so provider payloads and persisted timestamps are deterministic.
        $this->fixedNow = CarbonImmutable::parse('2026-02-07 11:00:00');
        CarbonImmutable::setTestNow($this->fixedNow);

        config([
            'quotations.cache_ttl' => 0,
            'quotations.auto_collect.symbols' => ['BTC', 'ETH'],
            'quotations.auto_collect.provider' => null,
            'market-data.default' => 'awesome_api',
            'market-data.providers.awesome_api.timezone' => 'UTC',
            'market-data.fallbacks' => [
                'stock' => ['awesome_api'],
                'crypto' => ['awesome_api'],
                'currency' => ['awesome_api'],
            ],
        ]);
    }

    /**
     * Limpa o cenario apos a execucao do teste.
     */
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    /**
     * It collects configured symbols and persists one quotation per symbol.
     */
    public function test_collect_command_persists_configured_symbols(): void
    {
        $this->fakeAwesomeQuotes([
            'BTCUSD' => ['code' => 'BTC', 'codein' => 'USD', 'name' => 'BTC/USD', 'bid' => '51000.35'],
            'ETHUSD' => ['code' => 'ETH', 'codein' => 'USD', 'name' => 'ETH/USD', 'bid' => '3200.10'],
        ]);

        $this->artisan('quotations:collect')
            ->expectsOutputToContain('Collecting 2 symbol(s)')
            ->expectsOutputToContain('Done. total=2 success=2 failed=0')
            ->assertExitCode(0);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/BTC-USD'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/ETH-USD'));

        $this->assertDatabaseCount((new Asset)->getTable(), 2);
        $this->assertDatabaseCount((new Quotation)->getTable(), 2);

        $this->assertPersistedQuote('BTC', 'BTC/USD', AssetType::Crypto, '51000.350000');
        $this->assertPersistedQuote('ETH', 'ETH/USD', AssetType::Crypto, '3200.100000');
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    /**
     * It executes in dry-run mode without writing to assets or quotations tables.
     */
    public function test_collect_command_dry_run_does_not_persist(): void
    {
        $this->fakeAwesomeQuotes([
            'BTCUSD' => ['code' => 'BTC', 'codein' => 'USD', 'name' => 'BTC/USD', 'bid' => '51000.35'],
        ]);

        $this->artisan('quotations:collect --symbol=BTC --dry-run')
            ->expectsOutputToContain('Collecting 1 symbol(s) in dry-run mode')
            ->expectsOutputToContain('Done. total=1 success=1 failed=0')
            ->assertExitCode(0);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/BTC-USD'));

        $this->assertDatabaseCount((new Asset)->getTable(), 0);
        $this->assertDatabaseCount((new Quotation)->getTable(), 0);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    /**
     * It returns failure when no symbol list is provided by flags or config.
     */
    public function test_collect_command_fails_when_no_symbols_are_configured(): void
    {
        config(['quotations.auto_collect.symbols' => []]);

        $this->artisan('quotations:collect')
            ->expectsOutputToContain('No symbols configured.')
            ->assertExitCode(1);

        $this->assertDatabaseCount((new Asset)->getTable(), 0);
        $this->assertDatabaseCount((new Quotation)->getTable(), 0);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_collect_command_falls_back_to_awesome_api_when_alpha_and_yahoo_are_rate_limited(): void
    {
        config([
            'market-data.fallbacks.stock' => ['alpha_vantage', 'yahoo_finance', 'awesome_api'],
        ]);

        Http::fake([
            'www.alphavantage.co/*' => Http::response([
                'Note' => 'Alpha Vantage rate limit reached.',
            ], 200),
            'query1.finance.yahoo.com/*' => Http::response('Edge: Too Many Requests', 429),
            'economia.awesomeapi.com.br/*' => $this->awesomeMsftResponse(),
        ]);

        $this->artisan('quotations:collect --symbol=MSFT --dry-run')
            ->expectsOutputToContain('Collecting 1 symbol(s) in dry-run mode')
            ->expectsOutputToContain('OK: MSFT | source=awesome_api | price=401.14')
            ->expectsOutputToContain('Done. total=1 success=1 failed=0')
            ->assertExitCode(0);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_collect_command_fails_when_any_symbol_fails_by_default(): void
    {
        $this->fakeAwesomeQuotes([
            'BTCUSD' => ['code' => 'BTC', 'codein' => 'USD', 'name' => 'BTC/USD', 'bid' => '51000.35'],
        ]);

        $this->artisan('quotations:collect --symbol=BTC --symbol=MSFT --dry-run --provider=awesome_api')
            ->expectsOutputToContain('OK: BTC | source=awesome_api | price=51000.35')
            ->expectsOutputToContain('ERROR: MSFT | Quote not found for symbol [MSFT].')
            ->expectsOutputToContain('Done. total=2 success=1 failed=1')
            ->assertExitCode(1);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_collect_command_can_return_success_for_partial_results_when_enabled(): void
    {
        $this->fakeAwesomeQuotes([
            'BTCUSD' => ['code' => 'BTC', 'codein' => 'USD', 'name' => 'BTC/USD', 'bid' => '51000.35'],
        ]);

        $this->artisan('quotations:collect --symbol=BTC --symbol=MSFT --dry-run --provider=awesome_api --allow-partial-success')
            ->expectsOutputToContain('OK: BTC | source=awesome_api | price=51000.35')
            ->expectsOutputToContain('ERROR: MSFT | Quote not found for symbol [MSFT].')
            ->expectsOutputToContain('Done. total=2 success=1 failed=1')
            ->expectsOutputToContain('Partial success mode is enabled')
            ->assertExitCode(0);
    }

    /**
     * Executa a rotina principal do metodo fakeAwesomeQuotes.
     */
    /**
     * @param  array<string, array{code: string, codein: string, name: string, bid: string}>  $quotes
     */
    private function fakeAwesomeQuotes(array $quotes): void
    {
        Http::fake([
            'economia.awesomeapi.com.br/*' => function ($request) use ($quotes) {
                $pair = strtoupper((string) basename((string) parse_url($request->url(), PHP_URL_PATH)));
                $key = str_replace('-', '', $pair);
                $quote = $quotes[$key] ?? null;

                if ($quote === null) {
                    return Http::response([], 404);
                }

                return Http::response([
                    $key => $quote + ['create_date' => $this->fixedNow->toDateTimeString()],
                ], 200);
            },
        ]);
    }

    /**
     * Executa a rotina principal do metodo awesomeMsftResponse.
     */
    private function awesomeMsftResponse()
    {
        return Http::response([
            'MSFTUSD' => [
                'code' => 'MSFT',
                'codein' => 'USD',
                'name' => 'MSFT/USD',
                'bid' => '401.14',
                'create_date' => $this->fixedNow->toDateTimeString(),
            ],
        ], 200);
    }

    /**
     * Executa uma validacao de expectativa para o cenario atual.
     */
    /**
     * Asserts full persistence contract for an individual quotation record.
     */
    private function assertPersistedQuote(string $symbol, string $name, AssetType $type, string $price): void
    {
        $asset = Asset::query()->where('symbol', $symbol)->firstOrFail();
        $quotation = Quotation::query()->whereBelongsTo($asset)->sole();

        $this->assertSame($name, $asset->name);
        $this->assertSame($type, $asset->type);
        $this->assertSame($price, $quotation->price);
        $this->assertSame('USD', $quotation->currency);
        $this->assertSame('awesome_api', $quotation->source);
        $this->assertSame($this->fixedNow->toDateTimeString(), $quotation->quoted_at?->toDateTimeString());
    }
}
