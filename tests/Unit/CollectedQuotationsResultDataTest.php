<?php

namespace Tests\Unit;

use App\Data\CollectedQuotationItemData;
use App\Data\CollectedQuotationsResultData;
use PHPUnit\Framework\TestCase;

class CollectedQuotationsResultDataTest extends TestCase
{
    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_serializes_aggregate_payload_with_items(): void
    {
        $result = new CollectedQuotationsResultData(
            total: 2,
            success: 1,
            failed: 1,
            items: [
                CollectedQuotationItemData::ok('BTC', 'awesome_api', 51000.35, 100),
                CollectedQuotationItemData::error('BTC$', 'Invalid symbol format.'),
            ],
            canceled: true
        );

        $this->assertSame([
            'total' => 2,
            'success' => 1,
            'failed' => 1,
            'items' => [
                [
                    'symbol' => 'BTC',
                    'status' => 'ok',
                    'source' => 'awesome_api',
                    'price' => 51000.35,
                    'quotation_id' => 100,
                ],
                [
                    'symbol' => 'BTC$',
                    'status' => 'error',
                    'message' => 'Invalid symbol format.',
                ],
            ],
            'canceled' => true,
        ], $result->toArray());
    }
}
