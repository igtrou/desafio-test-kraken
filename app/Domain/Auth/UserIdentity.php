<?php

namespace App\Domain\Auth;

/**
 * Identificador imutavel de usuario trafegado entre servicos e portas.
 */
class UserIdentity
{
    /**
     * Cria a referencia de usuario a partir do identificador persistido.
     */
    public function __construct(
        public readonly int $id,
    ) {}
}
