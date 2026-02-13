<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serializacao da cotacao em tempo real retornada por providers.
 *
 * @mixin \App\Domain\MarketData\Quote
 */
class QuoteDataResource extends JsonResource
{
    /**
     * Transforma o objeto de cotacao de dominio em payload JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'symbol' => $this->symbol,
            'name' => $this->name,
            'type' => $this->type,
            'price' => $this->price,
            'currency' => $this->currency,
            'source' => $this->source,
            'quoted_at' => $this->quotedAt->toIso8601String(),
        ];
    }
}
