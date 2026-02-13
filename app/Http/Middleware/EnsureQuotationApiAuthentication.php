<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce autenticacao Sanctum para endpoints de cotacoes quando habilitada.
 */
class EnsureQuotationApiAuthentication
{
    /**
     * Aplica gate condicional de autenticacao conforme configuracao do sistema.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('quotations.require_auth')) {
            return $next($request);
        }

        if ($this->hasTrustedGatewayJwt($request)) {
            return $next($request);
        }

        Auth::shouldUse('sanctum');

        if (! Auth::guard('sanctum')->check()) {
            throw new AuthenticationException('Unauthenticated.', ['sanctum']);
        }

        return $next($request);
    }

    /**
     * Permite autenticacao delegada ao gateway quando JWT ja foi validado.
     */
    private function hasTrustedGatewayJwt(Request $request): bool
    {
        if (! (bool) config('gateway.trust_jwt_assertion', true)) {
            return false;
        }

        if ($request->attributes->get('gateway_request_verified') !== true) {
            return false;
        }

        $assertionHeader = (string) config('gateway.jwt_assertion_header', 'X-Gateway-Auth');
        $expectedValue = strtolower((string) config('gateway.jwt_assertion_value', 'jwt'));
        $assertedValue = strtolower((string) $request->header($assertionHeader, ''));

        return $assertedValue !== '' && hash_equals($expectedValue, $assertedValue);
    }
}
