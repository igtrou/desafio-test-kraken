<?php

namespace Tests\Unit\Infrastructure\MarketData\Providers;

use App\Domain\MarketData\Exceptions\ProviderRateLimitException;
use App\Domain\MarketData\Exceptions\ProviderUnavailableException;
use App\Domain\MarketData\Exceptions\QuoteNotFoundException;
use App\Infrastructure\MarketData\Providers\YahooFinanceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YahooFinanceProviderTest extends TestCase
{
    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_maps_valid_payload_to_quote_data(): void
    {
        $marketTime = CarbonImmutable::parse('2026-02-08 14:30:00 UTC')->timestamp;

        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response([
                'quoteResponse' => [
                    'result' => [[
                        'regularMarketPrice' => 410.12,
                        'shortName' => 'Microsoft Corporation',
                        'currency' => 'USD',
                        'regularMarketTime' => $marketTime,
                    ]],
                ],
            ], 200),
        ]);

        $quote = $this->provider()->fetch('msft', 'stock');

        $this->assertSame('MSFT', $quote->symbol);
        $this->assertSame('Microsoft Corporation', $quote->name);
        $this->assertSame('stock', $quote->type);
        $this->assertSame(410.12, $quote->price);
        $this->assertSame('USD', $quote->currency);
        $this->assertSame('yahoo_finance', $quote->source);
        $this->assertSame($marketTime, $quote->quotedAt->timestamp);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_throws_provider_unavailable_for_unauthorized_status_codes(): void
    {
        foreach ([401, 403] as $status) {
            Http::fake([
                'query1.finance.yahoo.com/*' => Http::response([], $status),
            ]);

            try {
                $this->provider()->fetch('MSFT', 'stock');
                $this->fail("Expected ProviderUnavailableException for status [{$status}].");
            } catch (ProviderUnavailableException $exception) {
                $this->assertSame('yahoo_finance', $exception->provider);
            }
        }
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_throws_rate_limit_exception_for_status_429(): void
    {
        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response([], 429),
        ]);

        try {
            $this->provider()->fetch('MSFT', 'stock');
            $this->fail('Expected ProviderRateLimitException to be thrown.');
        } catch (ProviderRateLimitException $exception) {
            $this->assertSame('yahoo_finance', $exception->provider);
        }
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_throws_not_found_when_response_has_no_result_entry(): void
    {
        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response([
                'quoteResponse' => [
                    'result' => [],
                ],
            ], 200),
        ]);

        $this->expectException(QuoteNotFoundException::class);
        $this->provider()->fetch('MSFT', 'stock');
    }

    /**
     * Retorna o provider apropriado para a solicitacao.
     */
    /**
     * @param  array<string, mixed>  $config
     */
    private function provider(array $config = []): YahooFinanceProvider
    {
        return new YahooFinanceProvider(array_merge([
            'base_uri' => 'https://query1.finance.yahoo.com',
            'currency' => 'USD',
        ], $config));
    }
}
