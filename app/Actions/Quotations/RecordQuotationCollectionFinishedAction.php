<?php

namespace App\Actions\Quotations;

use App\Services\Quotations\QuotationCollectionExecutionService;

/**
 * Registra o encerramento de uma execucao de coleta de cotacoes.
 */
class RecordQuotationCollectionFinishedAction
{
    /**
     * Injeta o servico que controla o ciclo de vida de execucoes de coleta.
     */
    public function __construct(
        private readonly QuotationCollectionExecutionService $execution,
    ) {}

    /**
     * Marca uma execucao como finalizada usando contexto operacional recebido.
     *
     * @param  array<string, mixed>  $context
     */
    public function __invoke(array $context): void
    {
        $this->execution->finished($context);
    }
}
