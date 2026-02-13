<?php

namespace App\Infrastructure\Audit;

use App\Domain\Audit\AuditEntityReference;
use App\Domain\Contracts\AuditLoggerPort;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Centraliza o registro de eventos de auditoria com fallback seguro de erro.
 */
class AuditLogger implements AuditLoggerPort
{
    /**
     * Registra um evento de auditoria com fallback seguro em caso de falha.
     *
     * @param  string  $description Descricao do evento auditado.
     * @param  AuditEntityReference|null  $subject Entidade alvo da operacao.
     * @param  AuditEntityReference|null  $causer Entidade responsavel pela acao.
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $properties
     * @param  string  $event Nome tecnico do evento para indexacao.
     * @param  string  $logName Nome do canal de auditoria.
     */
    public function log(
        string $description,
        ?AuditEntityReference $subject = null,
        ?AuditEntityReference $causer = null,
        array $context = [],
        array $properties = [],
        string $event = 'custom',
        string $logName = 'audit'
    ): void {
        $payload = $this->mergeProperties(
            $context,
            array_replace_recursive($properties, $this->referencePayload($subject, $causer))
        );
        $subjectModel = $this->resolveModel($subject);
        $causerModel = $this->resolveModel($causer);

        $logger = activity($logName)
            ->event($event)
            ->withProperties($payload);

        if ($causerModel instanceof Model) {
            $logger->causedBy($causerModel);
        }

        if ($subjectModel instanceof Model) {
            $logger->performedOn($subjectModel);
        }

        try {
            $logger->log($description);
        } catch (Throwable $exception) {
            report($exception);

            Log::warning('Audit log write skipped due to persistence error.', [
                'event' => $event,
                'log_name' => $logName,
                'description' => $description,
                'request_id' => $context['request_id'] ?? null,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Mescla contexto e propriedades extras removendo valores vazios.
     *
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function mergeProperties(array $base, array $extra): array
    {
        return $this->removeEmptyValues(array_replace_recursive($base, $extra));
    }

    /**
     * Elimina chaves com valores nulos ou strings vazias antes de persistir.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function removeEmptyValues(array $values): array
    {
        return array_filter(
            $values,
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );
    }

    /**
     * Resolve a referencia de auditoria para model Eloquent compatível com o logger.
     */
    private function resolveModel(?AuditEntityReference $reference): ?Model
    {
        if ($reference === null) {
            return null;
        }

        return match ($reference->type) {
            AuditEntityReference::TYPE_USER => User::query()->find($reference->id),
            AuditEntityReference::TYPE_QUOTATION => Quotation::query()->withTrashed()->find($reference->id),
            default => null,
        };
    }

    /**
     * Converte referencias de auditoria em metadados padronizados de payload.
     *
     * @return array<string, mixed>
     */
    private function referencePayload(?AuditEntityReference $subject, ?AuditEntityReference $causer): array
    {
        return [
            'subject' => $subject !== null
                ? ['type' => $subject->type, 'id' => $subject->id]
                : null,
            'causer' => $causer !== null
                ? ['type' => $causer->type, 'id' => $causer->id]
                : null,
        ];
    }
}
