<?php

namespace App\Domain\Quotations;

use DateTimeInterface;

/**
 * Classifica precos de cotacao usando regras de nao positivo e outlier.
 */
class QuotationQualityService
{
    /**
     * Permite ajustar limites e habilitar/desabilitar a validacao de outlier.
     */
    public function __construct(
        private readonly bool $outlierGuardEnabled = true,
        private readonly int $configuredMinReferencePoints = 4,
        private readonly int $configuredWindowSize = 20,
        private readonly float $configuredMaxDeviationRatio = 0.85,
    ) {}

    /**
     * Classifica o preco informado com base no historico de referencia.
     *
     * @param  array<int, mixed>  $referencePrices
     */
    public function classifyPrice(float $price, array $referencePrices): QuotationQualityResult
    {
        if ($price <= 0) {
            return QuotationQualityResult::invalidNonPositive();
        }

        if (! $this->isOutlierGuardEnabled()) {
            return QuotationQualityResult::valid();
        }

        $normalizedReferencePrices = $this->normalizeReferencePrices($referencePrices);

        if (count($normalizedReferencePrices) < $this->minReferencePoints()) {
            return QuotationQualityResult::valid();
        }

        $median = $this->medianFromNormalizedValues($normalizedReferencePrices);

        if ($median === null || $median <= 0) {
            return QuotationQualityResult::valid();
        }

        $deviationRatio = abs($price - $median) / $median;

        if ($deviationRatio > $this->maxDeviationRatio()) {
            return QuotationQualityResult::invalidOutlier();
        }

        return QuotationQualityResult::valid();
    }

    /**
     * Classifica resultado final para persistencia, incluindo regra de duplicidade por timestamp.
     *
     * @param  array<int, mixed>  $referencePrices
     */
    public function classifyForPersistence(
        float $price,
        array $referencePrices,
        bool $sameTimestampQuotationExists,
        ?DateTimeInterface $classifiedAt = null
    ): QuotationQualityResult {
        if ($sameTimestampQuotationExists) {
            return QuotationQualityResult::invalidDuplicate($classifiedAt);
        }

        $qualityClassification = $this->classifyPrice($price, $referencePrices);

        if ($qualityClassification->isInvalid() && $qualityClassification->invalidatedAt === null) {
            return $qualityClassification->withInvalidatedAt($classifiedAt);
        }

        return $qualityClassification;
    }

    /**
     * Calcula a mediana ignorando valores nao positivos.
     *
     * @param  array<int, mixed>  $values
     */
    public function median(array $values): ?float
    {
        return $this->medianFromNormalizedValues(
            $this->normalizeReferencePrices($values)
        );
    }

    /**
     * Calcula a mediana de uma lista ja normalizada para float positivo.
     *
     * @param  array<int, float>  $values
     */
    private function medianFromNormalizedValues(array $values): ?float
    {
        $count = count($values);

        if ($count === 0) {
            return null;
        }

        sort($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }

        return $values[$middle];
    }

    /**
     * Retorna o minimo de pontos exigido para aplicar deteccao de outlier.
     */
    public function minReferencePoints(): int
    {
        return max(1, $this->configuredMinReferencePoints);
    }

    /**
     * Retorna o tamanho maximo da janela historica considerada na analise.
     */
    public function windowSize(): int
    {
        return max(1, $this->configuredWindowSize);
    }

    /**
     * Indica se a validacao de outlier esta habilitada.
     */
    private function isOutlierGuardEnabled(): bool
    {
        return $this->outlierGuardEnabled;
    }

    /**
     * Retorna o desvio percentual maximo permitido para considerar outlier.
     */
    private function maxDeviationRatio(): float
    {
        return max(0.01, $this->configuredMaxDeviationRatio);
    }

    /**
     * Converte a lista de referencia para floats positivos e remove invalidos.
     *
     * @param  array<int, mixed>  $referencePrices
     * @return array<int, float>
     */
    private function normalizeReferencePrices(array $referencePrices): array
    {
        return array_values(array_filter(
            array_map(
                static fn ($rawPrice): float => (float) $rawPrice,
                $referencePrices
            ),
            static fn (float $normalizedPrice): bool => $normalizedPrice > 0
        ));
    }
}
