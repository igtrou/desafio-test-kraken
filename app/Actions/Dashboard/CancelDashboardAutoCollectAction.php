<?php

namespace App\Actions\Dashboard;

use App\Services\Dashboard\DashboardOperationsAuthorizationService;
use App\Services\Quotations\AutoCollectCancellationService;

/**
 * Permite cancelar a execucao corrente do auto-collect via painel.
 */
class CancelDashboardAutoCollectAction
{
    public function __construct(
        private readonly DashboardOperationsAuthorizationService $authorization,
        private readonly AutoCollectCancellationService $cancellation,
        private readonly \App\Services\Quotations\AutoCollectRunStateService $runState,
    ) {}

    public function __invoke(?string $runId = null): array
    {
        $this->authorization->ensureLocalOrTesting();

        $this->cancellation->request($runId);
        $current = $this->runState->current();

        return [
            'message' => 'Cancelamento solicitado. A execução atual será interrompida na próxima verificação.',
            'current_run' => $current,
        ];
    }
}
