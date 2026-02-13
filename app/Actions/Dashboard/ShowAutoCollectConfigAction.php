<?php

namespace App\Actions\Dashboard;

use App\Data\AutoCollectSettingsData;
use App\Services\Dashboard\DashboardAutoCollectService;
use App\Services\Dashboard\DashboardOperationsAuthorizationService;

/**
 * Exibe a configuracao atual de coleta automatica no painel.
 */
class ShowAutoCollectConfigAction
{
    /**
     * Injeta servicos de autorizacao e leitura das configuracoes do auto-collect.
     */
    public function __construct(
        private readonly DashboardOperationsAuthorizationService $authorization,
        private readonly DashboardAutoCollectService $autoCollectService,
    ) {}

    /**
     * Autoriza o ambiente e retorna as configuracoes persistidas do auto-collect.
     */
    public function __invoke(): AutoCollectSettingsData
    {
        $this->authorization->ensureLocalOrTesting();

        $settings = $this->autoCollectService->currentSettings();

        return new AutoCollectSettingsData(
            enabled: $settings['enabled'],
            intervalMinutes: $settings['interval_minutes'],
            symbols: $settings['symbols'],
            provider: $settings['provider'],
            availableProviders: $settings['available_providers'],
            cronExpression: $settings['cron_expression'],
            requiresSchedulerRestart: $settings['requires_scheduler_restart'],
            schedulerRestartNote: $settings['scheduler_restart_note']
        );
    }
}
