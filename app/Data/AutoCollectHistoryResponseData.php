<?php

namespace App\Data;

/**
 * DTO imutavel para respostas de historico de execucao do auto-collect.
 */
class AutoCollectHistoryResponseData
{
    /**
     * Encapsula as linhas de historico de execucao em um envelope de resposta estavel.
     *
     * @param  array<int, array<string, mixed>>  $data
     */
    public function __construct(
        public readonly array $data
    ) {}

    /**
     * @return array{data: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
        ];
    }
}
