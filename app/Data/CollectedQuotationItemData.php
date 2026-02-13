<?php

namespace App\Data;

/**
 * DTO imutavel para uma tentativa de coleta de simbolo.
 */
class CollectedQuotationItemData
{
    /**
     * Armazena o resultado normalizado de uma tentativa de coleta de simbolo.
     */
    public function __construct(
        public readonly string $symbol,
        public readonly string $status,
        public readonly ?string $source = null,
        public readonly ?float $price = null,
        public readonly ?int $quotationId = null,
        public readonly ?string $message = null
    ) {}

    /**
     * Cria um item de sucesso com metadados do provider e da cotacao persistida.
     */
    public static function ok(
        string $symbol,
        string $source,
        float $price,
        ?int $quotationId
    ): self {
        return new self(
            symbol: $symbol,
            status: 'ok',
            source: $source,
            price: $price,
            quotationId: $quotationId,
            message: null
        );
    }

    /**
     * Cria um item de erro preservando o simbolo que falhou e o motivo.
     */
    public static function error(string $symbol, string $message): self
    {
        return new self(
            symbol: $symbol,
            status: 'error',
            source: null,
            price: null,
            quotationId: null,
            message: $message
        );
    }

    /**
     * Indica se o resultado da coleta representa uma busca com sucesso.
     */
    public function isOk(): bool
    {
        return $this->status === 'ok';
    }

    /**
     * Indica se o resultado da coleta representa falha.
     */
    public function isError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * @return array{
     *     symbol: string,
     *     status: 'ok'|'error',
     *     source?: string,
     *     price?: float,
     *     quotation_id?: int|null,
     *     message?: string
     * }
     */
    public function toArray(): array
    {
        $payload = [
            'symbol' => $this->symbol,
            'status' => $this->status,
        ];

        if ($this->isOk()) {
            $payload['source'] = $this->source;
            $payload['price'] = $this->price;
            $payload['quotation_id'] = $this->quotationId;
        }

        if ($this->isError()) {
            $payload['message'] = $this->message;
        }

        return $payload;
    }
}
