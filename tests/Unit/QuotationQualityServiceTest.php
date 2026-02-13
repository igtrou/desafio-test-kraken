<?php

namespace Tests\Unit;

use App\Domain\Quotations\QuotationInvalidReason;
use App\Domain\Quotations\QuotationQualityResult;
use App\Domain\Quotations\QuotationQualityService;
use App\Domain\Quotations\QuotationStatus;
use DateTimeImmutable;
use Tests\TestCase;

class QuotationQualityServiceTest extends TestCase
{
    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_classify_price_returns_dto(): void
    {
        $service = $this->service();
        $result = $service->classifyPrice(100.0, [99.0, 100.0, 101.0]);

        $this->assertInstanceOf(QuotationQualityResult::class, $result);
        $this->assertTrue($result->isValid());
        $this->assertSame(QuotationStatus::Valid->value, $result->status);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_classify_price_marks_non_positive_values_as_invalid(): void
    {
        $service = $this->service();
        $result = $service->classifyPrice(0.0, [99.0, 100.0, 101.0]);

        $this->assertTrue($result->isInvalid());
        $this->assertTrue($result->isNonPositive());
        $this->assertSame(QuotationInvalidReason::NonPositivePrice->value, $result->invalidReason);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_classify_price_marks_outliers_as_invalid(): void
    {
        $service = $this->service();
        $result = $service->classifyPrice(30.0, [99.0, 100.0, 101.0, 100.5]);

        $this->assertTrue($result->isInvalid());
        $this->assertTrue($result->isOutlier());
        $this->assertSame(QuotationInvalidReason::OutlierPrice->value, $result->invalidReason);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_classify_price_stays_valid_when_outlier_guard_is_disabled(): void
    {
        $service = $this->service(outlierGuardEnabled: false);
        $result = $service->classifyPrice(30.0, [99.0, 100.0, 101.0, 100.5]);

        $this->assertTrue($result->isValid());
        $this->assertNull($result->invalidReason);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_classify_for_persistence_marks_same_timestamp_as_duplicate(): void
    {
        $service = $this->service();
        $classifiedAt = new DateTimeImmutable('2026-02-08 12:00:00');
        $result = $service->classifyForPersistence(
            price: 101.5,
            referencePrices: [100.0, 101.0, 102.0],
            sameTimestampQuotationExists: true,
            classifiedAt: $classifiedAt
        );

        $this->assertTrue($result->isInvalid());
        $this->assertSame(QuotationInvalidReason::DuplicateQuote->value, $result->invalidReason);
        $this->assertSame($classifiedAt, $result->invalidatedAt);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_classify_for_persistence_stamps_timestamp_when_invalid_without_timestamp(): void
    {
        $service = $this->service();
        $classifiedAt = new DateTimeImmutable('2026-02-08 13:00:00');
        $result = $service->classifyForPersistence(
            price: 30.0,
            referencePrices: [99.0, 100.0, 101.0, 100.5],
            sameTimestampQuotationExists: false,
            classifiedAt: $classifiedAt
        );

        $this->assertTrue($result->isInvalid());
        $this->assertSame(QuotationInvalidReason::OutlierPrice->value, $result->invalidReason);
        $this->assertSame($classifiedAt, $result->invalidatedAt);
    }

    /**
     * Executa a rotina principal do metodo service.
     */
    private function service(bool $outlierGuardEnabled = true): QuotationQualityService
    {
        return new QuotationQualityService(
            outlierGuardEnabled: $outlierGuardEnabled,
            configuredMinReferencePoints: 3,
            configuredWindowSize: 20,
            configuredMaxDeviationRatio: 0.5
        );
    }
}
