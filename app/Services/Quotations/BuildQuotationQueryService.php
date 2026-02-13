<?php

namespace App\Services\Quotations;

use App\Domain\Contracts\QuotationQueryBuilderPort;
use App\Domain\Quotations\QuotationHistoryPage;

/**
 * Monta consultas de cotacoes com regras de filtro reutilizaveis.
 */
class BuildQuotationQueryService
{
    /**
     * Injeta o construtor de consultas baseado em infraestrutura.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly QuotationQueryBuilderPort $quotationQueryBuilder,
    ) {}

    /**
     * Retorna pagina de historico com filtros aplicados.
     *
     * @param  array<string, mixed>  $filters
     */
    /**
     * Executa a rotina principal do metodo paginate.
     */
    public function paginate(array $filters, int $perPage): QuotationHistoryPage
    {
        return $this->quotationQueryBuilder->paginate($filters, $perPage);
    }

    /**
     * Aplica filtros e executa exclusao logica das cotacoes encontradas.
     *
     * @param  array<string, mixed>  $filters
     */
    /**
     * Executa a rotina principal do metodo delete.
     */
    public function delete(array $filters): int
    {
        return $this->quotationQueryBuilder->delete($filters);
    }
}
