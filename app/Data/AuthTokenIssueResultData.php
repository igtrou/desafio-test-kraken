<?php

namespace App\Data;

/**
 * DTO interno que carrega metadados do token emitido.
 */
class AuthTokenIssueResultData
{
    /**
     * Armazena metadados internos gerados durante a emissao do token.
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $token,
        public readonly int $tokenId,
        public readonly string $deviceName
    ) {}

    /**
     * Converte os dados internos de emissao no DTO publico de token.
     */
    public function toTokenData(): AuthTokenData
    {
        return new AuthTokenData(
            token: $this->token,
            tokenType: 'Bearer',
            deviceName: $this->deviceName
        );
    }
}
