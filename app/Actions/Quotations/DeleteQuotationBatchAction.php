<?php

namespace App\Actions\Quotations;

use App\Data\DeleteQuotationBatchData;
use App\Data\DeleteQuotationBatchResponseData;
use App\Services\Quotations\QuotationDeletionService;

/**
 * Remove cotacoes em lote com regras de permissao e auditoria.
 */
class DeleteQuotationBatchAction
{
    /**
     * Injeta o servico responsavel pela exclusao de cotacoes.
     */
    public function __construct(
        private readonly QuotationDeletionService $quotationDeletionService
    ) {}

    /**
     * Executa exclusao em lote com contexto de autorizacao e trilha de auditoria.
     *
     * @param  array<string, mixed>  $validatedPayload
     * @param  array<string, mixed>  $auditContext
     */
    public function __invoke(
        array $validatedPayload,
        bool $canDelete,
        ?int $userId = null,
        array $auditContext = []
    ): DeleteQuotationBatchResponseData
    {
        $response = $this->quotationDeletionService->deleteBatch(
            validatedPayload: $validatedPayload,
            canDelete: $canDelete,
            userId: $userId,
            auditContext: $auditContext
        );

        return new DeleteQuotationBatchResponseData(
            message: $response->message,
            data: new DeleteQuotationBatchData($response->deletedCount),
            statusCode: $response->statusCode
        );
    }
}
