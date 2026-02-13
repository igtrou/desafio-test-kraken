<?php

namespace App\Data;

/**
 * DTO imutavel com dados minimos para revogacao de token atual.
 */
class AuthTokenRevocationInputData
{
    /**
     * Armazena identificador do usuario e metadados do token atual.
     */
    public function __construct(
        public readonly ?int $userId,
        public readonly ?int $tokenId,
        public readonly ?string $tokenName,
    ) {}
}
