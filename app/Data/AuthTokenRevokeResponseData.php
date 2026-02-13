<?php

namespace App\Data;

/**
 * DTO imutavel para respostas de revogacao de token com sucesso.
 */
class AuthTokenRevokeResponseData
{
    /**
     * Cria o payload padrao para respostas de sucesso na revogacao de token.
     */
    public function __construct(
        public readonly string $message,
        public readonly int $statusCode = 200
    ) {}

    /**
     * @return array{message: string}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
        ];
    }
}
