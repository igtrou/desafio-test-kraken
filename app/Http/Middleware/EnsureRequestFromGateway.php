<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica se a requisicao foi encaminhada pelo gateway confiavel.
 */
class EnsureRequestFromGateway
{
    /**
     * Valida cabecalho interno compartilhado entre KrakenD e Laravel.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = (string) config('gateway.shared_secret');
        $headerName = (string) config('gateway.shared_secret_header', 'X-Gateway-Secret');
        $providedSecret = (string) $request->header($headerName, '');

        $isVerified = $expectedSecret !== '' && hash_equals($expectedSecret, $providedSecret);
        $request->attributes->set('gateway_request_verified', $isVerified);

        if ((bool) config('gateway.enforce_source', false) && ! $isVerified) {
            throw new AccessDeniedHttpException('Forbidden.');
        }

        return $next($request);
    }
}
