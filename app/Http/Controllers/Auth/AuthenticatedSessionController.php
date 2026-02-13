<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateSessionAction;
use App\Actions\Auth\LogoutSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Response;

/**
 * Gerencia autenticacao e encerramento de sessao web.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Processa login de sessao usando credenciais validadas.
     */
    public function store(
        LoginRequest $request,
        AuthenticateSessionAction $authenticateSession
    ): Response
    {
        $validated = $request->validated();

        $authenticateSession(
            email: $validated['email'],
            password: $validated['password'],
            remember: $request->boolean('remember'),
            throttleKey: $request->throttleKey()
        );

        return response()->noContent();
    }

    /**
     * Finaliza a sessao autenticada corrente.
     */
    public function destroy(
        LogoutSessionAction $logoutSession
    ): Response
    {
        $logoutSession();

        return response()->noContent();
    }
}
