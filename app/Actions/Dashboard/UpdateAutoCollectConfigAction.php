<?php

namespace App\Actions\Dashboard;

use App\Data\AutoCollectSettingsUpdateResponseData;
use App\Data\AutoCollectSettingsData;
use App\Services\Dashboard\DashboardAutoCollectService;
use App\Services\Dashboard\DashboardOperationsAuthorizationService;

/**
 * Atualiza as configuracoes de coleta automatica via painel operacional.
 */
class UpdateAutoCollectConfigAction
{
    /**
     * Injeta servicos de autorizacao e persistencia de configuracao do auto-collect.
     */
    public function __construct(
        private readonly DashboardOperationsAuthorizationService $authorization,
        private readonly DashboardAutoCollectService $autoCollectService,
    ) {}

    /**
     * Autoriza o ambiente e persiste novas configuracoes de coleta automatica.
     *
     * @param  array{
     *     enabled?: bool,
     *     interval_minutes?: int,
     *     symbols?: array<int, string>,
     *     provider?: string|null
     * }  $validated
     */
    public function __invoke(array $validated): AutoCollectSettingsUpdateResponseData
    {
        $this->authorization->ensureLocalOrTesting();

        $settings = $this->autoCollectService->updateSettings($validated);
        $settingsData = new AutoCollectSettingsData(
            enabled: $settings['enabled'],
            intervalMinutes: $settings['interval_minutes'],
            symbols: $settings['symbols'],
            provider: $settings['provider'],
            availableProviders: $settings['available_providers'],
            cronExpression: $settings['cron_expression'],
            requiresSchedulerRestart: $settings['requires_scheduler_restart'],
            schedulerRestartNote: $settings['scheduler_restart_note']
        );

        return new AutoCollectSettingsUpdateResponseData(
            message: 'Auto-collect settings saved successfully.',
            data: $settingsData
        );
    }
}
