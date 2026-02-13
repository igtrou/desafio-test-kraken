<?php

namespace App\Data;

/**
 * DTO imutavel que representa uma execucao de auto-collect disparada pelo dashboard.
 */
class AutoCollectRunData
{
    /**
     * Armazena detalhes normalizados da execucao gerados por um run do dashboard.
     *
     * @param  array<int, string>  $symbols
     * @param  array<int, string>  $warnings
     * @param  array{total: int, success: int, failed: int}|null  $summary
     * @param  array<int, string>  $output
     */
    public function __construct(
        public readonly int $exitCode,
        public readonly bool $dryRun,
        public readonly bool $forceProvider,
        public readonly bool $allowPartialSuccess,
        public readonly array $symbols,
        public readonly ?string $requestedProvider,
        public readonly ?string $effectiveProvider,
        public readonly bool $autoFallbackApplied,
        public readonly array $warnings,
        public readonly ?array $summary,
        public readonly array $output
    ) {}

    /**
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
    public function toArray(): array
    {
        return [
            'exit_code' => $this->exitCode,
            'dry_run' => $this->dryRun,
            'force_provider' => $this->forceProvider,
            'allow_partial_success' => $this->allowPartialSuccess,
            'symbols' => $this->symbols,
            'requested_provider' => $this->requestedProvider,
            'effective_provider' => $this->effectiveProvider,
            'auto_fallback_applied' => $this->autoFallbackApplied,
            'warnings' => $this->warnings,
            'summary' => $this->summary,
            'output' => $this->output,
        ];
    }
}
