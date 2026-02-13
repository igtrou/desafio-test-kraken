<?php

namespace App\Data;

/**
 * DTO imutavel que representa os resultados da execucao de reconciliacao de cotacoes.
 */
class ReconcileQuotationsResultData
{
    /**
     * Armazena contadores gerados durante a reconciliacao de cotacoes.
     */
    public function __construct(
        public readonly int $scanned,
        public readonly int $duplicatesInvalidated,
        public readonly int $outliersInvalidated,
        public readonly int $nonPositiveInvalidated,
        public readonly int $totalInvalidated,
        public readonly bool $dryRun
    ) {}

    /**
     * @return array{
     *     scanned: int,
     *     duplicates_invalidated: int,
     *     outliers_invalidated: int,
     *     non_positive_invalidated: int,
     *     total_invalidated: int,
     *     dry_run: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'scanned' => $this->scanned,
            'duplicates_invalidated' => $this->duplicatesInvalidated,
            'outliers_invalidated' => $this->outliersInvalidated,
            'non_positive_invalidated' => $this->nonPositiveInvalidated,
            'total_invalidated' => $this->totalInvalidated,
            'dry_run' => $this->dryRun,
        ];
    }
}
