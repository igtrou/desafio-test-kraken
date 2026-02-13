<?php

namespace App\Services\Quotations;

use App\Domain\Contracts\QuotationReconciliationRepositoryPort;
use App\Domain\MarketData\SymbolNormalizer;
use App\Domain\Quotations\QuotationInvalidReason;
use App\Domain\Quotations\QuotationQualityService;
use App\Domain\Quotations\QuotationStatus;

/**
 * Reconcilia historico de cotacoes invalidando duplicidades e anomalias.
 */
class ReconcileQuotationHistoryService
{
    /**
     * Injeta servicos de qualidade, normalizacao e repositorio de reconciliacao.
     */
    public function __construct(
        private readonly QuotationQualityService $quotationQuality,
        private readonly SymbolNormalizer $symbolNormalizer,
        private readonly QuotationReconciliationRepositoryPort $reconciliationRepository,
    ) {}

    /**
     * Executa reconciliacao completa ou filtrada por simbolos.
     *
     * @param  array<int, string>  $symbols
     * @param  bool  $dryRun Quando true, calcula metricas sem persistir alteracoes.
     * @return array{
     *     scanned: int,
     *     duplicates_invalidated: int,
     *     outliers_invalidated: int,
     *     non_positive_invalidated: int,
     *     total_invalidated: int,
     *     dry_run: bool
     * }
     */
    public function handle(array $symbols = [], bool $dryRun = false): array
    {
        $symbolFilters = array_values(array_unique(array_filter(array_map(
            fn ($value): string => $this->symbolNormalizer->normalize((string) $value),
            $symbols
        ))));
        $validStatus = QuotationStatus::Valid->value;
        $invalidStatus = QuotationStatus::Invalid->value;

        $scannedQuotations = $this->reconciliationRepository->countScoped($symbolFilters);
        $duplicateQuotationIds = $this->reconciliationRepository->findDuplicateIdsToInvalidate(
            $symbolFilters,
            $validStatus
        );
        $nonPositiveQuotationIds = [];
        $outlierQuotationIds = [];

        if (! $dryRun) {
            $this->reconciliationRepository->invalidateByIds(
                $duplicateQuotationIds,
                $validStatus,
                $invalidStatus,
                QuotationInvalidReason::DuplicateQuote->value
            );
        }

        $validQuotationCandidates = $this->reconciliationRepository->listValidCandidates(
            $symbolFilters,
            $validStatus,
            $dryRun ? $duplicateQuotationIds : []
        );

        $quotationsGroupedByAssetAndCurrency = [];

        foreach ($validQuotationCandidates as $quotationCandidate) {
            $groupKey = $quotationCandidate['asset_id'].'|'.$quotationCandidate['currency'];
            $quotationsGroupedByAssetAndCurrency[$groupKey][] = $quotationCandidate;
        }

        foreach ($quotationsGroupedByAssetAndCurrency as $quotationGroup) {
            $groupReferencePrices = array_column($quotationGroup, 'price');

            foreach ($quotationGroup as $quotation) {
                $qualityClassification = $this->quotationQuality->classifyPrice(
                    (float) $quotation['price'],
                    $groupReferencePrices
                );

                if (! $qualityClassification->isInvalid()) {
                    continue;
                }

                if ($qualityClassification->isNonPositive()) {
                    $nonPositiveQuotationIds[] = $quotation['id'];

                    continue;
                }

                if ($qualityClassification->isOutlier()) {
                    $outlierQuotationIds[] = $quotation['id'];
                }
            }
        }

        $outlierQuotationIds = array_values(array_unique($outlierQuotationIds));
        $nonPositiveQuotationIds = array_values(array_unique($nonPositiveQuotationIds));

        if (! $dryRun) {
            $this->reconciliationRepository->invalidateByIds(
                $outlierQuotationIds,
                $validStatus,
                $invalidStatus,
                QuotationInvalidReason::OutlierPrice->value
            );
            $this->reconciliationRepository->invalidateByIds(
                $nonPositiveQuotationIds,
                $validStatus,
                $invalidStatus,
                QuotationInvalidReason::NonPositivePrice->value
            );
        }

        $duplicatesInvalidatedCount = count($duplicateQuotationIds);
        $outliersInvalidatedCount = count($outlierQuotationIds);
        $nonPositiveInvalidatedCount = count($nonPositiveQuotationIds);

        return [
            'scanned' => $scannedQuotations,
            'duplicates_invalidated' => $duplicatesInvalidatedCount,
            'outliers_invalidated' => $outliersInvalidatedCount,
            'non_positive_invalidated' => $nonPositiveInvalidatedCount,
            'total_invalidated' => $duplicatesInvalidatedCount + $outliersInvalidatedCount + $nonPositiveInvalidatedCount,
            'dry_run' => $dryRun,
        ];
    }
}
