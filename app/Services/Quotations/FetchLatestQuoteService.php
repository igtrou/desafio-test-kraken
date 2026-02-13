<?php

namespace App\Services\Quotations;

use App\Domain\Contracts\MarketDataProviderManagerPort;
use App\Domain\Contracts\QuotationsConfigPort;
use App\Domain\Contracts\QuoteCachePort;
use App\Domain\MarketData\AssetTypeResolver;
use App\Domain\MarketData\Exceptions\InvalidSymbolException;
use App\Domain\MarketData\Exceptions\ProviderRateLimitException;
use App\Domain\MarketData\Exceptions\ProviderUnavailableException;
use App\Domain\MarketData\Exceptions\QuoteNotFoundException;
use App\Domain\MarketData\Quote;
use App\Domain\MarketData\SymbolNormalizer;
use DateInterval;
use DateTimeImmutable;
use Throwable;

/**
 * Busca a cotacao mais recente usando fallback entre providers e cache.
 */
class FetchLatestQuoteService
{
    /**
     * Injeta componentes de normalizacao, resolucao de tipo e acesso a providers.
     */
    /**
     * Executa a rotina principal do metodo __construct.
     */
    public function __construct(
        private readonly MarketDataProviderManagerPort $marketData,
        private readonly QuoteCachePort $quoteCache,
        private readonly QuotationsConfigPort $quotationsConfig,
        private readonly AssetTypeResolver $assetTypeResolver,
        private readonly SymbolNormalizer $symbolNormalizer,
    ) {}

    /**
     * Resolve simbolo/tipo e consulta providers ate obter cotacao valida.
     *
     * @throws InvalidSymbolException
     * @throws QuoteNotFoundException
     * @throws ProviderUnavailableException
     * @throws ProviderRateLimitException
     */
    /**
     * Executa a rotina principal do metodo handle.
     */
    public function handle(string $symbol, ?string $provider = null, ?string $type = null): Quote
    {
        $normalizedSymbol = $this->symbolNormalizer->normalize($symbol);
        $resolvedAssetType = $type ?? $this->assetTypeResolver->resolve($normalizedSymbol)->value;
        $providerOrder = $this->marketData->resolveProviderOrder($provider, $resolvedAssetType);
        $cacheTtl = max(0, $this->quotationsConfig->cacheTtlSeconds());
        $cacheExpiresAt = (new DateTimeImmutable('now'))
            ->add(new DateInterval(sprintf('PT%dS', $cacheTtl)));
        $providerExceptions = [];

        foreach ($providerOrder as $providerName) {
            $cacheKey = sprintf('quote:%s:%s:%s', $providerName, $resolvedAssetType, $normalizedSymbol);

            try {
                return $this->quoteCache->remember($cacheKey, $cacheExpiresAt, function () use ($providerName, $normalizedSymbol, $resolvedAssetType) {
                    $providerInstance = $this->marketData->provider($providerName);

                    return $providerInstance->fetch($normalizedSymbol, $resolvedAssetType);
                });
            } catch (QuoteNotFoundException|ProviderUnavailableException|ProviderRateLimitException $exception) {
                $providerExceptions[] = $exception;

                // Provider explicito ativa modo fail-fast sem tentar fallback.
                if ($provider !== null) {
                    throw $exception;
                }
            } catch (Throwable $exception) {
                $providerExceptions[] = new ProviderUnavailableException($providerName, $exception->getMessage());

                // Mantem comportamento fail-fast para erros inesperados.
                if ($provider !== null) {
                    throw $providerExceptions[array_key_last($providerExceptions)];
                }
            }
        }

        if ($providerExceptions !== []) {
            $priority = [
                ProviderRateLimitException::class,
                ProviderUnavailableException::class,
                QuoteNotFoundException::class,
            ];

            foreach ($priority as $exceptionClass) {
                foreach ($providerExceptions as $providerException) {
                    if ($providerException instanceof $exceptionClass) {
                        throw $providerException;
                    }
                }
            }
        }

        throw new ProviderUnavailableException(message: 'No providers are available to fetch this quote.');
    }
}
