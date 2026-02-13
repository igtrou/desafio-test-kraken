<?php

namespace App\Actions\Auth;

use App\Data\AuthTokenRevocationInputData;
use App\Data\AuthTokenRevokeResponseData;
use App\Services\Auth\AuthTokenService;

/**
 * Revoga o token de acesso atual e registra a acao em auditoria.
 */
class RevokeAuthTokenAction
{
    /**
     * Injeta o servico de revogacao de token.
     */
    public function __construct(
        private readonly AuthTokenService $authTokenService,
    ) {}

    /**
     * Revoga o token atual da sessao API e retorna mensagem padronizada.
     *
     * @param  array<string, mixed>  $auditContext
     */
    public function __invoke(
        AuthTokenRevocationInputData $input,
        array $auditContext = []
    ): AuthTokenRevokeResponseData {
        $revocation = $this->authTokenService->revokeCurrentToken(
            tokenId: $input->tokenId,
            tokenName: $input->tokenName
        );
        $this->authTokenService->logRevokedToken($input->userId, $revocation, $auditContext);

        return new AuthTokenRevokeResponseData(
            message: 'Token revoked successfully.'
        );
    }
}
