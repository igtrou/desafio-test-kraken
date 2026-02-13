<?php

namespace App\Actions\Dashboard;

use App\Data\AutoCollectRunResponseData;
use App\Data\AutoCollectRunData;
use App\Services\Dashboard\DashboardAutoCollectService;
use App\Services\Dashboard\DashboardOperationsAuthorizationService;

/**
 * Executa a coleta de cotacoes sob demanda a partir do painel operacional.
 */
class RunDashboardAutoCollectAction
{
    /**
     * Injeta servicos de autorizacao e execucao do auto-collect.
     */
    public function __construct(
        private readonly DashboardOperationsAuthorizationService $authorization,
        private readonly DashboardAutoCollectService $autoCollectService,
    ) {}

    /**
     * Autoriza o ambiente e dispara uma execucao manual do comando de coleta.
     *
     * @param  array{
     *     symbols?: array<int, string>,
     *     provider?: string|null,
     *     dry_run?: bool,
     *     force_provider?: bool
     * }  $validated
     */
    public function __invoke(array $validated): AutoCollectRunResponseData
    {
        $this->authorization->ensureLocalOrTesting();

        $runResult = $this->autoCollectService->run($validated);
        $runData = new AutoCollectRunData(
            exitCode: $runResult['exit_code'],
            dryRun: $runResult['dry_run'],
            forceProvider: $runResult['force_provider'],
            allowPartialSuccess: $runResult['allow_partial_success'],
            symbols: $runResult['symbols'],
            requestedProvider: $runResult['requested_provider'],
            effectiveProvider: $runResult['effective_provider'],
            autoFallbackApplied: $runResult['auto_fallback_applied'],
            warnings: $runResult['warnings'],
            summary: $runResult['summary'],
            output: $runResult['output']
        );

        return new AutoCollectRunResponseData(
            message: $runData->exitCode === 0
                ? 'Collection command executed successfully.'
                : 'Collection command finished with failures.',
            data: $runData
        );
    }
}
