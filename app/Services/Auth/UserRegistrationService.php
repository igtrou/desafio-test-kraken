<?php

namespace App\Services\Auth;

use App\Domain\Contracts\AuthLifecycleEventsPort;
use App\Domain\Contracts\PasswordHasherPort;
use App\Domain\Contracts\UserRepositoryPort;
use App\Domain\Contracts\WebSessionAuthenticatorPort;

/**
 * Registra novos usuarios e inicia sessao web apos cadastro.
 */
class UserRegistrationService
{
    /**
     * Injeta repositorio de usuarios para persistencia de cadastro.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly UserRepositoryPort $userRepository,
        private readonly PasswordHasherPort $passwordHasher,
        private readonly AuthLifecycleEventsPort $authLifecycleEvents,
        private readonly WebSessionAuthenticatorPort $webSessionAuthenticator,
    ) {}

    /**
     * Cria o usuario, dispara evento de registro e autentica no guard web.
     */
    /**
     * Executa a rotina principal do metodo register.
     */
    public function register(string $name, string $email, string $password): void
    {
        $user = $this->userRepository->create($name, $email, $this->passwordHasher->make($password));

        $this->authLifecycleEvents->dispatchRegistered($user->identity());
        $this->webSessionAuthenticator->loginById($user->id);
    }
}
