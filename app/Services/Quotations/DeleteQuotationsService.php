<?php

namespace App\Services\Quotations;

/**
 * Executa exclusao logica de cotacoes com base em filtros.
 */
class DeleteQuotationsService
{
    /**
     * Injeta o servico que monta a query filtrada de cotacoes.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly BuildQuotationQueryService $buildQuotationQuery
    ) {}

    /**
     * Aplica filtros e remove logicamente todas as cotacoes encontradas.
     *
     * @param  array<string, mixed>  $filters
     */
    /**
     * Executa a rotina principal do metodo handle.
     */
    public function handle(array $filters): int
    {
        return $this->buildQuotationQuery->delete($filters);
    }
}
