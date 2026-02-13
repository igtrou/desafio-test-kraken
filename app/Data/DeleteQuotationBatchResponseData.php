<?php

namespace App\Data;

/**
 * DTO imutavel para respostas de exclusao em lote de cotacoes.
 */
class DeleteQuotationBatchResponseData
{
    /**
     * Cria um envelope de resposta para operacoes de exclusao em lote de cotacoes.
     */
    public function __construct(
        public readonly string $message,
        public readonly DeleteQuotationBatchData $data,
        public readonly int $statusCode = 200
    ) {}

    /**
     * @return array{
     *     message: string,
     *     data: array{deleted_count: int}
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
