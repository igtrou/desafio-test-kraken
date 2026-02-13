<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewaySourceEnforcementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valida o cenario identificado por `test_blocks_direct_api_access_when_gateway_source_enforcement_is_enabled`.
     */
    public function test_blocks_direct_api_access_when_gateway_source_enforcement_is_enabled(): void
    {
        config([
            'gateway.enforce_source' => true,
            'gateway.shared_secret' => 'krakend-internal',
            'quotations.require_auth' => false,
        ]);

        $response = $this->getJson('/api/quotation/BTC');

        $response->assertForbidden()
            ->assertJsonPath('error_code', 'forbidden');
    }

    /**
     * Valida o cenario identificado por `test_allows_api_access_with_valid_gateway_secret_when_enforcement_is_enabled`.
     */
    public function test_allows_api_access_with_valid_gateway_secret_when_enforcement_is_enabled(): void
    {
        config([
            'gateway.enforce_source' => true,
            'gateway.shared_secret' => 'krakend-internal',
            'quotations.require_auth' => false,
        ]);
        Http::fake([
            'economia.awesomeapi.com.br/*' => Http::response([
                'BTCUSD' => [
                    'code' => 'BTC',
                    'codein' => 'USD',
                    'name' => 'BTC/USD',
                    'bid' => '52000.10',
                    'create_date' => now()->toDateTimeString(),
                ],
            ], 200),
        ]);

        $response = $this->withHeaders([
            'X-Gateway-Secret' => 'krakend-internal',
        ])->getJson('/api/quotation/BTC');

        $response->assertOk()
            ->assertJsonPath('data.symbol', 'BTC');
    }
}
