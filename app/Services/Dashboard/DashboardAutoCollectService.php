<?php

namespace App\Services\Dashboard;

use App\Domain\Contracts\ApplicationEnvironmentPort;
use App\Domain\Contracts\ConfigCachePort;
use App\Domain\Contracts\EnvFileEditorPort;
use App\Domain\Contracts\QuotationCollectCommandRunnerPort;
use App\Domain\Contracts\QuotationCollectExecutionLoggerPort;
use App\Domain\Contracts\QuotationsConfigPort;
use App\Domain\MarketData\AssetTypeResolver;
use App\Domain\MarketData\SymbolNormalizer;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Orquestra leitura, atualizacao e execucao manual do auto-collect no dashboard.
 */
class DashboardAutoCollectService
{
    /**
     * Injeta dependencias de configuracao, observabilidade e execucao de comandos.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly EnvFileEditorPort $envFileEditor,
        private readonly ConfigCachePort $configCacheManager,
        private readonly ApplicationEnvironmentPort $applicationEnvironment,
        private readonly QuotationsConfigPort $quotationsConfig,
        private readonly QuotationCollectExecutionLoggerPort $executionLogger,
        private readonly QuotationCollectCommandRunnerPort $commandRunner,
        private readonly SymbolNormalizer $symbolNormalizer,
        private readonly AssetTypeResolver $assetTypeResolver,
        private readonly AutoCollectHealthBaselineService $healthBaseline,
    ) {}

    /**
     * Retorna as configuracoes efetivas atuais da coleta automatica.
     *
     * @return array{
     *     enabled: bool,
     *     interval_minutes: int,
     *     symbols: array<int, string>,
     *     provider: string|null,
     *     available_providers: array<int, string>,
     *     cron_expression: string,
     *     requires_scheduler_restart: bool,
     *     scheduler_restart_note: string
     * }
     */
    /**
     * Executa a rotina principal do metodo currentSettings.
     */
    public function currentSettings(): array
    {
        return $this->buildSettings(
            enabled: $this->quotationsConfig->autoCollectEnabled(),
            intervalMinutes: $this->quotationsConfig->autoCollectIntervalMinutes(),
            symbols: $this->quotationsConfig->autoCollectSymbols(),
            provider: $this->quotationsConfig->autoCollectProvider()
        );
    }

    /**
     * Persiste novas configuracoes de auto-collect no arquivo de ambiente.
     *
     * @param  array{
     *     enabled?: bool,
     *     interval_minutes?: int,
     *     symbols?: array<int, string>,
     *     provider?: string|null
     * }  $validated
     * @return array{
     *     enabled: bool,
     *     interval_minutes: int,
     *     symbols: array<int, string>,
     *     provider: string|null,
     *     available_providers: array<int, string>,
     *     cron_expression: string,
     *     requires_scheduler_restart: bool,
     *     scheduler_restart_note: string
     * }
     */
    /**
     * Executa a rotina principal do metodo updateSettings.
     */
    public function updateSettings(array $validated): array
    {
        $enabled = (bool) ($validated['enabled'] ?? false);
        $intervalMinutes = max(1, min(59, (int) ($validated['interval_minutes'] ?? 15)));
        $symbols = array_values(array_unique(array_filter(array_map(
            'strval',
            $validated['symbols'] ?? []
        ))));
        $provider = isset($validated['provider']) && $validated['provider'] !== ''
            ? (string) $validated['provider']
            : null;

        $this->envFileEditor->update([
            'QUOTATIONS_AUTO_COLLECT_ENABLED' => $enabled,
            'QUOTATIONS_AUTO_COLLECT_INTERVAL_MINUTES' => $intervalMinutes,
            'QUOTATIONS_AUTO_COLLECT_SYMBOLS' => implode(',', $symbols),
            'QUOTATIONS_AUTO_COLLECT_PROVIDER' => $provider,
        ]);

        if (! $this->applicationEnvironment->isTesting()) {
            $this->configCacheManager->clear();
        }

        return $this->buildSettings(
            enabled: $enabled,
            intervalMinutes: $intervalMinutes,
            symbols: $symbols,
            provider: $provider
        );
    }

