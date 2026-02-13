<?php

namespace App\Domain\Contracts;

interface QuotationReconciliationRepositoryPort
{
    /**
     * @param  array<int, string>  $symbolFilters
     */
    public function countScoped(array $symbolFilters): int;

    /**
     * @param  array<int, string>  $symbolFilters
     * @return array<int, int>
     */
    public function findDuplicateIdsToInvalidate(array $symbolFilters, string $validStatus): array;

    /**
     * @param  array<int, string>  $symbolFilters
     * @param  array<int, int>  $excludedIds
     * @return array<int, array{id: int, asset_id: int, currency: string, price: float, status: string}>
     */
    public function listValidCandidates(array $symbolFilters, string $validStatus, array $excludedIds = []): array;

    /**
     * @param  array<int, int>  $quotationIds
     */
    public function invalidateByIds(
        array $quotationIds,
        string $validStatus,
        string $invalidStatus,
        string $invalidReason
    ): void;
}
