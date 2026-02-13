<?php

namespace App\Infrastructure\Quotations;

use App\Domain\Contracts\QuotationPersistencePort;
use App\Domain\MarketData\AssetType;
use App\Domain\MarketData\Quote;
use App\Domain\Quotations\QuotationQualityService;
use App\Models\Asset;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

/**
 * Persiste cotacoes e ativos encapsulando detalhes de Eloquent e transacoes.
 */
class QuotationPersistenceGateway implements QuotationPersistencePort
{
    /**
     * Injeta servico de qualidade para classificar cotacoes antes da gravacao.
     */
    public function __construct(
        private readonly QuotationQualityService $quotationQuality,
    ) {}

    /**
     * Persiste cotacao de forma atomica e retorna payload normalizado.
     *
     * @return array{
     *     id: int,
     *     symbol: string,
     *     name: string,
     *     type: string,
     *     price: float,
     *     currency: string,
     *     source: string,
     *     status: string,
     *     invalid_reason: string|null,
     *     invalidated_at: \DateTimeInterface|null,
     *     quoted_at: \DateTimeInterface|null,
     *     created_at: \DateTimeInterface|null,
     *     was_recently_created: bool
     * }
     */
    public function persist(Quote $quote, ?string $typeOverride = null): array
    {
        return DB::transaction(function () use ($quote, $typeOverride) {
            $resolvedAssetType = $typeOverride ?? ($quote->type instanceof AssetType ? $quote->type->value : $quote->type);
            $resolvedAssetType ??= AssetType::Stock->value;

            $asset = Asset::query()->firstOrCreate(
                ['symbol' => $quote->symbol],
                [
                    'name' => $quote->name,
                    'type' => $resolvedAssetType,
                ],
            );

            // Atualiza metadados quando o provider retorna nome/tipo mais recentes.
            $asset->fill([
                'name' => $quote->name,
                'type' => $resolvedAssetType,
            ]);

            if ($asset->isDirty()) {
                $asset->save();
            }

            // Serializa persistencia por ativo para reduzir duplicidades por corrida.
            Asset::query()
                ->whereKey($asset->id)
                ->lockForUpdate()
                ->first();

            $existingDuplicateQuotation = Quotation::query()
                ->where('asset_id', $asset->id)
                ->where('source', $quote->source)
                ->where('quoted_at', $quote->quotedAt)
                ->where('price', number_format((float) $quote->price, 6, '.', ''))
                ->where('currency', $quote->currency)
                ->first();

            if ($existingDuplicateQuotation !== null) {
                return $this->toPayload(
                    $existingDuplicateQuotation->loadMissing('asset'),
                    wasRecentlyCreated: false
                );
            }

            $referencePrices = Quotation::query()
                ->valid()
                ->where('asset_id', $asset->id)
                ->where('currency', $quote->currency)
                ->latest('quoted_at')
                ->latest('id')
                ->limit($this->quotationQuality->windowSize())
                ->pluck('price')
                ->all();

            $classificationTimestamp = now();
            $sameTimestampQuotationExists = Quotation::query()
                ->where('asset_id', $asset->id)
                ->where('source', $quote->source)
                ->where('quoted_at', $quote->quotedAt)
                ->exists();

            $qualityClassification = $this->quotationQuality->classifyForPersistence(
                price: (float) $quote->price,
                referencePrices: $referencePrices,
                sameTimestampQuotationExists: $sameTimestampQuotationExists,
                classifiedAt: $classificationTimestamp
            );

            $persistedQuotation = $asset->quotations()->create([
                'price' => $quote->price,
                'currency' => $quote->currency,
                'source' => $quote->source,
                'status' => $qualityClassification->status,
                'invalid_reason' => $qualityClassification->invalidReason,
                'invalidated_at' => $qualityClassification->invalidatedAt,
                'quoted_at' => $quote->quotedAt,
            ]);

            $this->reconcileRecentQualityWindow($asset->id, $quote->currency);

            return $this->toPayload(
                $persistedQuotation->refresh()->loadMissing('asset'),
                wasRecentlyCreated: true
            );
        });
    }

    /**
     * Reavalia a janela recente de cotacoes validas para detectar inconsistencias.
     */
    private function reconcileRecentQualityWindow(int $assetId, string $currency): void
    {
        $recentValidQuotations = Quotation::query()
            ->valid()
            ->where('asset_id', $assetId)
            ->where('currency', $currency)
            ->latest('quoted_at')
            ->latest('id')
            ->limit($this->quotationQuality->windowSize())
            ->lockForUpdate()
            ->get(['id', 'price']);

        if ($recentValidQuotations->count() < $this->quotationQuality->minReferencePoints()) {
            return;
        }

        $referencePrices = $recentValidQuotations->pluck('price')->all();
        $outlierIdsToInvalidate = [];
        $nonPositiveIdsToInvalidate = [];

        foreach ($recentValidQuotations as $recentQuotation) {
            $qualityClassification = $this->quotationQuality->classifyPrice(
                (float) $recentQuotation->price,
                $referencePrices
            );

            if (! $qualityClassification->isInvalid()) {
                continue;
            }

            if ($qualityClassification->isOutlier()) {
                $outlierIdsToInvalidate[] = $recentQuotation->id;

                continue;
            }

            if ($qualityClassification->isNonPositive()) {
                $nonPositiveIdsToInvalidate[] = $recentQuotation->id;
            }
        }

        $this->invalidateByReason($outlierIdsToInvalidate, Quotation::INVALID_REASON_OUTLIER);
        $this->invalidateByReason($nonPositiveIdsToInvalidate, Quotation::INVALID_REASON_NON_POSITIVE);
    }

    /**
     * Invalida cotacoes por motivo especifico mantendo idempotencia de status.
     *
     * @param  array<int, int>  $quotationIds
     */
    private function invalidateByReason(array $quotationIds, string $invalidReason): void
    {
        if ($quotationIds === []) {
            return;
        }

        $timestamp = now();

        Quotation::query()
            ->whereIn('id', array_values(array_unique($quotationIds)))
            ->where('status', Quotation::STATUS_VALID)
            ->update([
                'status' => Quotation::STATUS_INVALID,
                'invalid_reason' => $invalidReason,
                'invalidated_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
    }

    /**
     * Converte model persistido em payload transportavel para camadas superiores.
     *
     * @return array{
     *     id: int,
     *     symbol: string,
     *     name: string,
     *     type: string,
     *     price: float,
     *     currency: string,
     *     source: string,
     *     status: string,
     *     invalid_reason: string|null,
     *     invalidated_at: \DateTimeInterface|null,
     *     quoted_at: \DateTimeInterface|null,
     *     created_at: \DateTimeInterface|null,
     *     was_recently_created: bool
     * }
     */
    private function toPayload(Quotation $quotation, bool $wasRecentlyCreated): array
    {
        $quotation->loadMissing('asset');
        $asset = $quotation->asset;
        $assetType = $asset?->type;

        return [
            'id' => $quotation->id,
            'symbol' => (string) $asset?->symbol,
            'name' => (string) $asset?->name,
            'type' => $assetType?->value ?? (string) $assetType,
            'price' => (float) $quotation->price,
            'currency' => $quotation->currency,
            'source' => $quotation->source,
            'status' => $quotation->status,
            'invalid_reason' => $quotation->invalid_reason,
            'invalidated_at' => $quotation->invalidated_at,
            'quoted_at' => $quotation->quoted_at,
            'created_at' => $quotation->created_at,
            'was_recently_created' => $wasRecentlyCreated,
        ];
    }
}
