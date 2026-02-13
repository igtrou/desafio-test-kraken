<?php

namespace App\Data;

/**
 * DTO imutavel que representa o payload do perfil do usuario autenticado.
 */
class AuthenticatedUserData
{
    /**
     * Cria um payload normalizado do perfil do usuario autenticado.
     *
     * @param  array{delete_quotations: bool}  $permissions
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly bool $isAdmin,
        public readonly array $permissions
    ) {}

    /**
     * @return array{
     *     id: int|null,
     *     name: string|null,
     *     email: string|null,
     *     is_admin: bool,
     *     permissions: array{delete_quotations: bool}
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->isAdmin,
            'permissions' => $this->permissions,
        ];
    }
}
