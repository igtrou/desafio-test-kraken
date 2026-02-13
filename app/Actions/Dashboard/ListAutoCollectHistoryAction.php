<?php

namespace App\Actions\Dashboard;

use App\Data\AutoCollectHistoryResponseData;
use App\Services\Dashboard\DashboardAutoCollectService;
use App\Services\Dashboard\DashboardOperationsAuthorizationService;

/**
 * Lista o historico de execucoes de coleta automatica no painel operacional.
 */
class ListAutoCollectHistoryAction
{
    /**
     * Injeta servicos de autorizacao e consulta de historico do auto-collect.
     */
    public function __construct(
        private readonly DashboardOperationsAuthorizationService $authorization,
        private readonly DashboardAutoCollectService $autoCollectService,
    ) {}

    /**
     * Autoriza o ambiente e retorna as ultimas execucoes registradas.
     *
     * @param  int  $limit Quantidade maxima de registros retornados.
     */
    public function __invoke(int $limit): AutoCollectHistoryResponseData
    {
        $this->authorization->ensureLocalOrTesting();

        return new AutoCollectHistoryResponseData(
            data: $this->autoCollectService->history($limit)
        );
    }
}
