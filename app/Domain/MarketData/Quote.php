<?php

namespace App\Domain\MarketData;

use DateTimeImmutable;

/**
 * Representa uma cotacao de mercado imutavel retornada pelos providers.
 */
class Quote
{
    /**
     * Monta a cotacao de mercado com os campos normalizados do provider.
     */
    public function __construct(
        public readonly string $symbol,
        public readonly string $name,
        public readonly string $type,
        public readonly float $price,
        public readonly string $currency,
        public readonly string $source,
        public readonly DateTimeImmutable $quotedAt
    ) {}

    /**
     * Exporta a cotacao em formato de array para serializacao.
     *
     * @return array{symbol: string, name: string, type: string, price: float, currency: string, source: string, quoted_at: DateTimeImmutable}
     */
    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'name' => $this->name,
            'type' => $this->type,
            'price' => $this->price,
            'currency' => $this->currency,
            'source' => $this->source,
            'quoted_at' => $this->quotedAt,
        ];
    }
}
