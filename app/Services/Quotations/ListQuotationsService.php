<?php

namespace App\Services\Quotations;

use App\Domain\Quotations\QuotationHistoryPage;

/**
 * Aplica filtros e paginacao sobre o historico persistido de cotacoes.
 */
class ListQuotationsService
{
    /**
     * Injeta o servico que constroi a query base de cotacoes.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly BuildQuotationQueryService $buildQuotationQuery
    ) {}

    /**
     * Retorna pagina de historico com ordenacao padrao por data e id decrescentes.
     *
     * @param  array<string, mixed>  $filters
     */
    /**
     * Executa a rotina principal do metodo handle.
     */
    public function handle(array $filters): QuotationHistoryPage
    {
        $perPage = (int) ($filters['per_page'] ?? 20);

        return $this->buildQuotationQuery->paginate($filters, $perPage);
    }
}
