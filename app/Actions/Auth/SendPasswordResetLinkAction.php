<?php

namespace App\Actions\Auth;

use App\Services\Auth\PasswordResetService;

/**
 * Inicia o fluxo de recuperacao de senha enviando o link de reset.
 */
class SendPasswordResetLinkAction
{
    /**
     * Injeta o servico responsavel por enviar links de redefinicao.
     */
    public function __construct(
        private readonly PasswordResetService $passwordResetService
    ) {}

    /**
     * Envia o link de redefinicao para o e-mail informado.
     *
     * @param  array{email: string}  $validatedPayload
     */
    public function __invoke(array $validatedPayload): string
    {
        return $this->passwordResetService->sendResetLink($validatedPayload['email']);
    }
}
