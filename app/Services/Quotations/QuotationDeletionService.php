<?php

namespace App\Services\Quotations;

use App\Domain\Audit\AuditEntityReference;
use App\Domain\Contracts\ApplicationLoggerPort;
use App\Domain\Contracts\AuditLoggerPort;
use App\Domain\Contracts\QuotationDeletionRepositoryPort;
use App\Domain\Exceptions\ForbiddenOperationException;

/**
 * Centraliza exclusao de cotacoes (unitaria e em lote) com auditoria.
 */
class QuotationDeletionService
{
    /**
     * Injeta servicos de exclusao filtrada e registro de auditoria.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly DeleteQuotationsService $deleteQuotations,
        private readonly QuotationDeletionRepositoryPort $quotationDeletionRepository,
        private readonly AuditLoggerPort $auditLogger,
        private readonly ApplicationLoggerPort $applicationLogger,
    ) {}

    /**
     * Remove logicamente uma cotacao especifica quando o usuario tem permissao.
     *
     * @param  array<string, mixed>  $auditContext
     */
    /**
     * Executa a rotina principal do metodo deleteSingle.
     */
    public function deleteSingle(
        int $quotationId,
        bool $canDelete,
        ?int $userId = null,
        array $auditContext = []
    ): DeleteSingleQuotationResult {
        $this->ensureCanDelete($canDelete);

        $this->quotationDeletionRepository->deleteByIdOrFail($quotationId);
        $causerReference = $this->resolveUserAuditReference($userId);

        $this->applicationLogger->info('Quotation soft deleted', [
            'quotation_id' => $quotationId,
            'user_id' => $userId,
            'request_id' => $auditContext['request_id'] ?? null,
            'ip' => $auditContext['ip'] ?? null,
        ]);

        $this->auditLogger->log(
            description: 'Quotation soft deleted',
            subject: AuditEntityReference::quotation($quotationId),
            causer: $causerReference,
            context: $auditContext,
            properties: [
                'quotation_id' => $quotationId,
            ],
            event: 'quotation.deleted',
        );

        return new DeleteSingleQuotationResult(
            message: 'Quotation deleted successfully.',
            quotationId: $quotationId,
            statusCode: 200
        );
    }

    /**
     * Remove cotacoes em lote com base em filtros validados e sinalizador delete-all.
     *
     * @param  array<string, mixed>  $validatedPayload
     * @param  array<string, mixed>  $auditContext
     */
    /**
     * Executa a rotina principal do metodo deleteBatch.
     */
    public function deleteBatch(
        array $validatedPayload,
        bool $canDelete,
        ?int $userId = null,
        array $auditContext = []
    ): DeleteQuotationBatchResult {
        $this->ensureCanDelete($canDelete);

        $filters = array_diff_key($validatedPayload, array_flip(['confirm', 'delete_all']));

        if (
            (bool) ($validatedPayload['delete_all'] ?? false)
            && ! array_key_exists('status', $filters)
            && ! array_key_exists('include_invalid', $filters)
        ) {
            $filters['include_invalid'] = true;
        }

        $deletedCount = $this->deleteQuotations->handle($filters);
        $causerReference = $this->resolveUserAuditReference($userId);

        $this->applicationLogger->info('Quotation batch soft delete executed', [
            'deleted_count' => $deletedCount,
            'filters' => $filters,
            'delete_all' => (bool) ($validatedPayload['delete_all'] ?? false),
            'user_id' => $userId,
            'request_id' => $auditContext['request_id'] ?? null,
            'ip' => $auditContext['ip'] ?? null,
        ]);

        $this->auditLogger->log(
            description: 'Quotation batch soft delete executed',
            causer: $causerReference,
            context: $auditContext,
            properties: [
                'deleted_count' => $deletedCount,
                'filters' => $filters,
                'delete_all' => (bool) ($validatedPayload['delete_all'] ?? false),
            ],
            event: 'quotation.batch_deleted',
        );

        return new DeleteQuotationBatchResult(
            message: $deletedCount > 0
                ? 'Quotations deleted successfully.'
                : 'No quotations matched the provided filters.',
            deletedCount: $deletedCount,
            statusCode: 200
        );
    }

    /**
     * Bloqueia exclusao para perfis sem permissao administrativa.
     *
     * @throws ForbiddenOperationException
     */
    /**
     * Executa a rotina principal do metodo ensureCanDelete.
     */
    private function ensureCanDelete(bool $canDelete): void
    {
        if (! $canDelete) {
            throw new ForbiddenOperationException('Only admin users can delete quotations.');
        }
    }

    private function resolveUserAuditReference(?int $userId): ?AuditEntityReference
    {
        if ($userId === null) {
            return null;
        }

        return AuditEntityReference::user($userId);
    }
}
