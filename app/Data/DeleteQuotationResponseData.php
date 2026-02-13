<?php

namespace App\Data;

/**
 * DTO imutavel para respostas de exclusao de cotacao individual.
 */
class DeleteQuotationResponseData
{
    /**
     * Cria um envelope de resposta para operacoes de exclusao de cotacao individual.
     */
    public function __construct(
        public readonly string $message,
        public readonly DeletedQuotationData $data,
        public readonly int $statusCode = 200
    ) {}

    /**
     * @return array{
     *     message: string,
     *     data: array{id: int}
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
