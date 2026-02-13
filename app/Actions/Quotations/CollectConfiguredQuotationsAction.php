<?php

namespace App\Actions\Quotations;

use App\Data\CollectedQuotationItemData;
use App\Data\CollectedQuotationsResultData;
use App\Services\Quotations\AutoCollectCancellationService;
use App\Services\Quotations\FetchLatestQuoteService;
use App\Services\Quotations\PersistQuotationService;
use Throwable;

/**
 * Coleta cotacoes para uma lista de simbolos e opcionalmente persiste os resultados.
 */
class CollectConfiguredQuotationsAction
{
    /**
     * Injeta os servicos de consulta de cotacao e persistencia.
     */
    public function __construct(
        private readonly FetchLatestQuoteService $fetchLatestQuote,
        private readonly PersistQuotationService $persistQuotation,
        private readonly AutoCollectCancellationService $cancellation,
    ) {}

    /**
     * Processa simbolo a simbolo e monta um resumo consolidado da execucao.
     *
     * @param  array<int, string>  $symbols
     * @param  string|null  $provider Nome explicito do provider; null usa fallback configurado.
     * @param  bool  $dryRun Quando true, consulta sem gravar ativos/cotacoes.
     * @param  string|null  $runId Identificador da execucao, usado para cancelamento cooperativo.
     */
    public function __invoke(
        array $symbols,
        ?string $provider = null,
        bool $dryRun = false,
        ?string $runId = null
    ): CollectedQuotationsResultData
    {
        $successfulSymbolsCount = 0;
        $failedSymbolsCount = 0;
        $collectionItems = [];
        $canceled = false;

        foreach ($symbols as $inputSymbol) {
            if ($this->cancellation->isRequested($runId)) {
                $canceled = true;
                break;
            }

            try {
                $fetchedQuote = $this->fetchLatestQuote->handle($inputSymbol, $provider, null);

                $persistedQuotationId = null;

                // Em dry-run, validamos integracoes externas sem alterar estado persistido.
                if (! $dryRun) {
                    $persistedQuotation = $this->persistQuotation->handle($fetchedQuote, null);
                    $persistedQuotationId = $persistedQuotation['id'];
                }

                $successfulSymbolsCount++;
                $collectionItems[] = CollectedQuotationItemData::ok(
                    $fetchedQuote->symbol,
                    $fetchedQuote->source,
                    $fetchedQuote->price,
                    $persistedQuotationId
                );
            } catch (Throwable $exception) {
                // Mantem o processamento dos demais simbolos mesmo com falha pontual.
                $failedSymbolsCount++;
                $collectionItems[] = CollectedQuotationItemData::error($inputSymbol, $exception->getMessage());
            }
        }

        return new CollectedQuotationsResultData(
            total: count($symbols),
            success: $successfulSymbolsCount,
            failed: $failedSymbolsCount,
            items: $collectionItems,
            canceled: $canceled
        );
    }
}
