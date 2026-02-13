<?php

namespace App\Actions\Quotations;

use App\Data\QuotationHistoryItemData;
use App\Data\QuotationHistoryPageData;
use App\Services\Quotations\ListQuotationsService;

/**
 * Lista historico paginado de cotacoes de acordo com filtros informados.
 */
class IndexQuotationHistoryAction
{
    /**
     * Injeta o servico que monta a consulta paginada de cotacoes.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly ListQuotationsService $listQuotations
    ) {}

    /**
     * Retorna a paginacao de historico de cotacoes a partir de filtros validados.
     *
     * @param  array<string, mixed>  $validatedPayload
     */
    /**
     * Executa a rotina principal do metodo __invoke.
     */
    public function __invoke(array $validatedPayload): QuotationHistoryPageData
    {
        $paginatedQuotations = $this->listQuotations->handle($validatedPayload);
        $items = array_map(
            function ($quotation): QuotationHistoryItemData {
                return new QuotationHistoryItemData(
                    id: $quotation->id,
                    symbol: $quotation->symbol,
                    name: $quotation->name,
                    type: $quotation->type,
                    price: $quotation->price,
                    currency: $quotation->currency,
                    source: $quotation->source,
                    status: $quotation->status,
                    invalidReason: $quotation->invalidReason,
                    invalidatedAt: $quotation->invalidatedAt,
                    quotedAt: $quotation->quotedAt,
                    createdAt: $quotation->createdAt,
                );
            },
            $paginatedQuotations->items
        );

        return new QuotationHistoryPageData(
            items: $items,
            currentPage: $paginatedQuotations->currentPage,
            perPage: $paginatedQuotations->perPage,
            total: $paginatedQuotations->total,
        );
    }
}
