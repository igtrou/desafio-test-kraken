<?php

namespace App\Actions\Dashboard;

use App\Services\Dashboard\DashboardAutoCollectService;
use App\Services\Dashboard\DashboardOperationsAuthorizationService;

/**
 * Reinicia os indicadores de saúde sem apagar o histórico persistido.
 */
class ResetAutoCollectHealthAction
{
    public function __construct(
        private readonly DashboardOperationsAuthorizationService $authorization,
        private readonly DashboardAutoCollectService $autoCollectService,
    ) {}

    /**
     * @return array{message: string, health_reset_at: string}
     */
    public function __invoke(): array
    {
        $this->authorization->ensureLocalOrTesting();

        return [
            'message' => 'Saúde reiniciada. Os indicadores agora consideram apenas execuções após este momento.',
            'health_reset_at' => $this->autoCollectService->resetHealthBaseline(),
        ];
    }
}
