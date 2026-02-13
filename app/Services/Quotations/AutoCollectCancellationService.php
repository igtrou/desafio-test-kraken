<?php

namespace App\Services\Quotations;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Permite solicitar e consultar cancelamento de uma execucao de auto-coleta.
 */
class AutoCollectCancellationService
{
    private const CACHE_KEY = 'quotations:auto_collect:cancel_request';

    /**
     * Solicita cancelamento da execucao em andamento (opcionalmente atrelada a run_id).
     */
    public function request(?string $runId = null): void
    {
        Cache::put(self::CACHE_KEY, [
            'run_id' => $runId,
            'requested_at' => Carbon::now('UTC')->timestamp,
        ], now()->addMinutes(10));
    }

    /**
     * Verifica se ha cancelamento pendente para a execucao atual.
     */
    public function isRequested(?string $runId = null): bool
    {
        $payload = Cache::get(self::CACHE_KEY);

        if (! is_array($payload)) {
            return false;
        }

        if (! isset($payload['run_id']) || $payload['run_id'] === null) {
            return true;
        }

        return $runId !== null && $payload['run_id'] === $runId;
    }

    /**
     * Limpa a flag de cancelamento (usado apos conclusao/cancelamento).
     */
    public function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
