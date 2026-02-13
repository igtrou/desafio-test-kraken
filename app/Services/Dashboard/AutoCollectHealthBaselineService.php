<?php

namespace App\Services\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Mantem o marco temporal usado para reiniciar os indicadores de saúde.
 */
class AutoCollectHealthBaselineService
{
    private const CACHE_KEY = 'dashboard:auto_collect:health_baseline_started_at';

    /**
     * Salva o marco em UTC e retorna o valor persistido.
     */
    public function resetNow(): string
    {
        $startedAt = CarbonImmutable::now('UTC')->toIso8601String();
        Cache::forever(self::CACHE_KEY, $startedAt);

        return $startedAt;
    }

    /**
     * Retorna o marco atual, quando disponível.
     */
    public function current(): ?CarbonImmutable
    {
        $rawValue = Cache::get(self::CACHE_KEY);

        if (! is_string($rawValue) || $rawValue === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($rawValue)->utc();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Limpa marco persistido.
     */
    public function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
