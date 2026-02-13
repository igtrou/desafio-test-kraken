<?php

namespace App\Infrastructure\MarketData;

use App\Domain\Contracts\MarketDataProvider;
use App\Domain\Contracts\MarketDataProviderManagerPort;
use Closure;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Resolve e reutiliza instancias de providers de market data a partir da configuracao.
 */
class MarketDataProviderManager implements MarketDataProviderManagerPort
{
    private array $resolvedProviders = [];
    private ?string $providersFingerprint = null;

    /**
     * Injeta container e configuracao dos providers de market data.
     */
    public function __construct(
        private readonly Container $container,
        private readonly array|Closure $config
    ) {}

    /**
     * Retorna uma instancia de provider pelo nome ou pelo provider padrao configurado.
     */
    public function provider(?string $providerName = null): MarketDataProvider
    {
        $config = $this->config();
        $providerName ??= $config['default'] ?? null;

        if (! $providerName || ! isset($config['providers'][$providerName])) {
            throw new InvalidArgumentException('Market data provider not configured.');
        }

        if (! isset($this->resolvedProviders[$providerName])) {
            $providerConfig = $config['providers'][$providerName];
            $providerClass = $providerConfig['class'] ?? null;

            if (! $providerClass || ! class_exists($providerClass)) {
                throw new InvalidArgumentException("Provider class for [{$providerName}] is invalid.");
            }

            $this->resolvedProviders[$providerName] = $this->container->make($providerClass, ['config' => $providerConfig]);
        }

        return $this->resolvedProviders[$providerName];
    }

    /**
     * Resolve a ordem de fallback de providers para um tipo de ativo.
     *
     * Quando um provider preferencial e informado, o fallback eh desabilitado.
     *
     * @return array<int, string>
     */
    public function resolveProviderOrder(?string $preferredProvider, string $assetType): array
    {
        $config = $this->config();

        if ($preferredProvider !== null) {
            if (! isset($config['providers'][$preferredProvider])) {
                throw new InvalidArgumentException("Provider [{$preferredProvider}] is not configured.");
            }

            return [$preferredProvider];
        }

        $fallbacks = $config['fallbacks'][$assetType] ?? [];
        $default = $config['default'] ?? null;

        if ($fallbacks === [] && $default !== null) {
            $fallbacks = [$default];
        }

        $resolvedProviderOrder = [];

        foreach ($fallbacks as $providerName) {
            if (
                is_string($providerName) &&
                $providerName !== '' &&
                isset($config['providers'][$providerName]) &&
                ! in_array($providerName, $resolvedProviderOrder, true)
            ) {
                $resolvedProviderOrder[] = $providerName;
            }
        }

        if ($resolvedProviderOrder === []) {
            throw new InvalidArgumentException('No market data providers are available for the selected asset type.');
        }

        return $resolvedProviderOrder;
    }

    /**
     * Resolve configuracao efetiva e invalida cache local quando providers mudam.
     *
     * @return array<string, mixed>
     */
    private function config(): array
    {
        $resolvedConfig = is_array($this->config)
            ? $this->config
            : (array) ($this->config)();

        $providers = $resolvedConfig['providers'] ?? [];
        $fingerprint = md5(serialize($providers));

        if ($this->providersFingerprint !== $fingerprint) {
            $this->resolvedProviders = [];
            $this->providersFingerprint = $fingerprint;
        }

        return $resolvedConfig;
    }
}
