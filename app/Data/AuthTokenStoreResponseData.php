<?php

namespace App\Data;

/**
 * DTO imutavel para respostas de criacao de token com sucesso.
 */
class AuthTokenStoreResponseData
{
    /**
     * Cria o payload padrao para respostas de sucesso na criacao de token.
     */
    public function __construct(
        public readonly string $message,
        public readonly AuthTokenData $data,
        public readonly int $statusCode = 201
    ) {}

    /**
     * @return array{
     *     message: string,
     *     data: array{
     *         token: string,
     *         token_type: string,
     *         device_name: string
     *     }
     * }
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'data' => $this->data->toArray(),
        ];
    }
}
