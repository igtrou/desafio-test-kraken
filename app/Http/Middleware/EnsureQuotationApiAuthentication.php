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

        Auth::shouldUse('sanctum');

        if (! Auth::guard('sanctum')->check()) {
            throw new AuthenticationException('Unauthenticated.', ['sanctum']);
        }

        return $next($request);
    }
}
