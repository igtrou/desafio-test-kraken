<?php

namespace App\Domain\Contracts;

interface QuotationsConfigPort
{
    public function cacheTtlSeconds(): int;

    public function autoCollectEnabled(): bool;

    public function autoCollectIntervalMinutes(): int;

    /**
     * @return array<int, string>
     */
    public function autoCollectSymbols(): array;

    public function autoCollectProvider(): ?string;

    /**
     * @return array<int, string>
     */
    public function availableProviders(): array;
}
