<?php

namespace Tests\Unit;

use App\Data\CollectedQuotationItemData;
use PHPUnit\Framework\TestCase;

class CollectedQuotationItemDataTest extends TestCase
{
    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_builds_ok_item_with_expected_payload(): void
    {
        $item = CollectedQuotationItemData::ok(
            symbol: 'BTC',
            source: 'awesome_api',
            price: 51000.35,
            quotationId: 10
        );

        $this->assertTrue($item->isOk());
        $this->assertFalse($item->isError());
        $this->assertSame([
            'symbol' => 'BTC',
            'status' => 'ok',
            'source' => 'awesome_api',
            'price' => 51000.35,
            'quotation_id' => 10,
        ], $item->toArray());
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_builds_error_item_with_expected_payload(): void
    {
        $item = CollectedQuotationItemData::error('BTC$', 'Invalid symbol format.');

        $this->assertFalse($item->isOk());
        $this->assertTrue($item->isError());
        $this->assertSame([
            'symbol' => 'BTC$',
            'status' => 'error',
            'message' => 'Invalid symbol format.',
        ], $item->toArray());
    }
}
