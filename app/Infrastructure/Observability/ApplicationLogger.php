<?php

namespace App\Infrastructure\Observability;

use App\Domain\Contracts\ApplicationLoggerPort;
use Illuminate\Support\Facades\Log;

/**
 * Centralizes generic application log writes used by service layer.
 */
class ApplicationLogger implements ApplicationLoggerPort
{
    /**
     * Writes an informational message with structured context.
     *
     * @param  array<string, mixed>  $context
     */
    /**
     * Executa a rotina principal do metodo info.
     */
    public function info(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * Writes a warning message with structured context.
     *
     * @param  array<string, mixed>  $context
     */
    /**
     * Executa a rotina principal do metodo warning.
     */
    public function warning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }
}
