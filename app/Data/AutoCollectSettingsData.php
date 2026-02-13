<?php

namespace App\Data;

/**
 * DTO imutavel que representa as configuracoes atuais do agendador de auto-collect.
 */
class AutoCollectSettingsData
{
    /**
     * Armazena configuracoes efetivas do agendador e metadados derivados para o dashboard.
     *
     * @param  array<int, string>  $symbols
     * @param  array<int, string>  $availableProviders
     */
    public function __construct(
        public readonly bool $enabled,
        public readonly int $intervalMinutes,
        public readonly array $symbols,
        public readonly ?string $provider,
        public readonly array $availableProviders,
        public readonly string $cronExpression,
        public readonly bool $requiresSchedulerRestart,
        public readonly string $schedulerRestartNote
    ) {}

    /**
     * @return array{
     *     enabled: bool,
     *     interval_minutes: int,
     *     symbols: array<int, string>,
     *     symbols_csv: string,
     *     provider: string|null,
     *     available_providers: array<int, string>,
     *     cron_expression: string,
     *     requires_scheduler_restart: bool,
     *     scheduler_restart_note: string
     * }
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'interval_minutes' => $this->intervalMinutes,
            'symbols' => $this->symbols,
            'symbols_csv' => implode(',', $this->symbols),
            'provider' => $this->provider,
            'available_providers' => $this->availableProviders,
            'cron_expression' => $this->cronExpression,
            'requires_scheduler_restart' => $this->requiresSchedulerRestart,
            'scheduler_restart_note' => $this->schedulerRestartNote,
        ];
    }
}
