<?php

namespace App\Services\Auth;

use App\Domain\Auth\UserIdentity;
use App\Domain\Contracts\AuthLifecycleEventsPort;

/**
 * Encapsula fluxos de notificacao e confirmacao de verificacao de e-mail.
 */
class EmailVerificationService
{
    /**
     * Injeta dispatcher de eventos do ciclo de autenticacao.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly AuthLifecycleEventsPort $authLifecycleEvents
    ) {}

    /**
     * Envia notificacao de verificacao quando o usuario ainda nao confirmou o e-mail.
     */
    /**
     * Executa a rotina principal do metodo sendNotification.
     */
    public function sendNotification(?object $user): bool
    {
        if (
            $user === null
            || ! method_exists($user, 'hasVerifiedEmail')
            || ! method_exists($user, 'sendEmailVerificationNotification')
            || $user->hasVerifiedEmail()
        ) {
            return false;
        }

        $user->sendEmailVerificationNotification();

        return true;
    }

    /**
     * Marca o e-mail como verificado e publica o evento de verificacao.
     */
    /**
     * Executa a rotina principal do metodo verify.
     */
    public function verify(?object $user): bool
    {
        if (
            $user === null
            || ! method_exists($user, 'hasVerifiedEmail')
            || ! method_exists($user, 'markEmailAsVerified')
            || $user->hasVerifiedEmail()
        ) {
            return false;
        }

        $wasVerified = (bool) $user->markEmailAsVerified();

        if ($wasVerified) {
            $userId = $this->resolveUserId($user);

            if ($userId !== null) {
                $this->authLifecycleEvents->dispatchVerified(new UserIdentity($userId));
            }
        }

        return $wasVerified;
    }

    private function resolveUserId(object $user): ?int
    {
        if (isset($user->id) && is_numeric($user->id)) {
            return (int) $user->id;
        }

        if (method_exists($user, 'getAuthIdentifier')) {
            $identifier = $user->getAuthIdentifier();

            if (is_numeric($identifier)) {
                return (int) $identifier;
            }
        }

        if (method_exists($user, 'getKey')) {
            $key = $user->getKey();

            if (is_numeric($key)) {
                return (int) $key;
            }
        }

        return null;
    }
}
