<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Fornece montagem padronizada de contexto para trilhas de auditoria.
 */
trait BuildsAuditContext
{
    /**
     * Extrai metadados da requisicao atual para anexar em eventos auditados.
     *
     * @return array<string, mixed>
     */
    protected function buildAuditContext(Request $request): array
    {
        return array_filter([
            'request_id' => $request->attributes->get('request_id'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'path' => $request->path(),
            'route' => $request->route()?->getName(),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
