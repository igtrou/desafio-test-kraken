<?php

namespace App\Services\Auth;

use App\Domain\Contracts\LoginRateLimiterPort;
use App\Domain\Contracts\WebSessionAuthenticatorPort;
use App\Domain\Contracts\WebSessionStatePort;
use App\Domain\Exceptions\DomainValidationException;

/**
 * Centraliza autenticacao e logout de sessoes web com protecao de throttle.
 */
class WebSessionService
{
    /**
     * Injeta componentes de autenticacao web e controle de tentativas.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly WebSessionAuthenticatorPort $webSessionAuthenticator,
        private readonly LoginRateLimiterPort $loginRateLimiter,
        private readonly WebSessionStatePort $webSessionState,
    ) {}

    /**
     * Tenta autenticar o usuario e aplica limitacao de tentativas quando configurada.
     *
     * @param  string  $email  E-mail informado no login.
     * @param  string  $password  Senha informada no login.
     * @param  bool  $remember  Define se sessao persistente deve ser criada.
     * @param  string  $throttleKey  Chave usada para controle de tentativas.
     *
     * @throws DomainValidationException
     */
    /**
     * Executa a rotina principal do metodo authenticate.
     */
    public function authenticate(
        string $email,
        string $password,
        bool $remember = false,
        string $throttleKey = ''
    ): void {
        if ($throttleKey !== '') {
            $this->ensureIsNotRateLimited($throttleKey);
        }

        if (! $this->webSessionAuthenticator->attempt($email, $password, $remember)) {
            if ($throttleKey !== '') {
                $this->loginRateLimiter->hit($throttleKey);
            }

            throw DomainValidationException::withErrors([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if ($throttleKey !== '') {
            $this->loginRateLimiter->clear($throttleKey);
        }

        $this->webSessionState->regenerate();
    }

    /**
     * Interrompe o login quando o limite de tentativas foi excedido.
     *
     * @throws DomainValidationException
     */
    /**
     * Executa a rotina principal do metodo ensureIsNotRateLimited.
     */
    private function ensureIsNotRateLimited(string $throttleKey): void
    {
        if (! $this->loginRateLimiter->tooManyAttempts($throttleKey, 5)) {
            return;
        }

        $seconds = $this->loginRateLimiter->availableIn($throttleKey);

        throw DomainValidationException::withErrors([
            'email' => [
                sprintf(
                    'Too many login attempts. Please try again in %d seconds.',
                    $seconds
                ),
            ],
        ]);
    }

    /**
     * Finaliza sessao web e renova token CSRF associado.
     */
    /**
     * Executa a rotina principal do metodo logout.
     */
    public function logout(): void
    {
        $this->webSessionAuthenticator->logoutWeb();
        $this->webSessionState->invalidate();
        $this->webSessionState->regenerateToken();
    }
}
