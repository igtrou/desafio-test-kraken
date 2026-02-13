<?php

namespace App\Infrastructure\Quotations;

use App\Domain\Contracts\QuotationReconciliationRepositoryPort;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Encapsula consultas de reconciliacao sobre historico de cotacoes.
 */
class QuotationReconciliationRepository implements QuotationReconciliationRepositoryPort
{
    /**
     * Conta cotacoes consideradas pela reconciliacao.
     *
     * @param  array<int, string>  $symbolFilters
     */
    public function countScoped(array $symbolFilters): int
    {
        return $this->scopedQuery($symbolFilters)->count();
    }

    /**
     * Identifica IDs validos duplicados que devem ser invalidados.
     *
     * @param  array<int, string>  $symbolFilters
     * @return array<int, int>
     */
    public function findDuplicateIdsToInvalidate(array $symbolFilters, string $validStatus): array
    {
        $duplicateBuckets = $this->scopedQuery($symbolFilters)
            ->select([
                'asset_id',
                'source',
                'quoted_at',
                'currency',
                'price',
                DB::raw('COUNT(*) as aggregate_count'),
            ])
            ->groupBy(['asset_id', 'source', 'quoted_at', 'currency', 'price'])
            ->having('aggregate_count', '>', 1)
            ->get();

        $duplicateIds = [];

        foreach ($duplicateBuckets as $duplicateBucket) {
            $groupedQuotations = $this->scopedQuery($symbolFilters)
                ->select(['id', 'status'])
                ->where('asset_id', $duplicateBucket->asset_id)
                ->where('source', $duplicateBucket->source)
                ->where('currency', $duplicateBucket->currency)
                ->where('price', $duplicateBucket->price)
                ->where('quoted_at', $duplicateBucket->quoted_at)
                ->orderBy('id')
                ->get();

            if ($groupedQuotations->count() <= 1) {
                continue;
            }

            $quotationIdToKeep = $groupedQuotations
                ->firstWhere('status', $validStatus)?->id
                ?? $groupedQuotations->first()?->id;

            if ($quotationIdToKeep === null) {
                continue;
            }

            foreach ($groupedQuotations as $quotationCandidate) {
                if ($quotationCandidate->id !== $quotationIdToKeep && $quotationCandidate->status === $validStatus) {
                    $duplicateIds[] = (int) $quotationCandidate->id;
                }
            }
        }

        return array_values(array_unique($duplicateIds));
    }

    /**
     * Lista cotacoes validas candidatas para avaliacao de anomalias.
     *
     * @param  array<int, string>  $symbolFilters
     * @param  array<int, int>  $excludedIds
     * @return array<int, array{id: int, asset_id: int, currency: string, price: float, status: string}>
     */
    public function listValidCandidates(array $symbolFilters, string $validStatus, array $excludedIds = []): array
    {
        $query = $this->scopedQuery($symbolFilters)
            ->where('status', $validStatus)
            ->select(['id', 'asset_id', 'currency', 'price', 'status']);

        if ($excludedIds !== []) {
            $query->whereNotIn('id', $excludedIds);
        }

        return $query->get()
            ->map(static fn (Quotation $quotation): array => [
                'id' => (int) $quotation->id,
                'asset_id' => (int) $quotation->asset_id,
                'currency' => $quotation->currency,
                'price' => (float) $quotation->price,
                'status' => $quotation->status,
            ])
            ->all();
    }

    /**
     * Invalida cotacoes por IDs e motivo especifico.
     *
     * @param  array<int, int>  $quotationIds
     */
    public function invalidateByIds(
        array $quotationIds,
        string $validStatus,
        string $invalidStatus,
        string $invalidReason
    ): void {
        if ($quotationIds === []) {
            return;
        }

        $timestamp = now();

        Quotation::query()
            ->whereIn('id', $quotationIds)
            ->where('status', $validStatus)
            ->update([
                'status' => $invalidStatus,
                'invalid_reason' => $invalidReason,
                'invalidated_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
    }

    /**
     * Retorna query base opcionalmente filtrada por simbolos.
     *
     * @param  array<int, string>  $symbolFilters
     */
    private function scopedQuery(array $symbolFilters): Builder
    {
        $query = Quotation::query();

        if ($symbolFilters !== []) {
            $query->whereHas('asset', fn (Builder $assetQuery) => $assetQuery->whereIn('symbol', $symbolFilters));
        }

        return $query;
    }
}
