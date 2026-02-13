<?php

namespace App\Infrastructure\Config;

use App\Domain\Contracts\ApplicationEnvironmentPort;
use Illuminate\Contracts\Foundation\Application;

/**
 * Expoe verificacoes de ambiente da aplicacao sem vazar helper global para Services.
 */
class ApplicationEnvironment implements ApplicationEnvironmentPort
{
    /**
     * Injeta a instancia da aplicacao para consultar ambiente atual.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly Application $application,
    ) {}

    /**
     * Indica se a aplicacao esta executando em ambiente de testes.
     */
    /**
     * Executa a rotina principal do metodo isTesting.
     */
    public function isTesting(): bool
    {
        return $this->application->environment('testing');
    }

    /**
     * Indica se a aplicacao esta executando em ambiente local ou de testes.
     */
    public function isLocalOrTesting(): bool
    {
        return $this->application->environment(['local', 'testing']);
    }
}
