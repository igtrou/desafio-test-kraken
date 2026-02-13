<?php

namespace App\Actions\Auth;

use App\Services\Auth\EmailVerificationService;

/**
 * Dispara o envio do e-mail de verificacao para o usuario autenticado.
 */
class SendEmailVerificationNotificationAction
{
    /**
     * Injeta o servico que orquestra notificacoes de verificacao de e-mail.
     */
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService
    ) {}

    /**
     * Solicita envio da notificacao de verificacao e indica se houve envio.
     *
     * @param  object|null  $user Usuario autenticado corrente.
     */
    public function __invoke(?object $user): bool
    {
        return $this->emailVerificationService->sendNotification($user);
    }
}
