<?php

namespace Tests\Unit;

use App\Data\QuotationQualityResultData;
use App\Domain\Quotations\QuotationInvalidReason;
use App\Domain\Quotations\QuotationStatus;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QuotationQualityResultDataTest extends TestCase
{
    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_builds_valid_result(): void
    {
        $result = QuotationQualityResultData::valid();

        $this->assertTrue($result->isValid());
        $this->assertFalse($result->isInvalid());
        $this->assertNull($result->invalidReason);
        $this->assertNull($result->invalidatedAt);
        $this->assertSame([
            'status' => QuotationStatus::Valid->value,
            'invalid_reason' => null,
            'invalidated_at' => null,
        ], $result->toArray());
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_builds_invalid_results_with_semantic_factories(): void
    {
        $invalidatedAt = Carbon::parse('2026-02-07 16:00:00');
        $outlier = QuotationQualityResultData::invalidOutlier($invalidatedAt);
        $nonPositive = QuotationQualityResultData::invalidNonPositive($invalidatedAt);
        $duplicate = QuotationQualityResultData::invalidDuplicate($invalidatedAt);

        $this->assertTrue($outlier->isInvalid());
        $this->assertTrue($outlier->isOutlier());
        $this->assertSame(QuotationInvalidReason::OutlierPrice->value, $outlier->invalidReason);
        $this->assertTrue($outlier->invalidatedAt?->equalTo($invalidatedAt));

        $this->assertTrue($nonPositive->isInvalid());
        $this->assertTrue($nonPositive->isNonPositive());
        $this->assertSame(QuotationInvalidReason::NonPositivePrice->value, $nonPositive->invalidReason);
        $this->assertTrue($nonPositive->invalidatedAt?->equalTo($invalidatedAt));

        $this->assertTrue($duplicate->isInvalid());
        $this->assertSame(QuotationInvalidReason::DuplicateQuote->value, $duplicate->invalidReason);
        $this->assertTrue($duplicate->invalidatedAt?->equalTo($invalidatedAt));
    }
}
