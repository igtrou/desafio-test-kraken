<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Quotation;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class QuotationApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Configura o ambiente padrao para os cenarios desta suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'market-data.default' => 'awesome_api',
            'quotations.require_auth' => false,
            'quotations.cache_ttl' => 0,
        ]);
    }

    /**
     * Valida o cenario identificado por `test_can_fetch_quote_from_default_provider`.
     */
    public function test_can_fetch_quote_from_default_provider(): void
    {
        $this->fakeAwesomeSuccess('BTC', 51000.35);

        $response = $this->getJson('/api/quotation/btc');

        $response->assertOk()
            ->assertJsonPath('data.symbol', 'BTC')
            ->assertJsonPath('data.price', 51000.35)
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.source', 'awesome_api');
    }

    /**
     * Valida o cenario identificado por `test_respects_explicit_provider_parameter`.
     */
    public function test_respects_explicit_provider_parameter(): void
    {
        $this->fakeAlphaSuccess('MSFT', 410.12);

        $response = $this->getJson('/api/quotation/msft?provider=alpha_vantage');

        $response->assertOk()
            ->assertJsonPath('data.symbol', 'MSFT')
            ->assertJsonPath('data.price', 410.12)
            ->assertJsonPath('data.source', 'alpha_vantage');
    }

    /**
     * Valida o cenario identificado por `test_alpha_vantage_uses_currency_exchange_endpoint_for_crypto_symbols`.
     */
    public function test_alpha_vantage_uses_currency_exchange_endpoint_for_crypto_symbols(): void
    {
        $this->fakeAlphaCurrencySuccess('BTC', 'USD', 68866.17);

        $response = $this->getJson('/api/quotation/BTC?provider=alpha_vantage&type=crypto');

        $response->assertOk()
            ->assertJsonPath('data.symbol', 'BTC')
            ->assertJsonPath('data.type', 'crypto')
            ->assertJsonPath('data.price', 68866.17)
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.source', 'alpha_vantage');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'function=CURRENCY_EXCHANGE_RATE') &&
                str_contains($request->url(), 'from_currency=BTC') &&
                str_contains($request->url(), 'to_currency=USD');
        });
    }

    /**
     * Valida o cenario identificado por `test_falls_back_to_next_provider_when_default_chain_fails`.
     */
    public function test_falls_back_to_next_provider_when_default_chain_fails(): void
    {
        Http::fake([
            'www.alphavantage.co/*' => Http::response([], 503),
            'query1.finance.yahoo.com/*' => Http::response([
                'quoteResponse' => [
                    'result' => [],
                ],
            ], 200),
            'economia.awesomeapi.com.br/*' => $this->awesomePayloadResponse('AAPL', 188.55),
        ]);

        $response = $this->getJson('/api/quotation/AAPL');

        $response->assertOk()
            ->assertJsonPath('data.symbol', 'AAPL')
            ->assertJsonPath('data.source', 'awesome_api');
    }

    /**
     * Valida o cenario identificado por `test_returns_rate_limit_error_when_fallback_chain_contains_provider_rate_limit`.
     */
    public function test_returns_rate_limit_error_when_fallback_chain_contains_provider_rate_limit(): void
    {
        Http::fake([
            'www.alphavantage.co/*' => Http::response([
                'Note' => 'Thank you for using Alpha Vantage! Please consider spreading out your free API requests.',
            ], 200),
            'query1.finance.yahoo.com/*' => Http::response([
                'quoteResponse' => [
                    'result' => [],
                ],
            ], 200),
            'economia.awesomeapi.com.br/*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/api/quotation/MSFT');

        $response->assertStatus(429)
            ->assertJsonPath('error_code', 'provider_rate_limited');
    }

    /**
     * Valida o cenario identificado por `test_falls_back_to_yahoo_finance_when_alpha_vantage_is_rate_limited`.
     */
    public function test_falls_back_to_yahoo_finance_when_alpha_vantage_is_rate_limited(): void
    {
        Http::fake([
            'www.alphavantage.co/*' => Http::response([
                'Note' => 'Alpha Vantage rate limit reached.',
            ], 200),
            'query1.finance.yahoo.com/*' => Http::response([
                'quoteResponse' => [
                    'result' => [
                        [
                            'symbol' => 'MSFT',
                            'shortName' => 'Microsoft Corporation',
                            'regularMarketPrice' => 421.77,
                            'regularMarketTime' => 1765137600,
                            'currency' => 'USD',
                        ],
                    ],
                ],
            ], 200),
            'economia.awesomeapi.com.br/*' => Http::response([], 404),
        ]);

        $response = $this->getJson('/api/quotation/MSFT');

        $response->assertOk()
            ->assertJsonPath('data.symbol', 'MSFT')
            ->assertJsonPath('data.source', 'yahoo_finance')
            ->assertJsonPath('data.price', 421.77);
    }

    /**
     * Valida o cenario identificado por `test_falls_back_to_awesome_api_when_alpha_and_yahoo_are_unavailable`.
     */
    public function test_falls_back_to_awesome_api_when_alpha_and_yahoo_are_unavailable(): void
    {
        Http::fake([
            'www.alphavantage.co/*' => Http::response([
                'Note' => 'Alpha Vantage rate limit reached.',
            ], 200),
            'query1.finance.yahoo.com/*' => Http::response('Edge: Too Many Requests', 429),
            'economia.awesomeapi.com.br/*' => $this->awesomePayloadResponse('MSFT', 401.14),
        ]);

        $response = $this->getJson('/api/quotation/MSFT');

        $response->assertOk()
            ->assertJsonPath('data.symbol', 'MSFT')
            ->assertJsonPath('data.source', 'awesome_api')
            ->assertJsonPath('data.price', 401.14);
    }

    /**
     * Valida o cenario identificado por `test_falls_back_to_awesome_api_when_yahoo_returns_unauthorized`.
     */
    public function test_falls_back_to_awesome_api_when_yahoo_returns_unauthorized(): void
    {
        Http::fake([
            'www.alphavantage.co/*' => Http::response([
                'Note' => 'Alpha Vantage rate limit reached.',
            ], 200),
            'query1.finance.yahoo.com/*' => Http::response([
                'finance' => [
                    'result' => null,
                    'error' => [
                        'code' => 'Unauthorized',
                        'description' => 'User is unable to access this feature.',
                    ],
                ],
            ], 401),
            'economia.awesomeapi.com.br/*' => $this->awesomePayloadResponse('MSFT', 401.14),
        ]);

        $response = $this->getJson('/api/quotation/MSFT');

        $response->assertOk()
            ->assertJsonPath('data.symbol', 'MSFT')
            ->assertJsonPath('data.source', 'awesome_api')
            ->assertJsonPath('data.price', 401.14);
    }

    /**
     * Valida o cenario identificado por `test_returns_provider_unavailable_when_explicit_yahoo_is_unauthorized`.
     */
    public function test_returns_provider_unavailable_when_explicit_yahoo_is_unauthorized(): void
    {
        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response([
                'finance' => [
                    'result' => null,
                    'error' => [
                        'code' => 'Unauthorized',
                        'description' => 'User is unable to access this feature.',
                    ],
                ],
            ], 401),
        ]);

        $response = $this->getJson('/api/quotation/MSFT?provider=yahoo_finance');

        $response->assertStatus(503)
            ->assertJsonPath('error_code', 'provider_unavailable');
    }

    /**
     * Valida o cenario identificado por `test_does_not_fallback_when_provider_is_explicit`.
     */
    public function test_does_not_fallback_when_provider_is_explicit(): void
    {
        Http::fake([
            'www.alphavantage.co/*' => Http::response([], 503),
            'economia.awesomeapi.com.br/*' => $this->awesomePayloadResponse('AAPL', 188.55),
        ]);

        $response = $this->getJson('/api/quotation/AAPL?provider=alpha_vantage');

        $response->assertStatus(503)
            ->assertJsonPath('error_code', 'provider_unavailable');
    }

    /**
     * Valida o cenario identificado por `test_returns_validation_error_for_invalid_symbol`.
     */
    public function test_returns_validation_error_for_invalid_symbol(): void
    {
        $response = $this->getJson('/api/quotation/BTC$');

        $response->assertStatus(422)
            ->assertJsonPath('error_code', 'invalid_symbol')
            ->assertJsonPath('message', 'Invalid symbol format.');
    }

    /**
     * Valida o cenario identificado por `test_returns_provider_unavailable_error_when_external_api_is_down`.
     */
    public function test_returns_provider_unavailable_error_when_external_api_is_down(): void
    {
        Http::fake([
            'www.alphavantage.co/*' => Http::response([], 503),
        ]);

        $response = $this->getJson('/api/quotation/MSFT?provider=alpha_vantage');

        $response->assertStatus(503)
            ->assertJsonPath('error_code', 'provider_unavailable');
    }

    /**
     * Valida o cenario identificado por `test_can_store_quote_and_prevent_asset_duplication`.
     */
    public function test_can_store_quote_and_prevent_asset_duplication(): void
    {
        Carbon::setTestNow(CarbonImmutable::parse('2026-02-07 12:00:00'));

        try {
            $this->fakeAwesomeSuccess('ETH', 3200.10);

            $first = $this->postJson('/api/quotation/eth', ['type' => 'crypto']);
            $second = $this->postJson('/api/quotation/eth', ['type' => 'crypto']);

            $first->assertCreated()
                ->assertJsonPath('data.symbol', 'ETH')
                ->assertJsonPath('data.status', Quotation::STATUS_VALID);

            $second->assertOk()
                ->assertJsonPath('data.symbol', 'ETH')
                ->assertJsonPath('data.status', Quotation::STATUS_VALID)
                ->assertJsonPath('data.id', $first->json('data.id'));

            $this->assertDatabaseCount((new Asset)->getTable(), 1);
            $this->assertDatabaseCount((new Quotation)->getTable(), 1);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Valida o cenario identificado por `test_lists_saved_quotations_with_filters_and_pagination`.
     */
    public function test_lists_saved_quotations_with_filters_and_pagination(): void
    {
        $stockAsset = Asset::factory()->create(['symbol' => 'MSFT', 'type' => 'stock']);
        $cryptoAsset = Asset::factory()->create(['symbol' => 'BTC', 'type' => 'crypto']);

        Quotation::factory()->create([
            'asset_id' => $stockAsset->id,
            'price' => 500.25,
            'currency' => 'USD',
            'source' => 'alpha_vantage',
            'quoted_at' => Carbon::parse('2026-02-07 10:00:00'),
        ]);

        Quotation::factory()->create([
            'asset_id' => $cryptoAsset->id,
            'price' => 90000.10,
            'currency' => 'USD',
            'source' => 'awesome_api',
            'quoted_at' => Carbon::parse('2026-01-02 10:00:00'),
        ]);

        $response = $this->getJson('/api/quotations?symbol=MSFT&type=stock&source=alpha_vantage&date_from=2026-01-01&date_to=2026-12-31&per_page=1');

        $response->assertOk()
            ->assertJsonPath('data.0.symbol', 'MSFT')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1);
    }

    /**
     * Valida o cenario identificado por `test_marks_extreme_outlier_as_invalid_and_hides_it_from_default_history`.
     */
    public function test_marks_extreme_outlier_as_invalid_and_hides_it_from_default_history(): void
    {
        config([
            'quotations.quality.outlier_guard.min_reference_points' => 3,
            'quotations.quality.outlier_guard.max_deviation_ratio' => 0.5,
        ]);

        $asset = Asset::factory()->create(['symbol' => 'BTC', 'type' => 'crypto']);

        foreach ([68866.17, 68910.25, 68795.88, 68850.31] as $index => $price) {
            Quotation::factory()->create([
                'asset_id' => $asset->id,
                'price' => $price,
                'currency' => 'USD',
                'source' => 'alpha_vantage',
                'quoted_at' => Carbon::parse("2026-02-07 0{$index}:00:00"),
            ]);
        }

        $this->fakeAwesomeSuccess('BTC', 30.99);

        $store = $this->postJson('/api/quotation/BTC', ['type' => 'crypto']);

        $store->assertCreated()
            ->assertJsonPath('data.symbol', 'BTC')
            ->assertJsonPath('data.status', Quotation::STATUS_INVALID)
            ->assertJsonPath('data.invalid_reason', Quotation::INVALID_REASON_OUTLIER);

        $defaultHistory = $this->getJson('/api/quotations?symbol=BTC');
        $defaultHistory->assertOk()
            ->assertJsonPath('meta.total', 4);

        $invalidHistory = $this->getJson('/api/quotations?symbol=BTC&include_invalid=1&status=invalid');
        $invalidHistory->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', Quotation::STATUS_INVALID)
            ->assertJsonPath('data.0.invalid_reason', Quotation::INVALID_REASON_OUTLIER);
    }

    /**
     * Valida o cenario identificado por `test_can_list_invalid_quotations_when_status_filter_is_used`.
     */
    public function test_can_list_invalid_quotations_when_status_filter_is_used(): void
    {
        $asset = Asset::factory()->create(['symbol' => 'BTC', 'type' => 'crypto']);

        Quotation::factory()->create([
            'asset_id' => $asset->id,
            'price' => 68800.10,
            'currency' => 'USD',
            'source' => 'alpha_vantage',
            'status' => Quotation::STATUS_VALID,
        ]);

        Quotation::factory()->create([
            'asset_id' => $asset->id,
            'price' => 30.99,
            'currency' => 'USD',
            'source' => 'alpha_vantage',
            'status' => Quotation::STATUS_INVALID,
            'invalid_reason' => Quotation::INVALID_REASON_OUTLIER,
            'invalidated_at' => Carbon::parse('2026-02-07 10:00:00'),
        ]);

        $defaultHistory = $this->getJson('/api/quotations?symbol=BTC');
        $defaultHistory->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', Quotation::STATUS_VALID);

        $invalidHistory = $this->getJson('/api/quotations?symbol=BTC&include_invalid=1&status=invalid');
        $invalidHistory->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', Quotation::STATUS_INVALID)
            ->assertJsonPath('data.0.invalid_reason', Quotation::INVALID_REASON_OUTLIER);
    }

    /**
     * Valida o cenario identificado por `test_requires_token_when_quotations_auth_is_enabled`.
     */
    public function test_requires_token_when_quotations_auth_is_enabled(): void
    {
        config(['quotations.require_auth' => true]);
        $this->fakeAwesomeSuccess('BTC', 51000.35);

        $unauthorized = $this->getJson('/api/quotation/BTC');
        $unauthorized->assertStatus(401)
            ->assertJsonPath('error_code', 'unauthenticated');

        $user = User::factory()->create();
        $token = $user->createToken('tests')->plainTextToken;

        $authorized = $this->withToken($token)->getJson('/api/quotation/BTC');
        $authorized->assertOk()
            ->assertJsonPath('data.symbol', 'BTC');
    }

    /**
     * Valida o cenario identificado por `test_allows_trusted_gateway_jwt_when_quotations_auth_is_enabled`.
     */
    public function test_allows_trusted_gateway_jwt_when_quotations_auth_is_enabled(): void
    {
        config([
            'quotations.require_auth' => true,
            'gateway.enforce_source' => true,
            'gateway.shared_secret' => 'krakend-internal',
            'gateway.trust_jwt_assertion' => true,
        ]);
        $this->fakeAwesomeSuccess('BTC', 51000.35);

        $response = $this->withHeaders([
            'X-Gateway-Secret' => 'krakend-internal',
            'X-Gateway-Auth' => 'jwt',
            'X-Auth-Roles' => 'reader',
        ])->getJson('/api/quotation/BTC');

        $response->assertOk()
            ->assertJsonPath('data.symbol', 'BTC');
    }

    /**
     * Valida o cenario identificado por `test_single_quotation_delete_requires_authentication_and_uses_soft_delete`.
     */
    public function test_single_quotation_delete_requires_authentication_and_uses_soft_delete(): void
    {
        $quotation = Quotation::factory()->create();

        $unauthorized = $this->deleteJson('/api/quotations/'.$quotation->id);
        $unauthorized->assertStatus(401)
            ->assertJsonPath('error_code', 'unauthenticated');

        $user = User::factory()->admin()->create();
        $token = $user->createToken('quotations-delete')->plainTextToken;

        $authorized = $this->withToken($token)->deleteJson('/api/quotations/'.$quotation->id);
        $authorized->assertOk()
            ->assertJsonPath('data.id', $quotation->id);

        $this->assertSoftDeleted((new Quotation)->getTable(), [
            'id' => $quotation->id,
        ]);

        $activity = Activity::query()
            ->where('event', 'quotation.deleted')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('audit', $activity->log_name);
        $this->assertSame('Quotation soft deleted', $activity->description);
        $this->assertSame($user->id, $activity->causer_id);
        $this->assertSame($quotation->id, $activity->subject_id);
        $this->assertSame($quotation->id, $activity->getExtraProperty('quotation_id'));
        $this->assertSame('DELETE', $activity->getExtraProperty('method'));
        $this->assertSame('api/quotations/'.$quotation->id, $activity->getExtraProperty('path'));
        $this->assertNotEmpty($activity->getExtraProperty('request_id'));
    }

    /**
     * Valida o cenario identificado por `test_gateway_moderator_role_can_delete_without_sanctum_token`.
     */
    public function test_gateway_moderator_role_can_delete_without_sanctum_token(): void
    {
        config([
            'gateway.enforce_source' => true,
            'gateway.shared_secret' => 'krakend-internal',
            'gateway.trust_jwt_assertion' => true,
            'gateway.jwt_moderator_role' => 'moderator',
        ]);
        $quotation = Quotation::factory()->create();

        $authorized = $this->withHeaders([
            'X-Gateway-Secret' => 'krakend-internal',
            'X-Gateway-Auth' => 'jwt',
            'X-Auth-Roles' => 'reader,moderator',
        ])->deleteJson('/api/quotations/'.$quotation->id);

        $authorized->assertOk()
            ->assertJsonPath('data.id', $quotation->id);

        $this->assertSoftDeleted((new Quotation)->getTable(), [
            'id' => $quotation->id,
        ]);
    }

    /**
     * Valida o cenario identificado por `test_single_quotation_delete_succeeds_when_activity_log_table_is_missing`.
     */
    public function test_single_quotation_delete_succeeds_when_activity_log_table_is_missing(): void
    {
        $quotation = Quotation::factory()->create();

        $user = User::factory()->admin()->create();
        $token = $user->createToken('quotations-delete-missing-activity-log')->plainTextToken;

        Schema::connection(config('activitylog.database_connection'))
            ->dropIfExists(config('activitylog.table_name'));

        $response = $this->withToken($token)->deleteJson('/api/quotations/'.$quotation->id);
        $response->assertOk()
            ->assertJsonPath('data.id', $quotation->id);

        $this->assertSoftDeleted((new Quotation)->getTable(), [
            'id' => $quotation->id,
        ]);
    }

    /**
     * Valida o cenario identificado por `test_single_quotation_delete_returns_not_found_when_identifier_does_not_exist`.
     */
    public function test_single_quotation_delete_returns_not_found_when_identifier_does_not_exist(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('quotations-delete-not-found')->plainTextToken;

        $response = $this->withToken($token)->deleteJson('/api/quotations/999999');

        $response->assertNotFound();
    }

    /**
     * Valida o cenario identificado por `test_single_quotation_delete_route_rejects_non_numeric_identifier`.
     */
    public function test_single_quotation_delete_route_rejects_non_numeric_identifier(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('quotations-delete-invalid-id')->plainTextToken;

        $response = $this->withToken($token)->deleteJson('/api/quotations/not-a-number');

        $response->assertNotFound();
    }

    /**
     * Valida o cenario identificado por `test_can_soft_delete_filtered_quotation_history_in_batch`.
     */
    public function test_can_soft_delete_filtered_quotation_history_in_batch(): void
    {
        $btcAsset = Asset::factory()->create(['symbol' => 'BTC', 'type' => 'crypto']);
        $ethAsset = Asset::factory()->create(['symbol' => 'ETH', 'type' => 'crypto']);

        $btcOne = Quotation::factory()->create([
            'asset_id' => $btcAsset->id,
            'quoted_at' => Carbon::parse('2026-02-07 10:00:00'),
        ]);
        $btcTwo = Quotation::factory()->create([
            'asset_id' => $btcAsset->id,
            'quoted_at' => Carbon::parse('2026-02-07 11:00:00'),
        ]);
        $ethOne = Quotation::factory()->create([
            'asset_id' => $ethAsset->id,
            'quoted_at' => Carbon::parse('2026-02-07 12:00:00'),
        ]);

        $user = User::factory()->admin()->create();
        $token = $user->createToken('quotations-delete-batch')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/quotations/bulk-delete', [
            'confirm' => true,
            'symbol' => 'BTC',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.deleted_count', 2);

        $this->assertSoftDeleted((new Quotation)->getTable(), ['id' => $btcOne->id]);
        $this->assertSoftDeleted((new Quotation)->getTable(), ['id' => $btcTwo->id]);
        $this->assertDatabaseHas((new Quotation)->getTable(), [
            'id' => $ethOne->id,
            'deleted_at' => null,
        ]);

        $history = $this->getJson('/api/quotations?include_invalid=1');
        $history->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.symbol', 'ETH');

        $activity = Activity::query()
            ->where('event', 'quotation.batch_deleted')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('audit', $activity->log_name);
        $this->assertSame('Quotation batch soft delete executed', $activity->description);
        $this->assertSame($user->id, $activity->causer_id);
        $this->assertNull($activity->subject_id);
        $this->assertSame(2, $activity->getExtraProperty('deleted_count'));
        $this->assertSame('BTC', $activity->getExtraProperty('filters.symbol'));
        $this->assertFalse($activity->getExtraProperty('delete_all'));
        $this->assertSame('POST', $activity->getExtraProperty('method'));
        $this->assertSame('api/quotations/bulk-delete', $activity->getExtraProperty('path'));
        $this->assertNotEmpty($activity->getExtraProperty('request_id'));
    }

    /**
     * Valida o cenario identificado por `test_batch_delete_requires_delete_all_flag_when_no_filter_is_provided`.
     */
    public function test_batch_delete_requires_delete_all_flag_when_no_filter_is_provided(): void
    {
        Quotation::factory()->count(2)->create();

        $user = User::factory()->admin()->create();
        $token = $user->createToken('quotations-delete-guard')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/quotations/bulk-delete', [
            'confirm' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_error');
    }

    /**
     * Valida o cenario identificado por `test_legacy_batch_delete_route_is_removed`.
     */
    public function test_legacy_batch_delete_route_is_removed(): void
    {
        $quotation = Quotation::factory()->create();
        $user = User::factory()->admin()->create();
        $token = $user->createToken('quotations-delete-legacy-route')->plainTextToken;

        $response = $this->withToken($token)->deleteJson('/api/quotations', [
            'confirm' => true,
            'symbol' => $quotation->asset->symbol,
        ]);

        $response->assertStatus(405)
            ->assertJsonPath('error_code', 'method_not_allowed');

        $this->assertDatabaseHas((new Quotation)->getTable(), [
            'id' => $quotation->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Valida o cenario identificado por `test_batch_delete_with_delete_all_removes_valid_and_invalid_quotations`.
     */
    public function test_batch_delete_with_delete_all_removes_valid_and_invalid_quotations(): void
    {
        $valid = Quotation::factory()->create([
            'status' => Quotation::STATUS_VALID,
        ]);
        $invalid = Quotation::factory()->create([
            'status' => Quotation::STATUS_INVALID,
            'invalid_reason' => Quotation::INVALID_REASON_OUTLIER,
            'invalidated_at' => Carbon::parse('2026-02-07 13:00:00'),
        ]);

        $user = User::factory()->admin()->create();
        $token = $user->createToken('quotations-delete-all')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/quotations/bulk-delete', [
            'confirm' => true,
            'delete_all' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.deleted_count', 2);

        $this->assertSoftDeleted((new Quotation)->getTable(), ['id' => $valid->id]);
        $this->assertSoftDeleted((new Quotation)->getTable(), ['id' => $invalid->id]);
    }

    /**
     * Valida o cenario identificado por `test_non_admin_user_cannot_delete_quotations`.
     */
    public function test_non_admin_user_cannot_delete_quotations(): void
    {
        $quotation = Quotation::factory()->create();
        $user = User::factory()->create(['is_admin' => false]);
        $token = $user->createToken('quotations-delete-forbidden')->plainTextToken;

        $singleDelete = $this->withToken($token)->deleteJson('/api/quotations/'.$quotation->id);
        $singleDelete->assertStatus(403)
            ->assertJsonPath('error_code', 'forbidden');

        $batchDelete = $this->withToken($token)->postJson('/api/quotations/bulk-delete', [
            'confirm' => true,
            'symbol' => $quotation->asset->symbol,
        ]);
        $batchDelete->assertStatus(403)
            ->assertJsonPath('error_code', 'forbidden');

        $this->assertDatabaseHas((new Quotation)->getTable(), [
            'id' => $quotation->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Simula resposta de sucesso da AwesomeAPI para o simbolo informado.
     */
    private function fakeAwesomeSuccess(string $symbol, float $price): void
    {
        Http::fake([
            'economia.awesomeapi.com.br/*' => $this->awesomePayloadResponse($symbol, $price),
        ]);
    }

    /**
     * Simula resposta de sucesso da Alpha Vantage para ativos de acoes.
     */
    private function fakeAlphaSuccess(string $symbol, float $price): void
    {
        Http::fake([
            'www.alphavantage.co/*' => Http::response([
                'Global Quote' => [
                    '01. symbol' => strtoupper($symbol),
                    '05. price' => number_format($price, 2, '.', ''),
                    '07. latest trading day' => '2026-02-07',
                ],
            ], 200),
        ]);
    }

    /**
     * Simula resposta de cambio da Alpha Vantage para ativos cripto/moeda.
     */
    private function fakeAlphaCurrencySuccess(string $from, string $to, float $price): void
    {
        Http::fake([
            'www.alphavantage.co/*' => Http::response([
                'Realtime Currency Exchange Rate' => [
                    '1. From_Currency Code' => strtoupper($from),
                    '2. From_Currency Name' => strtoupper($from),
                    '3. To_Currency Code' => strtoupper($to),
                    '4. To_Currency Name' => strtoupper($to),
                    '5. Exchange Rate' => number_format($price, 2, '.', ''),
                    '6. Last Refreshed' => '2026-02-07 10:00:00',
                ],
            ], 200),
        ]);
    }

    /**
     * Monta payload no formato da AwesomeAPI para o simbolo e preco informados.
     */
    private function awesomePayloadResponse(string $symbol, float $price)
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', $symbol));
        $isCurrency = strlen($normalized) === 6 && ctype_alpha($normalized);
        $code = $isCurrency ? substr($normalized, 0, 3) : $normalized;
        $codeIn = $isCurrency ? substr($normalized, 3, 3) : 'USD';
        $key = "{$code}{$codeIn}";

        return Http::response([
            $key => [
                'code' => $code,
                'codein' => $codeIn,
                'name' => "{$code}/{$codeIn}",
                'bid' => number_format($price, 2, '.', ''),
                'create_date' => now()->toDateTimeString(),
            ],
        ], 200);
    }
}
