<?php

namespace App\Data;

/**
 * DTO imutavel para respostas de atualizacao de configuracoes de auto-collect.
 */
class AutoCollectSettingsUpdateResponseData
{
    /**
     * Cria um envelope de resposta para atualizacoes de configuracoes de auto-collect.
     */
    public function __construct(
        public readonly string $message,
        public readonly AutoCollectSettingsData $data
    ) {}

    /**
     * @return array{
     *     message: string,
     *     data: array{
     *         enabled: bool,
     *         interval_minutes: int,
     *         symbols: array<int, string>,
     *         symbols_csv: string,
     *         provider: string|null,
     *         available_providers: array<int, string>,
     *         cron_expression: string,
     *         requires_scheduler_restart: bool,
     *         scheduler_restart_note: string
     *     }
     * }
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'data' => $this->data->toArray(),
        ];
    }
}
