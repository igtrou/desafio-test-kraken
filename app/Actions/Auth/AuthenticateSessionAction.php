<?php

namespace App\Actions\Auth;

use App\Services\Auth\WebSessionService;

/**
 * Autentica uma sessao web e aplica as rotinas de seguranca de pos-login.
 */
class AuthenticateSessionAction
{
    /**
     * Injeta o servico responsavel por autenticar credenciais de sessao.
     */
    public function __construct(
        private readonly WebSessionService $webSessionService
    ) {}

    /**
     * Executa o login e regenera o ID da sessao para evitar session fixation.
     *
     * @param  string  $email E-mail informado no formulario de login.
     * @param  string  $password Senha informada no formulario de login.
     * @param  bool  $remember Indica se o usuario solicitou sessao persistente.
     * @param  string  $throttleKey Chave usada para limitacao de tentativas.
     */
    public function __invoke(
        string $email,
        string $password,
        bool $remember,
        string $throttleKey
    ): void
    {
        $this->webSessionService->authenticate(
            email: $email,
            password: $password,
            remember: $remember,
            throttleKey: $throttleKey
        );
    }
}
