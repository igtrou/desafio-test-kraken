<?php

namespace App\Data;

/**
 * DTO interno que carrega metadados do token capturados durante a revogacao.
 */
class AuthTokenRevocationData
{
    /**
     * Armazena identificadores do token capturados antes da conclusao da revogacao.
     */
    public function __construct(
        public readonly ?int $tokenId,
        public readonly ?string $tokenName
    ) {}
}
