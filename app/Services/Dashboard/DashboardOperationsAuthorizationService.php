<?php

namespace App\Services\Dashboard;

use App\Domain\Contracts\ApplicationEnvironmentPort;
use App\Domain\Exceptions\ForbiddenOperationException;

/**
 * Aplica restricoes de ambiente para operacoes administrativas do dashboard.
 */
class DashboardOperationsAuthorizationService
{
    /**
     * Injeta o contexto da aplicacao para validar ambiente de execucao.
     */
    public function __construct(
        private readonly ApplicationEnvironmentPort $applicationEnvironment
    ) {}

    /**
     * Garante execucao apenas em ambientes locais ou de teste.
     *
     * @throws ForbiddenOperationException
     */
    public function ensureLocalOrTesting(): void
    {
        if (! $this->applicationEnvironment->isLocalOrTesting()) {
            throw new ForbiddenOperationException('Dashboard operations are available only in local/testing environments.');
        }
    }
}
