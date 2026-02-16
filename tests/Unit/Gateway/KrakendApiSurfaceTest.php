<?php

namespace Tests\Unit\Gateway;

use PHPUnit\Framework\TestCase;

class KrakendApiSurfaceTest extends TestCase
{
    /**
     * Evita exposicao acidental da raiz no gateway.
     */
    public function test_gateway_does_not_expose_root_surface(): void
    {
        $config = $this->loadGatewayConfig();

        $this->assertFalse(
            $this->gatewayContainsEndpoint($config['endpoints'], 'GET', '/'),
            'Endpoint raiz GET / ainda exposto no gateway.'
        );
    }

    /**
     * Evita reexposicao acidental da superficie legada /api/* no gateway.
     */
    public function test_gateway_does_not_expose_legacy_api_surface(): void
    {
        $config = $this->loadGatewayConfig();

        $legacyEndpoints = [
            ['method' => 'POST', 'endpoint' => '/api/auth/token'],
            ['method' => 'DELETE', 'endpoint' => '/api/auth/token'],
            ['method' => 'GET', 'endpoint' => '/api/user'],
            ['method' => 'GET', 'endpoint' => '/api/quotation/{symbol}'],
            ['method' => 'POST', 'endpoint' => '/api/quotation/{symbol}'],
            ['method' => 'GET', 'endpoint' => '/api/quotations'],
            ['method' => 'POST', 'endpoint' => '/api/quotations/bulk-delete'],
            ['method' => 'DELETE', 'endpoint' => '/api/quotations/{quotation}'],
        ];

        foreach ($legacyEndpoints as $legacyEndpoint) {
            $this->assertFalse(
                $this->gatewayContainsEndpoint(
                    $config['endpoints'],
                    $legacyEndpoint['method'],
                    $legacyEndpoint['endpoint'],
                ),
                sprintf(
                    'Endpoint legado ainda exposto no gateway: %s %s',
                    $legacyEndpoint['method'],
                    $legacyEndpoint['endpoint']
                )
            );
        }
    }

    /**
     * Evita reexposicao acidental dos endpoints de playground no gateway.
     */
    public function test_gateway_does_not_expose_playground_surface(): void
    {
        $config = $this->loadGatewayConfig();

        $playgroundEndpoints = [
            ['method' => 'GET', 'endpoint' => '/playground/quotation/{symbol}/snapshot'],
            ['method' => 'GET', 'endpoint' => '/playground/private/quotation/{symbol}'],
        ];

        foreach ($playgroundEndpoints as $playgroundEndpoint) {
            $this->assertFalse(
                $this->gatewayContainsEndpoint(
                    $config['endpoints'],
                    $playgroundEndpoint['method'],
                    $playgroundEndpoint['endpoint'],
                ),
                sprintf(
                    'Endpoint de playground ainda exposto no gateway: %s %s',
                    $playgroundEndpoint['method'],
                    $playgroundEndpoint['endpoint']
                )
            );
        }
    }

    /**
     * Garante que a superficie v1 do gateway cobre as rotas API principais.
     */
    public function test_v1_gateway_surface_covers_core_api_routes(): void
    {
        $config = $this->loadGatewayConfig();

        $requiredMappings = [
            ['method' => 'POST', 'endpoint' => '/v1/public/auth/token', 'backend' => '/api/auth/token'],
            ['method' => 'DELETE', 'endpoint' => '/v1/private/auth/token', 'backend' => '/api/auth/token'],
            ['method' => 'GET', 'endpoint' => '/v1/private/user', 'backend' => '/api/user'],
            ['method' => 'GET', 'endpoint' => '/v1/public/quotation/{symbol}', 'backend' => '/api/quotation/{symbol}'],
            ['method' => 'GET', 'endpoint' => '/v1/private/quotation/{symbol}', 'backend' => '/api/quotation/{symbol}'],
            ['method' => 'POST', 'endpoint' => '/v1/private/quotation/{symbol}', 'backend' => '/api/quotation/{symbol}'],
            ['method' => 'GET', 'endpoint' => '/v1/private/quotations', 'backend' => '/api/quotations'],
            ['method' => 'POST', 'endpoint' => '/v1/private/quotations/bulk-delete', 'backend' => '/api/quotations/bulk-delete'],
            ['method' => 'DELETE', 'endpoint' => '/v1/private/quotations/{quotation}', 'backend' => '/api/quotations/{quotation}'],
        ];

        foreach ($requiredMappings as $mapping) {
            $this->assertTrue(
                $this->gatewayContainsMapping(
                    $config['endpoints'],
                    $mapping['method'],
                    $mapping['endpoint'],
                    $mapping['backend'],
                ),
                sprintf(
                    'Gateway mapping ausente: %s %s -> %s',
                    $mapping['method'],
                    $mapping['endpoint'],
                    $mapping['backend']
                )
            );
        }
    }

    /**
     * Carrega a configuracao do KrakenD e valida estrutura minima.
     */
    private function loadGatewayConfig(): array
    {
        $configPath = dirname(__DIR__, 3).'/docker/krakend/krakend.json';
        $this->assertFileExists($configPath);

        $rawConfig = file_get_contents($configPath);
        $this->assertIsString($rawConfig);

        $config = json_decode($rawConfig, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($config);
        $this->assertIsArray($config['endpoints'] ?? null);

        return $config;
    }

    /**
     * Verifica se endpoint+metodo existe no gateway.
     */
    private function gatewayContainsEndpoint(
        array $endpoints,
        string $method,
        string $gatewayEndpoint,
    ): bool {
        foreach ($endpoints as $endpoint) {
            $configuredMethod = strtoupper((string) ($endpoint['method'] ?? ''));
            $configuredEndpoint = (string) ($endpoint['endpoint'] ?? '');

            if ($configuredMethod === strtoupper($method) && $configuredEndpoint === $gatewayEndpoint) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se existe endpoint+metodo no gateway encaminhando para o backend esperado.
     */
    private function gatewayContainsMapping(
        array $endpoints,
        string $method,
        string $gatewayEndpoint,
        string $backendPattern,
    ): bool {
        foreach ($endpoints as $endpoint) {
            $configuredMethod = strtoupper((string) ($endpoint['method'] ?? ''));
            $configuredEndpoint = (string) ($endpoint['endpoint'] ?? '');

            if ($configuredMethod !== strtoupper($method) || $configuredEndpoint !== $gatewayEndpoint) {
                continue;
            }

            foreach (($endpoint['backend'] ?? []) as $backend) {
                if ((string) ($backend['url_pattern'] ?? '') === $backendPattern) {
                    return true;
                }
            }
        }

        return false;
    }
}
