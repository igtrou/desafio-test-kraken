<?php

namespace App\Data;

/**
 * DTO imutavel para respostas de execucao de auto-collect no dashboard.
 */
class AutoCollectRunResponseData
{
    /**
     * Cria um envelope de resposta para uma execucao disparada pelo dashboard.
     */
    public function __construct(
        public readonly string $message,
        public readonly AutoCollectRunData $data
    ) {}

    /**
     * @return array{
     *     message: string,
     *     data: array{
     *         exit_code: int,
     *         dry_run: bool,
     *         force_provider: bool,
     *         allow_partial_success: bool,
     *         symbols: array<int, string>,
     *         requested_provider: string|null,
     *         effective_provider: string|null,
     *         auto_fallback_applied: bool,
     *         warnings: array<int, string>,
     *         summary: array{total: int, success: int, failed: int}|null,
     *         output: array<int, string>
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
