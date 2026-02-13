<?php

namespace App\Console\Commands;

use App\Actions\Quotations\CollectConfiguredQuotationsAction;
use App\Actions\Quotations\RecordQuotationCollectionFinishedAction;
use App\Actions\Quotations\RecordQuotationCollectionStartedAction;
use App\Services\Quotations\AutoCollectCancellationService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Executa coleta de cotacoes por simbolo, com suporte a dry-run e sucesso parcial.
 */
class CollectQuotationsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'quotations:collect
        {--symbol=* : Symbols to collect}
        {--provider= : Explicit provider to use}
        {--dry-run : Fetch only without persisting}
        {--ignore-config-provider : Ignore QUOTATIONS_AUTO_COLLECT_PROVIDER and use fallback strategy}
        {--allow-partial-success : Return success when at least one symbol succeeds}
        {--trigger=manual : Execution trigger (manual|dashboard|scheduler)}';

    /**
     * @var string
     */
    protected $description = 'Collect configured quotations and persist them';

    /**
     * Injeta actions de coleta e registro do ciclo de execucao.
     */
    public function __construct(
        private readonly CollectConfiguredQuotationsAction $collector,
        private readonly RecordQuotationCollectionStartedAction $recordExecutionStarted,
        private readonly RecordQuotationCollectionFinishedAction $recordExecutionFinished,
        private readonly AutoCollectCancellationService $cancellation,
    ) {
        parent::__construct();
    }

    /**
     * Processa coleta de cotacoes e retorna codigo de saida compativel com CLI.
     */
    public function handle(): int
    {
        $runId = (string) Str::uuid();
        $startedAt = now('UTC');
        $symbolsToCollect = $this->option('symbol');
        $providerOption = trim((string) ($this->option('provider') ?? ''));
        $ignoreConfigProvider = (bool) $this->option('ignore-config-provider');
        $allowPartialSuccess = (bool) $this->option('allow-partial-success');
        $trigger = $this->resolveTrigger((string) ($this->option('trigger') ?? 'manual'));
        $requestedProvider = $providerOption !== '' ? $providerOption : null;
        $provider = $providerOption !== '' ? $providerOption : null;
        $dryRun = (bool) $this->option('dry-run');

        if ($provider === null && ! $ignoreConfigProvider) {
            $configuredProvider = trim((string) config('quotations.auto_collect.provider', ''));
            $provider = $configuredProvider !== '' ? $configuredProvider : null;
        }

        $providerSource = $requestedProvider !== null
            ? 'option'
            : ($provider !== null ? 'config' : 'fallback');

        if ($symbolsToCollect === []) {
            $symbolsToCollect = config('quotations.auto_collect.symbols', []);
        }

        if (! is_array($symbolsToCollect) || $symbolsToCollect === []) {
            $errorMessage = 'No symbols configured. Use --symbol=BTC or set QUOTATIONS_AUTO_COLLECT_SYMBOLS.';
            $this->warn($errorMessage);
            $finishedAt = now('UTC');

            ($this->recordExecutionFinished)([
                'run_id' => $runId,
                'trigger' => $trigger,
                'started_at' => $startedAt->toIso8601String(),
                'finished_at' => $finishedAt->toIso8601String(),
                'duration_ms' => $startedAt->diffInMilliseconds($finishedAt),
                'requested_provider' => $requestedProvider,
                'effective_provider' => $provider,
                'provider_source' => $providerSource,
                'dry_run' => $dryRun,
                'ignore_config_provider' => $ignoreConfigProvider,
                'allow_partial_success' => $allowPartialSuccess,
                'symbols' => [],
                'summary' => [
                    'total' => 0,
                    'success' => 0,
                    'failed' => 0,
                ],
                'status' => 'failed',
                'exit_code' => SymfonyCommand::FAILURE,
                'items' => [],
                'error_message' => $errorMessage,
            ]);

            return SymfonyCommand::FAILURE;
        }

        $symbolsToCollect = array_values(array_filter(array_map('strval', $symbolsToCollect)));

        ($this->recordExecutionStarted)([
            'run_id' => $runId,
            'trigger' => $trigger,
            'started_at' => $startedAt->toIso8601String(),
            'requested_provider' => $requestedProvider,
            'effective_provider' => $provider,
            'provider_source' => $providerSource,
            'dry_run' => $dryRun,
            'ignore_config_provider' => $ignoreConfigProvider,
            'allow_partial_success' => $allowPartialSuccess,
            'symbols' => $symbolsToCollect,
        ]);

        $this->info(sprintf(
            'Collecting %d symbol(s)%s%s...',
            count($symbolsToCollect),
            $provider ? " with provider [{$provider}]" : '',
            $dryRun ? ' in dry-run mode' : ''
        ));

        $collectionResult = ($this->collector)(
            $symbolsToCollect,
            $provider ?: null,
            $dryRun,
            $runId
        );

        foreach ($collectionResult->items as $collectionItem) {
            if ($collectionItem->isOk()) {
                $this->line(sprintf(
                    'OK: %s | source=%s | price=%s%s',
                    $collectionItem->symbol,
                    $collectionItem->source,
                    $collectionItem->price,
                    $collectionItem->quotationId !== null
                        ? " | quotation_id={$collectionItem->quotationId}"
                        : ''
                ));

                continue;
            }

            $this->error(sprintf('ERROR: %s | %s', $collectionItem->symbol, $collectionItem->message ?? 'Unknown error'));
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. total=%d success=%d failed=%d',
            $collectionResult->total,
            $collectionResult->success,
            $collectionResult->failed
        ));

        $exitCode = SymfonyCommand::SUCCESS;

        if ($collectionResult->canceled) {
            $this->warn('Execution canceled by user request.');
            $exitCode = SymfonyCommand::FAILURE;
        } elseif ($collectionResult->failed > 0) {
            if ($allowPartialSuccess && $collectionResult->success > 0) {
                $this->warn('Partial success mode is enabled: successful symbols were processed and failed symbols were skipped.');
            } else {
                $exitCode = SymfonyCommand::FAILURE;
            }
        }

        $finishedAt = now('UTC');
        $status = $collectionResult->canceled
            ? 'canceled'
            : ($collectionResult->failed === 0
                ? 'success'
                : ($collectionResult->success > 0 ? 'partial' : 'failed'));

        ($this->recordExecutionFinished)([
            'run_id' => $runId,
            'trigger' => $trigger,
            'started_at' => $startedAt->toIso8601String(),
            'finished_at' => $finishedAt->toIso8601String(),
            'duration_ms' => $startedAt->diffInMilliseconds($finishedAt),
            'requested_provider' => $requestedProvider,
            'effective_provider' => $provider,
            'provider_source' => $providerSource,
            'dry_run' => $dryRun,
            'ignore_config_provider' => $ignoreConfigProvider,
            'allow_partial_success' => $allowPartialSuccess,
            'symbols' => $symbolsToCollect,
            'summary' => [
                'total' => $collectionResult->total,
                'success' => $collectionResult->success,
                'failed' => $collectionResult->failed,
            ],
            'status' => $status,
            'exit_code' => $exitCode,
            'items' => array_map(
                static fn ($item): array => $item->toArray(),
                $collectionResult->items
            ),
            'canceled' => $collectionResult->canceled,
        ]);

        $this->cancellation->clear();

        return $exitCode;
    }

    /**
     * Normaliza trigger informado na CLI para valores aceitos.
     */
    private function resolveTrigger(string $triggerOption): string
    {
        $trigger = strtolower(trim($triggerOption));

        return in_array($trigger, ['manual', 'dashboard', 'scheduler'], true)
            ? $trigger
            : 'manual';
    }
}
