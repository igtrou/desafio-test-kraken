<?php

namespace App\Actions\Quotations;

use App\Data\ReconcileQuotationsResultData;
use App\Services\Quotations\ReconcileQuotationHistoryService;

/**
 * Reconcilia historico de cotacoes armazenadas, invalidando duplicidades e anomalias.
 */
class ReconcileQuotationHistoryAction
{
    /**
     * Injeta o servico de reconciliacao do historico de cotacoes.
     */
    public function __construct(
        private readonly ReconcileQuotationHistoryService $reconcileQuotationHistory,
    ) {}

    /**
     * Executa reconciliacao geral ou filtrada por simbolos.
     *
     * @param  array<int, string>  $symbols
     * @param  bool  $dryRun Quando true, calcula metricas sem persistir alteracoes.
     */
    public function __invoke(array $symbols = [], bool $dryRun = false): ReconcileQuotationsResultData
    {
        $result = $this->reconcileQuotationHistory->handle($symbols, $dryRun);

        return new ReconcileQuotationsResultData(
            scanned: $result['scanned'],
            duplicatesInvalidated: $result['duplicates_invalidated'],
            outliersInvalidated: $result['outliers_invalidated'],
            nonPositiveInvalidated: $result['non_positive_invalidated'],
            totalInvalidated: $result['total_invalidated'],
            dryRun: $result['dry_run']
        );
    }
}
