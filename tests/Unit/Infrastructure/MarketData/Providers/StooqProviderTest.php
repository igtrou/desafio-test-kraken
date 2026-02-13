<?php

namespace Tests\Unit\Infrastructure\MarketData\Providers;

use App\Domain\MarketData\Exceptions\ProviderRateLimitException;
use App\Domain\MarketData\Exceptions\ProviderUnavailableException;
use App\Domain\MarketData\Exceptions\QuoteNotFoundException;
use App\Infrastructure\MarketData\Providers\StooqProvider;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StooqProviderTest extends TestCase
{
    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_parses_valid_csv_response(): void
    {
        Http::fake([
            'stooq.com/*' => Http::response(
                'AAPL.US,20260208,153000,240.00,242.00,239.00,241.50,1000000',
                200
            ),
        ]);

        $quote = $this->provider()->fetch('aapl', 'stock');

        $this->assertSame('AAPL', $quote->symbol);
        $this->assertSame('AAPL', $quote->name);
        $this->assertSame('stock', $quote->type);
        $this->assertSame(241.5, $quote->price);
        $this->assertSame('USD', $quote->currency);
        $this->assertSame('stooq', $quote->source);
        $this->assertTrue($quote->quotedAt->equalTo(
            CarbonImmutable::createFromFormat('Ymd His', '20260208 153000', 'UTC')
        ));

        Http::assertSent(function (HttpRequest $request): bool {
            $queryString = parse_url($request->url(), PHP_URL_QUERY);
            parse_str(is_string($queryString) ? $queryString : '', $query);

            return str_starts_with($request->url(), 'https://stooq.com/q/l/')
                && ($query['s'] ?? null) === 'aapl.us'
                && ($query['i'] ?? null) === 'd';
        });
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_throws_not_found_for_nd_close_price_and_invalid_csv_payload(): void
    {
        foreach ([
            'AAPL.US,20260208,153000,240.00,242.00,239.00,N/D,1000000',
            'AAPL.US,20260208',
        ] as $body) {
            Http::fake([
                'stooq.com/*' => Http::response($body, 200),
            ]);

            try {
                $this->provider()->fetch('AAPL', 'stock');
                $this->fail('Expected QuoteNotFoundException for invalid Stooq payload.');
            } catch (QuoteNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_throws_rate_limit_exception_for_status_429(): void
    {
        Http::fake([
            'stooq.com/*' => Http::response('', 429),
        ]);

        try {
            $this->provider()->fetch('AAPL', 'stock');
            $this->fail('Expected ProviderRateLimitException to be thrown.');
        } catch (ProviderRateLimitException $exception) {
            $this->assertSame('stooq', $exception->provider);
        }
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_throws_provider_unavailable_for_server_errors(): void
    {
        Http::fake([
            'stooq.com/*' => Http::response('', 503),
        ]);

        try {
            $this->provider()->fetch('AAPL', 'stock');
            $this->fail('Expected ProviderUnavailableException to be thrown.');
        } catch (ProviderUnavailableException $exception) {
            $this->assertSame('stooq', $exception->provider);
        }
    }

    /**
     * Retorna o provider apropriado para a solicitacao.
     */
    /**
     * @param  array<string, mixed>  $config
     */
    private function provider(array $config = []): StooqProvider
    {
        return new StooqProvider(array_merge([
            'base_uri' => 'https://stooq.com',
            'currency' => 'USD',
        ], $config));
    }
}