    /**
     * Executa uma coleta manual a partir dos parametros enviados pelo dashboard.
     *
     * @param  array{
     *     symbols?: array<int, string>,
     *     provider?: string|null,
     *     dry_run?: bool,
     *     force_provider?: bool
     * }  $validated
     * @return array{
     *     exit_code: int,
     *     dry_run: bool,
     *     force_provider: bool,
     *     allow_partial_success: bool,
     *     symbols: array<int, string>,
     *     requested_provider: string|null,
     *     effective_provider: string|null,
     *     auto_fallback_applied: bool,
     *     warnings: array<int, string>,
     *     summary: array{total: int, success: int, failed: int}|null,
     *     output: array<int, string>
     * }
     */
    /**
     * Executa a rotina principal do metodo run.
     */
    public function run(array $validated): array
    {
        $symbols = array_values(array_filter(array_map(
            'strval',
            $validated['symbols'] ?? []
        )));
        $requestedProvider = isset($validated['provider']) && $validated['provider'] !== ''
            ? (string) $validated['provider']
            : null;
        $dryRun = (bool) ($validated['dry_run'] ?? false);
        $forceProvider = (bool) ($validated['force_provider'] ?? false);
        $effectiveProvider = $requestedProvider;
        $autoFallbackApplied = false;
        $warnings = [];

        $symbolsForAnalysis = $symbols !== []
            ? $symbols
            : $this->quotationsConfig->autoCollectSymbols();

        if (
            $requestedProvider !== null
            && ! $forceProvider
            && $this->hasMixedAssetTypes($symbolsForAnalysis)
        ) {
            $effectiveProvider = null;
            $autoFallbackApplied = true;
            $warnings[] = 'Mixed asset types detected. Fixed provider was ignored and fallback strategy was applied.';
        }

        $commandArguments = [];

        if ($symbols !== []) {
            $commandArguments['--symbol'] = $symbols;
        }

        if ($effectiveProvider !== null) {
            $commandArguments['--provider'] = $effectiveProvider;
        }

        if ($dryRun) {
            $commandArguments['--dry-run'] = true;
        }

        if ($effectiveProvider === null) {
            $commandArguments['--ignore-config-provider'] = true;
        }

        $commandArguments['--allow-partial-success'] = true;
        $commandArguments['--trigger'] = 'dashboard';

        $commandExecution = $this->commandRunner->run($commandArguments);
        $outputLines = $commandExecution['output'] === ''
            ? []
            : (preg_split('/\R/', $commandExecution['output']) ?: []);

        return [
            'exit_code' => $commandExecution['exit_code'],
            'dry_run' => $dryRun,
            'force_provider' => $forceProvider,
            'allow_partial_success' => true,
            'symbols' => $symbols,
            'requested_provider' => $requestedProvider,
            'effective_provider' => $effectiveProvider,
            'auto_fallback_applied' => $autoFallbackApplied,
            'warnings' => $warnings,
            'summary' => $this->extractCommandSummary($outputLines),
            'output' => $outputLines,
        ];
    }

    /**
     * Lista execucoes recentes registradas no logger operacional.
     *
     * @return array<int, array<string, mixed>>
     */
    /**
     * Executa a rotina principal do metodo history.
     */
    public function history(int $limit = 20): array
    {
        $safeLimit = max(1, min(100, $limit));
        $entries = $this->executionLogger->latest($safeLimit);
        $baselineStartedAt = $this->healthBaseline->current();

        if ($baselineStartedAt === null) {
            return $entries;
        }

        return array_values(array_filter(
            $entries,
            fn (array $entry): bool => $this->isEntryVisibleAfterBaseline($entry, $baselineStartedAt)
        ));
    }

    /**
     * Reinicia a janela de saúde sem excluir o histórico bruto.
     */
    public function resetHealthBaseline(): string
    {
        return $this->healthBaseline->resetNow();
    }

    /**
     * Determina se a lista contem simbolos de tipos de ativo diferentes.
     *
     * @param  array<int, string>  $symbols
     */
    /**
     * Executa a rotina principal do metodo hasMixedAssetTypes.
     */
    private function hasMixedAssetTypes(array $symbols): bool
    {
        $types = [];

        foreach ($symbols as $symbol) {
            $normalized = $this->symbolNormalizer->normalize($symbol);
            $resolvedType = $this->assetTypeResolver->resolve($normalized)->value;
            $types[$resolvedType] = true;
        }

        return count($types) > 1;
    }

    /**
     * Extrai metricas finais do output textual do comando de coleta.
     *
     * @param  array<int, string>  $outputLines
     * @return array{total: int, success: int, failed: int}|null
     */
    /**
     * Executa a rotina principal do metodo extractCommandSummary.
     */
    private function extractCommandSummary(array $outputLines): ?array
    {
        foreach (array_reverse($outputLines) as $line) {
            if (preg_match('/Done\.\s+total=(\d+)\s+success=(\d+)\s+failed=(\d+)/', $line, $matches) === 1) {
                return [
                    'total' => (int) $matches[1],
                    'success' => (int) $matches[2],
                    'failed' => (int) $matches[3],
                ];
            }
        }

        return null;
    }

    /**
     * Determina se a entrada deve ser considerada após o marco de reset.
     *
     * @param  array<string, mixed>  $entry
     */
    private function isEntryVisibleAfterBaseline(array $entry, CarbonImmutable $baselineStartedAt): bool
    {
        $entryFinishedAt = $this->entryTimestamp($entry);

        if ($entryFinishedAt === null) {
            return false;
        }

        return $entryFinishedAt->greaterThanOrEqualTo($baselineStartedAt);
    }

    /**
     * Extrai timestamp da execução para filtragem.
     *
     * @param  array<string, mixed>  $entry
     */
    private function entryTimestamp(array $entry): ?CarbonImmutable
    {
        $rawDate = $entry['finished_at'] ?? $entry['started_at'] ?? null;

        if (! is_string($rawDate) || $rawDate === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($rawDate)->utc();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Constroi DTO de configuracao com campos derivados para a UI.
     *
     * @param  array<int, string>  $symbols
     */
    /**
     * Executa a rotina principal do metodo buildSettings.
     */
    private function buildSettings(
        bool $enabled,
        int $intervalMinutes,
        array $symbols,
        ?string $provider
    ): array {
        return [
            'enabled' => $enabled,
            'interval_minutes' => $intervalMinutes,
            'symbols' => $symbols,
            'provider' => $provider,
            'available_providers' => $this->quotationsConfig->availableProviders(),
            'cron_expression' => "*/{$intervalMinutes} * * * *",
            'requires_scheduler_restart' => true,
            'scheduler_restart_note' => 'If schedule:work is running, restart it after saving schedule settings.',
        ];
    }
}
