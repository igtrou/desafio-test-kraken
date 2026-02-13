<?php

namespace App\Domain\Auth;

/**
 * Snapshot tipado do usuario usado no fluxo de autenticacao.
 */
class UserAccount
{
    /**
     * Monta os campos relevantes para regras de dominio e aplicacao.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly bool $isAdmin,
    ) {}

    /**
     * Retorna somente a identidade quando dados adicionais nao sao necessarios.
     */
    public function identity(): UserIdentity
    {
        return new UserIdentity($this->id);
    }
}
