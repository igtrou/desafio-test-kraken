<?php

namespace App\Domain\Auth;

/**
 * Resultado tipado da emissao de token pessoal.
 */
class IssuedAuthToken
{
    /**
     * Armazena o token em texto puro e seu identificador persistido.
     */
    public function __construct(
        public readonly string $token,
        public readonly int $tokenId,
    ) {}
}
