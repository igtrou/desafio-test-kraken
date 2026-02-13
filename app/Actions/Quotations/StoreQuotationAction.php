<?php

namespace App\Actions\Quotations;

use App\Data\StoredQuotationData;
use App\Services\Quotations\FetchLatestQuoteService;
use App\Services\Quotations\PersistQuotationService;

/**
 * Coleta uma cotacao e persiste o registro no historico.
 */
class StoreQuotationAction
{
    /**
     * Injeta os servicos de consulta de cotacao e persistencia.
     */
    public function __construct(
        private readonly FetchLatestQuoteService $fetchLatestQuote,
        private readonly PersistQuotationService $persistQuotation
    ) {}

    /**
     * Busca a cotacao atual, persiste no banco e retorna DTO para resposta HTTP.
     *
     * @param  array{symbol: string, provider?: string|null, type?: string|null}  $validatedPayload
     */
    public function __invoke(array $validatedPayload): StoredQuotationData
    {
        $quote = $this->fetchLatestQuote->handle(
            $validatedPayload['symbol'],
            $validatedPayload['provider'] ?? null,
            $validatedPayload['type'] ?? null
        );

        $persistedQuotation = $this->persistQuotation
            ->handle($quote, $validatedPayload['type'] ?? null);

        return new StoredQuotationData(
            id: $persistedQuotation['id'],
            symbol: $persistedQuotation['symbol'],
            name: $persistedQuotation['name'],
            type: $persistedQuotation['type'],
            price: $persistedQuotation['price'],
            currency: $persistedQuotation['currency'],
            source: $persistedQuotation['source'],
            status: $persistedQuotation['status'],
            invalidReason: $persistedQuotation['invalid_reason'],
            invalidatedAt: $persistedQuotation['invalidated_at'],
            quotedAt: $persistedQuotation['quoted_at'],
            createdAt: $persistedQuotation['created_at'],
            statusCode: $persistedQuotation['was_recently_created'] ? 201 : 200
        );
    }
}
