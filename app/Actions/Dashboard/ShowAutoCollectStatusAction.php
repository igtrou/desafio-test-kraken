<?php

namespace App\Actions\Dashboard;

use App\Services\Dashboard\DashboardOperationsAuthorizationService;
use App\Services\Quotations\AutoCollectRunStateService;

/**
 * Retorna estado atual da execucao de auto-coleta (se estiver em andamento).
 */
class ShowAutoCollectStatusAction
{
    public function __construct(
        private readonly DashboardOperationsAuthorizationService $authorization,
        private readonly AutoCollectRunStateService $runState,
    ) {}

    /**
     * @return array{running: bool, data: array<string, mixed>|null}
     */
    public function __invoke(): array
    {
        $this->authorization->ensureLocalOrTesting();

        $current = $this->runState->current();

        return [
            'running' => $current !== null,
            'data' => $current,
        ];
    }
}
