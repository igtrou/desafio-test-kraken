<?php

namespace App\Actions\Auth;

use App\Services\Auth\EmailVerificationService;

/**
 * Confirma o e-mail do usuario autenticado.
 */
class VerifyEmailAddressAction
{
    /**
     * Injeta o servico que valida e confirma verificacoes de e-mail.
     */
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService
    ) {}

    /**
     * Marca o e-mail como verificado quando elegivel.
     *
     * @param  object|null  $user Usuario autenticado corrente.
     */
    public function __invoke(?object $user): bool
    {
        return $this->emailVerificationService->verify($user);
    }
}
