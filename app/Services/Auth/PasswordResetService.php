<?php

namespace App\Services\Auth;

use App\Domain\Auth\UserIdentity;
use App\Domain\Contracts\AuthLifecycleEventsPort;
use App\Domain\Contracts\PasswordHasherPort;
use App\Domain\Contracts\PasswordResetBrokerPort;
use App\Domain\Contracts\RememberTokenGeneratorPort;
use App\Domain\Exceptions\DomainValidationException;

/**
 * Implementa o fluxo de recuperacao e redefinicao de senha.
 */
class PasswordResetService
{
    /**
     * Injeta componentes de broker, hashing, token e eventos.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly PasswordResetBrokerPort $passwordResetBroker,
        private readonly PasswordHasherPort $passwordHasher,
        private readonly RememberTokenGeneratorPort $rememberTokenGenerator,
        private readonly AuthLifecycleEventsPort $authLifecycleEvents,
    ) {}

    /**
     * Solicita envio do link de redefinicao para o e-mail informado.
     *
     * @param  string  $email  E-mail alvo do fluxo de reset.
     *
     * @throws DomainValidationException
     */
    /**
     * Executa a rotina principal do metodo sendResetLink.
     */
    public function sendResetLink(string $email): string
    {
        $status = $this->passwordResetBroker->sendResetLink($email);

        if ($status !== $this->passwordResetBroker->resetLinkSentStatus()) {
            throw DomainValidationException::withErrors([
                'email' => [$status],
            ]);
        }

        return $status;
    }

    /**
     * Valida token de reset e aplica nova senha para o usuario correspondente.
     *
     * @param  array{
     *     token: string,
     *     email: string,
     *     password: string
     * }  $validatedPayload
     *
     * @throws DomainValidationException
     */
    /**
     * Executa a rotina principal do metodo reset.
     */
    public function reset(array $validatedPayload): string
    {
        $status = $this->passwordResetBroker->reset(
            [
                'token' => $validatedPayload['token'],
                'email' => $validatedPayload['email'],
                'password' => $validatedPayload['password'],
                'password_confirmation' => $validatedPayload['password'],
            ],
            function (object $user) use ($validatedPayload): void {
                if (! method_exists($user, 'forceFill') || ! method_exists($user, 'save')) {
                    return;
                }

                $user->forceFill([
                    'password' => $this->passwordHasher->make($validatedPayload['password']),
                    'remember_token' => $this->rememberTokenGenerator->generate(),
                ])->save();

                $userId = $this->resolveUserId($user);

                if ($userId !== null) {
                    $this->authLifecycleEvents->dispatchPasswordReset(new UserIdentity($userId));
                }
            }
        );

        if ($status !== $this->passwordResetBroker->passwordResetStatus()) {
            throw DomainValidationException::withErrors([
                'email' => [$status],
            ]);
        }

        return $status;
    }

    private function resolveUserId(object $user): ?int
    {
        if (isset($user->id) && is_numeric($user->id)) {
            return (int) $user->id;
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
