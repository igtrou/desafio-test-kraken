<?php

namespace App\Data;

/**
 * DTO imutavel de output para renderizacao da pagina de operacoes.
 */
class DashboardOperationsPageData
{
    /**
     * @param  array<string, mixed>  $viewData
     */
    public function __construct(
        public readonly string $viewName,
        public readonly array $viewData = [],
    ) {}
}
