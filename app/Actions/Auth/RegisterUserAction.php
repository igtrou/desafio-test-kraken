<?php

namespace App\Actions\Auth;

use App\Services\Auth\UserRegistrationService;

/**
 * Registra um novo usuario no fluxo de cadastro web.
 */
class RegisterUserAction
{
    /**
     * Injeta o servico responsavel por criar usuarios.
     */
    public function __construct(
        private readonly UserRegistrationService $userRegistrationService
    ) {}

    /**
     * Executa o cadastro com os dados validados do formulario.
     *
     * @param  array{name: string, email: string, password: string, password_confirmation: string}  $validatedPayload
     */
    public function __invoke(array $validatedPayload): void
    {
        $this->userRegistrationService->register(
            name: $validatedPayload['name'],
            email: $validatedPayload['email'],
            password: $validatedPayload['password']
        );
    }
}
