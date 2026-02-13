<?php

namespace App\Services\Auth;

/**
 * Construi o payload padrao de perfil do usuario autenticado.
 */
class AuthenticatedUserProfileService
{
    /**
     * Mapeia dados basicos e permissoes derivadas para DTO de resposta.
     *
     * @return array{
     *     id: int|null,
     *     name: string|null,
     *     email: string|null,
     *     is_admin: bool,
     *     permissions: array{delete_quotations: bool}
     * }
     */
    public function build(AuthenticatedUserProfileInput $user): array
    {
        $isAdmin = $user->isAdmin;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $isAdmin,
            'permissions' => [
                'delete_quotations' => $isAdmin,
            ],
        ];
    }
}
