<?php

namespace App\Actions\Auth;

use App\Data\AuthTokenData;
use App\Data\AuthTokenStoreResponseData;
use App\Services\Auth\AuthTokenService;

/**
 * Emite um token Sanctum e registra o evento no log de auditoria.
 */
class IssueAuthTokenAction
{
    /**
     * Injeta o servico de emissao/revogacao de token.
     */
    public function __construct(
        private readonly AuthTokenService $authTokenService,
    ) {}

    /**
     * Valida credenciais, emite token e retorna payload padronizado de sucesso.
     *
     * @param  array{email: string, password: string, device_name?: string|null}  $credentials
     * @param  array<string, mixed>  $auditContext
     */
    public function __invoke(array $credentials, array $auditContext = []): AuthTokenStoreResponseData
    {
        $issueResult = $this->authTokenService->issue(
            email: $credentials['email'],
            password: $credentials['password'],
            deviceName: $credentials['device_name'] ?? null
        );
        $this->authTokenService->logIssuedToken($issueResult, $auditContext);

        return new AuthTokenStoreResponseData(
            message: 'Token created successfully.',
            data: new AuthTokenData(
                token: $issueResult['token'],
                tokenType: 'Bearer',
                deviceName: $issueResult['device_name']
            ),
            statusCode: 201
        );
    }
}
