<?php

namespace App\Services\Quotations;

use Illuminate\Support\Facades\Cache;

/**
 * Mantem estado volátil de execucao corrente do auto-collect.
 */
class AutoCollectRunStateService
{
    private const CACHE_KEY = 'quotations:auto_collect:current_run';
    private const TTL_MINUTES = 120;

    /**
     * Registra dados da execucao em andamento.
     *
     * @param  array<string, mixed>  $context
     */
    public function markRunning(array $context): void
    {
        Cache::put(self::CACHE_KEY, $context, now()->addMinutes(self::TTL_MINUTES));
    }

    /**
     * Limpa flag de execucao corrente.
     */
    public function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Retorna execucao corrente, se existir.
     *
     * @return array<string, mixed>|null
     */
    public function current(): ?array
    {
        $payload = Cache::get(self::CACHE_KEY);

        return is_array($payload) ? $payload : null;
    }
}
