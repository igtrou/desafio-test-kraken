<?php

namespace App\Actions\Auth;

use App\Services\Auth\PasswordResetService;

/**
 * Aplica o fluxo de redefinicao de senha via token.
 */
class ResetPasswordAction
{
    /**
     * Injeta o servico que valida token e troca senha.
     */
    public function __construct(
        private readonly PasswordResetService $passwordResetService
    ) {}

    /**
     * Redefine a senha usando os dados validados da requisicao.
     *
     * @param  array{
     *     token: string,
     *     email: string,
     *     password: string
     * }  $validatedPayload
     */
    public function __invoke(array $validatedPayload): string
    {
        return $this->passwordResetService->reset($validatedPayload);
    }
}
