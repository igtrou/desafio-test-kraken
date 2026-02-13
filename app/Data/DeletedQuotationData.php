<?php

namespace App\Data;

/**
 * DTO imutavel que representa o identificador de uma cotacao removida.
 */
class DeletedQuotationData
{
    /**
     * Armazena o identificador da cotacao removida pela operacao.
     */
    public function __construct(
        public readonly int $id
    ) {}

    /**
     * @return array{id: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
