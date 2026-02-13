<?php

namespace App\Data;

/**
 * DTO imutavel que representa o payload de token bearer emitido.
 */
class AuthTokenData
{
    /**
     * Cria uma representacao serializavel de um token de API emitido.
     */
    public function __construct(
        public readonly string $token,
        public readonly string $tokenType,
        public readonly string $deviceName
    ) {}

    /**
     * @return array{token: string, token_type: string, device_name: string}
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'token_type' => $this->tokenType,
            'device_name' => $this->deviceName,
        ];
    }
}
