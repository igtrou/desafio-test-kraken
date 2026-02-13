<?php

namespace Tests\Unit\Services\Quotations;

use App\Domain\MarketData\AssetType;
use App\Domain\MarketData\Quote;
use App\Models\Asset;
use App\Models\Quotation;
use App\Services\Quotations\PersistQuotationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersistQuotationServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara o cenario base para a execucao do teste.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'quotations.quality.outlier_guard.enabled' => false,
        ]);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_creates_asset_and_quotation_when_symbol_does_not_exist(): void
    {
        $service = $this->service();
        $quote = $this->quoteData(
            symbol: 'btc',
            name: 'Bitcoin',
            type: 'crypto',
            price: 51000.35,
            source: 'awesome_api',
            quotedAt: CarbonImmutable::parse('2026-02-08 10:00:00 UTC')
        );

        $storedQuotation = $service->handle($quote);

        $this->assertDatabaseCount((new Asset)->getTable(), 1);
        $this->assertDatabaseCount((new Quotation)->getTable(), 1);
        $this->assertSame('BTC', $storedQuotation['symbol']);
        $this->assertSame(AssetType::Crypto->value, $storedQuotation['type']);
        $this->assertSame('awesome_api', $storedQuotation['source']);
        $this->assertSame(Quotation::STATUS_VALID, $storedQuotation['status']);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_updates_asset_metadata_when_quote_name_or_type_changes(): void
    {
        $asset = Asset::factory()->create([
            'symbol' => 'BTC',
            'name' => 'Old BTC Name',
            'type' => AssetType::Stock->value,
        ]);

        $service = $this->service();
        $quote = $this->quoteData(
            symbol: 'BTC',
            name: 'Bitcoin Updated',
            type: 'crypto',
            price: 52000.00,
            source: 'alpha_vantage',
            quotedAt: CarbonImmutable::parse('2026-02-08 11:00:00 UTC')
        );

        $service->handle($quote);
        $asset->refresh();

        $this->assertSame('Bitcoin Updated', $asset->name);
        $this->assertSame(AssetType::Crypto, $asset->type);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_deduplicates_exact_same_quote(): void
    {
        $service = $this->service();
        $quote = $this->quoteData(
            symbol: 'ETH',
            name: 'Ethereum',
            type: 'crypto',
            price: 3200.10,
            source: 'awesome_api',
            quotedAt: CarbonImmutable::parse('2026-02-08 12:00:00 UTC')
        );

        $first = $service->handle($quote);
        $second = $service->handle($quote);

        $this->assertSame($first['id'], $second['id']);
        $this->assertDatabaseCount((new Asset)->getTable(), 1);
        $this->assertDatabaseCount((new Quotation)->getTable(), 1);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_marks_quote_as_invalid_duplicate_when_same_timestamp_exists_with_different_price(): void
    {
        $service = $this->service();
        $quotedAt = CarbonImmutable::parse('2026-02-08 13:00:00 UTC');

        $first = $service->handle($this->quoteData(
            symbol: 'MSFT',
            name: 'Microsoft',
            type: 'stock',
            price: 410.10,
            source: 'alpha_vantage',
            quotedAt: $quotedAt
        ));

        $second = $service->handle($this->quoteData(
            symbol: 'MSFT',
            name: 'Microsoft',
            type: 'stock',
            price: 411.55,
            source: 'alpha_vantage',
            quotedAt: $quotedAt
        ));

        $this->assertNotSame($first['id'], $second['id']);
        $this->assertSame(Quotation::STATUS_VALID, $first['status']);
        $this->assertSame(Quotation::STATUS_INVALID, $second['status']);
        $this->assertSame(Quotation::INVALID_REASON_DUPLICATE, $second['invalid_reason']);
        $this->assertNotNull($second['invalidated_at']);
        $this->assertDatabaseCount((new Quotation)->getTable(), 2);
    }

    /**
     * Executa a rotina principal do metodo service.
     */
    private function service(): PersistQuotationService
    {
        return app(PersistQuotationService::class);
    }

    /**
     * Executa a rotina principal do metodo quoteData.
     */
    private function quoteData(
        string $symbol,
        string $name,
        string $type,
        float $price,
        string $source,
        CarbonImmutable $quotedAt
    ): Quote {
        return new Quote(
            symbol: $symbol,
            name: $name,
            type: $type,
            price: $price,
            currency: 'USD',
            source: $source,
            quotedAt: $quotedAt
        );
    }
}
