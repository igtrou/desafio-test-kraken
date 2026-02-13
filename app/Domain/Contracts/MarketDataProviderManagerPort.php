<?php

namespace App\Domain\Contracts;

interface MarketDataProviderManagerPort
{
    public function provider(?string $providerName = null): MarketDataProvider;

    /**
     * @return array<int, string>
     */
    public function resolveProviderOrder(?string $preferredProvider, string $assetType): array;
}
