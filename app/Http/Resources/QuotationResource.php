<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serializacao de cotacoes historicas via DTO de aplicacao.
 *
 * @mixin \App\Data\QuotationHistoryItemData
 */
class QuotationResource extends JsonResource
{
    /**
     * Transforma o DTO de historico em payload JSON padronizado da API.
     *
     * @return array<string, mixed>
     */
    /**
     * Executa a rotina principal do metodo toArray.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'name' => $this->name,
            'type' => $this->type,
            'price' => $this->price,
            'currency' => $this->currency,
            'source' => $this->source,
            'status' => $this->status,
            'invalid_reason' => $this->invalidReason,
            'invalidated_at' => $this->invalidatedAt?->toIso8601String(),
            'quoted_at' => $this->quotedAt?->toIso8601String(),
            'created_at' => $this->createdAt?->toIso8601String(),
        ];
    }
}
