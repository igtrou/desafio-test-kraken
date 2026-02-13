<?php

namespace App\Services\Quotations;

use App\Domain\Contracts\QuotationCollectExecutionLoggerPort;

/**
 * Encaminha eventos de inicio/fim da coleta para camada de observabilidade.
 */
class QuotationCollectionExecutionService
{
    /**
     * Injeta logger tecnico das execucoes de coleta.
     */
    public function __construct(
        private readonly QuotationCollectExecutionLoggerPort $executionLogger,
        private readonly AutoCollectRunStateService $runState,
    ) {}

    /**
     * Registra inicio de execucao com contexto operacional.
     *
     * @param  array<string, mixed>  $context
     */
    public function started(array $context): void
    {
        $this->executionLogger->started($context);
        $this->runState->markRunning($context);
    }

    /**
     * Registra finalizacao de execucao com contexto operacional.
     *
     * @param  array<string, mixed>  $context
     */
    public function finished(array $context): void
    {
        $this->executionLogger->finished($context);
        $this->runState->clear();
    }
}
