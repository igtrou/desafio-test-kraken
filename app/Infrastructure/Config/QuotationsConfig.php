<?php

namespace App\Infrastructure\Config;

use App\Domain\Contracts\QuotationsConfigPort;

/**
 * Encapsulates quotations-related settings consumed by application services.
 */
class QuotationsConfig implements QuotationsConfigPort
{
    /**
     * Returns cache TTL (seconds) for quote fetch operations.
     */
    /**
     * Executa a rotina principal do metodo cacheTtlSeconds.
     */
    public function cacheTtlSeconds(): int
    {
        return (int) config('quotations.cache_ttl', 60);
    }

    /**
     * Returns whether auto-collect is enabled.
     */
    /**
     * Executa a rotina principal do metodo autoCollectEnabled.
     */
    public function autoCollectEnabled(): bool
    {
        return (bool) config('quotations.auto_collect.enabled', false);
    }

    /**
     * Returns bounded interval used by scheduler and dashboard.
     */
    /**
     * Executa a rotina principal do metodo autoCollectIntervalMinutes.
     */
    public function autoCollectIntervalMinutes(): int
    {
        return max(1, min(59, (int) config('quotations.auto_collect.interval_minutes', 15)));
    }

    /**
     * Returns normalized configured symbols for auto-collect.
     *
     * @return array<int, string>
     */
    /**
     * Executa a rotina principal do metodo autoCollectSymbols.
     */
    public function autoCollectSymbols(): array
    {
        return array_values(array_filter(array_map(
            'strval',
            config('quotations.auto_collect.symbols', [])
        )));
    }

    /**
     * Returns configured preferred provider for auto-collect.
     */
    /**
     * Executa a rotina principal do metodo autoCollectProvider.
     */
    public function autoCollectProvider(): ?string
    {
        $provider = config('quotations.auto_collect.provider');

        return $provider !== null && $provider !== '' ? (string) $provider : null;
    }

    /**
     * Returns configured market-data provider names.
     *
     * @return array<int, string>
     */
    /**
     * Executa a rotina principal do metodo availableProviders.
     */
    public function availableProviders(): array
    {
        return array_keys(config('market-data.providers', []));
    }
}
