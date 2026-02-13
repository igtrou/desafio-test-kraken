<?php

namespace Tests\Unit;

use App\Data\ReconcileQuotationsResultData;
use PHPUnit\Framework\TestCase;

class ReconcileQuotationsResultDataTest extends TestCase
{
    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_serializes_reconciliation_payload(): void
    {
        $result = new ReconcileQuotationsResultData(
            scanned: 10,
            duplicatesInvalidated: 2,
            outliersInvalidated: 3,
            nonPositiveInvalidated: 1,
            totalInvalidated: 6,
            dryRun: true
        );

        $this->assertSame([
            'scanned' => 10,
            'duplicates_invalidated' => 2,
            'outliers_invalidated' => 3,
            'non_positive_invalidated' => 1,
            'total_invalidated' => 6,
            'dry_run' => true,
        ], $result->toArray());
    }
}
