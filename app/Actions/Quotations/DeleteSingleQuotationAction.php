<?php

namespace App\Actions\Quotations;

use App\Data\DeleteQuotationResponseData;
use App\Data\DeletedQuotationData;
use App\Services\Quotations\QuotationDeletionService;

/**
 * Remove uma cotacao individual com regras de permissao e auditoria.
 */
class DeleteSingleQuotationAction
{
    /**
     * Injeta o servico responsavel pela exclusao de cotacoes.
     */
    public function __construct(
        private readonly QuotationDeletionService $quotationDeletionService
    ) {}

    /**
     * Executa a exclusao de uma cotacao especifica.
     *
     * @param  array<string, mixed>  $auditContext
     */
    public function __invoke(
        int $quotationId,
        bool $canDelete,
        ?int $userId = null,
        array $auditContext = []
    ): DeleteQuotationResponseData
    {
        $response = $this->quotationDeletionService->deleteSingle(
            quotationId: $quotationId,
            canDelete: $canDelete,
            userId: $userId,
            auditContext: $auditContext
        );

        return new DeleteQuotationResponseData(
            message: $response->message,
            data: new DeletedQuotationData($response->quotationId),
            statusCode: $response->statusCode
        );
    }
}
