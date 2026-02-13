<?php

namespace App\Actions\Quotations;

use App\Services\Quotations\FetchLatestQuoteService;

/**
 * Consulta a cotacao mais recente sem persistir no historico.
 */
class ShowQuotationAction
{
    /**
     * Injeta o servico que consulta cotacoes com fallback de providers.
     */
    public function __construct(
        private readonly FetchLatestQuoteService $fetchLatestQuote
    ) {}

    /**
     * Busca a cotacao mais recente a partir dos dados validados da requisicao.
     *
     * @param  array{symbol: string, provider?: string|null, type?: string|null}  $validatedPayload
     */
    public function __invoke(array $validatedPayload)
    {
        return $this->fetchLatestQuote->handle(
            $validatedPayload['symbol'],
            $validatedPayload['provider'] ?? null,
            $validatedPayload['type'] ?? null
        );
    }
}
