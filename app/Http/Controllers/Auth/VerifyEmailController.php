<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\VerifyEmailAddressAction;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Confirma verificacao de e-mail do usuario autenticado.
 */
class VerifyEmailController extends Controller
{
    /**
     * Executa a verificacao e redireciona para o dashboard com indicador de sucesso.
     */
    public function __invoke(
        EmailVerificationRequest $request,
        VerifyEmailAddressAction $verifyEmailAddress
    ): RedirectResponse
    {
        if (! $verifyEmailAddress($request->user())) {
            return redirect()->intended(
                config('app.frontend_url').'/dashboard?verified=1'
            );
        }

        return redirect()->intended(
            config('app.frontend_url').'/dashboard?verified=1'
        );
    }
}
