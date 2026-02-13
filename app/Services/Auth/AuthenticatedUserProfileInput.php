<?php

namespace App\Services\Auth;

/**
 * DTO de entrada para montar o perfil do usuario autenticado.
 */
class AuthenticatedUserProfileInput
{
    /**
     * Carrega atributos minimos do usuario usado no payload de perfil.
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly bool $isAdmin,
    ) {}

    /**
     * Converte usuario autenticado em snapshot tipado para a camada de servico.
     */
    public static function fromNullableUser(?object $user): self
    {
        if ($user === null) {
            return new self(
                id: null,
                name: null,
                email: null,
                isAdmin: false,
            );
        }

        return new self(
            id: isset($user->id) && is_numeric($user->id) ? (int) $user->id : null,
            name: isset($user->name) && is_string($user->name) ? $user->name : null,
            email: isset($user->email) && is_string($user->email) ? $user->email : null,
            isAdmin: isset($user->is_admin) ? (bool) $user->is_admin : false,
        );
    }
}
