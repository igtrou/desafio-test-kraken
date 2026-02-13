<?php

namespace App\Infrastructure\Quotations;

use App\Domain\Contracts\QuotationQueryBuilderPort;
use App\Domain\Quotations\QuotationHistoryPage;
use App\Domain\Quotations\QuotationHistoryRecord;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monta consultas de cotacoes com regras de filtro reutilizaveis.
 */
class QuotationQueryBuilder implements QuotationQueryBuilderPort
{
    /**
     * Retorna pagina de historico com filtros aplicados e payload serializavel.
     *
     * @param  array<string, mixed>  $filters
     */
    /**
     * Executa a rotina principal do metodo paginate.
     */
    public function paginate(array $filters, int $perPage): QuotationHistoryPage
    {
        $paginator = $this->build($filters, withAsset: true)
            ->latest('quoted_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $items = $paginator->getCollection()
            ->map(fn (Quotation $quotation): QuotationHistoryRecord => $this->toRecord($quotation))
            ->values()
            ->all();

        return new QuotationHistoryPage(
            items: $items,
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
        );
    }

    /**
     * Aplica filtros e executa exclusao logica dos registros encontrados.
     *
     * @param  array<string, mixed>  $filters
     */
    /**
     * Executa a rotina principal do metodo delete.
     */
    public function delete(array $filters): int
    {
        return $this->build($filters)->delete();
    }

    /**
     * Aplica filtros comuns para listagem, exclusao e reconciliacao de cotacoes.
     *
     * @param  array<string, mixed>  $filters
     */
    /**
     * Executa a rotina principal do metodo build.
     */
    private function build(array $filters, bool $withAsset = false): Builder
    {
        $query = Quotation::query();

        if ($withAsset) {
            $query->with('asset');
        }

        $requestedStatus = $filters['status'] ?? null;
        $includeInvalid = (bool) ($filters['include_invalid'] ?? false);

        if ($requestedStatus !== null) {
            $query->where('status', $requestedStatus);
        } elseif (! $includeInvalid) {
            $query->valid();
        }

        if (isset($filters['symbol'])) {
            $symbol = $filters['symbol'];
            $query->whereHas('asset', fn (Builder $assetQuery) => $assetQuery->where('symbol', $symbol));
        }

        if (isset($filters['type'])) {
            $type = $filters['type'];
            $query->whereHas('asset', fn (Builder $assetQuery) => $assetQuery->where('type', $type));
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('quoted_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('quoted_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Normaliza o model para payload de transporte entre camadas.
     */
    /**
     * Executa a rotina principal do metodo toRecord.
     */
    private function toRecord(Quotation $quotation): QuotationHistoryRecord
    {
        $quotation->loadMissing('asset');
        $asset = $quotation->asset;
        $assetType = $asset?->type;

        return new QuotationHistoryRecord(
            id: (int) $quotation->id,
            symbol: (string) $asset?->symbol,
            name: (string) $asset?->name,
            type: $assetType?->value ?? (string) $assetType,
            price: (float) $quotation->price,
            currency: $quotation->currency,
            source: $quotation->source,
            status: $quotation->status,
            invalidReason: $quotation->invalid_reason,
            invalidatedAt: $quotation->invalidated_at,
            quotedAt: $quotation->quoted_at,
            createdAt: $quotation->created_at,
        );
    }
}
