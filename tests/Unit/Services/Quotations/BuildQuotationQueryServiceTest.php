<?php

namespace Tests\Unit\Services\Quotations;

use App\Models\Asset;
use App\Models\Quotation;
use App\Services\Quotations\BuildQuotationQueryService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildQuotationQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_defaults_to_valid_quotations_when_include_invalid_is_absent(): void
    {
        $asset = Asset::factory()->create([
            'symbol' => 'BTC',
            'type' => 'crypto',
        ]);

        $valid = Quotation::factory()->create([
            'asset_id' => $asset->id,
            'status' => Quotation::STATUS_VALID,
            'quoted_at' => CarbonImmutable::parse('2026-02-08 10:00:00 UTC'),
        ]);

        $invalid = Quotation::factory()->create([
            'asset_id' => $asset->id,
            'status' => Quotation::STATUS_INVALID,
            'invalid_reason' => Quotation::INVALID_REASON_DUPLICATE,
            'invalidated_at' => CarbonImmutable::parse('2026-02-08 10:10:00 UTC'),
            'quoted_at' => CarbonImmutable::parse('2026-02-08 10:05:00 UTC'),
        ]);

        $resultIds = $this->service()
            ->paginate([], 100)
            ->items;
        $resultIds = $this->extractIds($resultIds);

        $this->assertSame([$valid->id], $resultIds);
        $this->assertNotContains($invalid->id, $resultIds);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_explicit_status_overrides_default_valid_scope(): void
    {
        $asset = Asset::factory()->create([
            'symbol' => 'ETH',
            'type' => 'crypto',
        ]);

        $valid = Quotation::factory()->create([
            'asset_id' => $asset->id,
            'status' => Quotation::STATUS_VALID,
        ]);

        $invalid = Quotation::factory()->create([
            'asset_id' => $asset->id,
            'status' => Quotation::STATUS_INVALID,
            'invalid_reason' => Quotation::INVALID_REASON_OUTLIER,
            'invalidated_at' => CarbonImmutable::parse('2026-02-08 11:00:00 UTC'),
        ]);

        $resultIds = $this->service()
            ->paginate([
                'status' => Quotation::STATUS_INVALID,
            ], 100)
            ->items;
        $resultIds = $this->extractIds($resultIds);

        $this->assertSame([$invalid->id], $resultIds);
        $this->assertNotContains($valid->id, $resultIds);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_applies_combined_symbol_type_source_and_date_filters(): void
    {
        $msft = Asset::factory()->create([
            'symbol' => 'MSFT',
            'type' => 'stock',
        ]);
        $aapl = Asset::factory()->create([
            'symbol' => 'AAPL',
            'type' => 'stock',
        ]);
        $btc = Asset::factory()->create([
            'symbol' => 'BTC',
            'type' => 'crypto',
        ]);

        $matching = Quotation::factory()->create([
            'asset_id' => $msft->id,
            'source' => 'alpha_vantage',
            'quoted_at' => CarbonImmutable::parse('2026-02-08 10:00:00 UTC'),
            'status' => Quotation::STATUS_VALID,
        ]);

        Quotation::factory()->create([
            'asset_id' => $msft->id,
            'source' => 'yahoo_finance',
            'quoted_at' => CarbonImmutable::parse('2026-02-08 10:01:00 UTC'),
            'status' => Quotation::STATUS_VALID,
        ]);

        Quotation::factory()->create([
            'asset_id' => $aapl->id,
            'source' => 'alpha_vantage',
            'quoted_at' => CarbonImmutable::parse('2026-02-08 10:02:00 UTC'),
            'status' => Quotation::STATUS_VALID,
        ]);

        Quotation::factory()->create([
            'asset_id' => $btc->id,
            'source' => 'alpha_vantage',
            'quoted_at' => CarbonImmutable::parse('2026-02-08 10:03:00 UTC'),
            'status' => Quotation::STATUS_VALID,
        ]);

        Quotation::factory()->create([
            'asset_id' => $msft->id,
            'source' => 'alpha_vantage',
            'quoted_at' => CarbonImmutable::parse('2026-02-09 10:00:00 UTC'),
            'status' => Quotation::STATUS_VALID,
        ]);

        $filters = [
            'symbol' => 'MSFT',
            'type' => 'stock',
            'source' => 'alpha_vantage',
            'date_from' => '2026-02-08',
            'date_to' => '2026-02-08',
        ];

        $resultIds = $this->service()
            ->paginate($filters, 100)
            ->items;
        $resultIds = $this->extractIds($resultIds);

        $this->assertSame([$matching->id], $resultIds);

        $mismatchTypeResultIds = $this->service()
            ->paginate([
                ...$filters,
                'type' => 'crypto',
            ], 100)
            ->items;
        $mismatchTypeResultIds = $this->extractIds($mismatchTypeResultIds);

        $this->assertSame([], $mismatchTypeResultIds);
    }

    /**
     * Executa a rotina principal do metodo service.
     */
    private function service(): BuildQuotationQueryService
    {
        return app(BuildQuotationQueryService::class);
    }

    /**
     * @param  array<int, object>  $quotations
     * @return array<int, int>
     */
    private function extractIds(array $quotations): array
    {
        return array_map(
            static fn (object $quotation): int => (int) $quotation->id,
            $quotations
        );
    }
}
