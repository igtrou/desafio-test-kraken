<?php

namespace App\Actions\Auth;

use App\Data\AuthenticatedUserData;
use App\Services\Auth\AuthenticatedUserProfileInput;
use App\Services\Auth\AuthenticatedUserProfileService;

/**
 * Monta o perfil do usuario autenticado para respostas da API.
 */
class GetAuthenticatedUserProfileAction
{
    /**
     * Injeta o servico que monta o DTO de perfil do usuario autenticado.
     */
    public function __construct(
        private readonly AuthenticatedUserProfileService $profileService,
    ) {}

    /**
     * Gera o perfil serializavel do usuario autenticado atual.
     *
     * @param  object|null  $user Usuario resolvido pelo guard da requisicao.
     */
    public function __invoke(?object $user): AuthenticatedUserData
    {
        $profile = $this->profileService->build(
            AuthenticatedUserProfileInput::fromNullableUser($user)
        );

        return new AuthenticatedUserData(
            id: $profile['id'],
            name: $profile['name'],
            email: $profile['email'],
            isAdmin: $profile['is_admin'],
            permissions: $profile['permissions']
        );
    }
}
