<?php

namespace App\Console\Commands;

use App\Actions\Quotations\ReconcileQuotationHistoryAction;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Reconcilia historico de cotacoes invalidando registros inconsistentes.
 */
class ReconcileQuotationsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'quotations:reconcile
        {--symbol=* : Restrict reconciliation to specific symbols}
        {--dry-run : Preview only, without updating records}';

    /**
     * @var string
     */
    protected $description = 'Marks duplicate and anomalous quotations as invalid without deleting historical rows';

    /**
     * Injeta action responsavel pela reconciliacao do historico.
     */
    public function __construct(
        private readonly ReconcileQuotationHistoryAction $reconcileQuotationHistory,
    ) {
        parent::__construct();
    }

    /**
     * Executa reconciliacao e exibe resumo final no terminal.
     */
    public function handle(): int
    {
        $symbolFilters = array_values(array_filter(array_map('strval', $this->option('symbol') ?? [])));
        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            'Reconciling quotation history%s%s...',
            $symbolFilters !== [] ? ' for symbols ['.implode(', ', $symbolFilters).']' : '',
            $dryRun ? ' in dry-run mode' : ''
        ));

        $reconciliationResult = ($this->reconcileQuotationHistory)($symbolFilters, $dryRun);

        $this->line(sprintf('Scanned: %d', $reconciliationResult->scanned));
        $this->line(sprintf('Duplicates invalidated: %d', $reconciliationResult->duplicatesInvalidated));
        $this->line(sprintf('Outliers invalidated: %d', $reconciliationResult->outliersInvalidated));
        $this->line(sprintf('Non-positive invalidated: %d', $reconciliationResult->nonPositiveInvalidated));
        $this->line(sprintf('Total invalidated: %d', $reconciliationResult->totalInvalidated));

        if ($dryRun) {
            $this->warn('Dry-run complete. No records were changed.');
        } else {
            $this->info('Reconciliation complete.');
        }

        return SymfonyCommand::SUCCESS;
    }
}
