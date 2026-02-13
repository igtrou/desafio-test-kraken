<?php

namespace Tests\Unit;

use App\Infrastructure\MarketData\MarketDataProviderManager;
use App\Infrastructure\MarketData\Providers\AlphaVantageProvider;
use App\Infrastructure\MarketData\Providers\AwesomeApiProvider;
use Illuminate\Container\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MarketDataProviderManagerTest extends TestCase
{
    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_resolves_provider_order_by_asset_type(): void
    {
        $manager = $this->manager();

        $this->assertSame(
            ['alpha_vantage', 'awesome_api'],
            $manager->resolveProviderOrder(null, 'stock')
        );

        $this->assertSame(
            ['awesome_api', 'alpha_vantage'],
            $manager->resolveProviderOrder(null, 'crypto')
        );
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_respects_explicit_provider_and_throws_when_unknown(): void
    {
        $manager = $this->manager();

        $this->assertSame(
            ['awesome_api'],
            $manager->resolveProviderOrder('awesome_api', 'stock')
        );

        $this->expectException(InvalidArgumentException::class);
        $manager->resolveProviderOrder('unknown_provider', 'stock');
    }

    /**
     * Executa a rotina principal do metodo manager.
     */
    private function manager(): MarketDataProviderManager
    {
        return new MarketDataProviderManager(new Container, [
            'default' => 'awesome_api',
            'providers' => [
                'alpha_vantage' => ['class' => AlphaVantageProvider::class],
                'awesome_api' => ['class' => AwesomeApiProvider::class],
            ],
            'fallbacks' => [
                'stock' => ['alpha_vantage', 'awesome_api'],
                'crypto' => ['awesome_api', 'alpha_vantage'],
                'currency' => ['awesome_api', 'alpha_vantage'],
            ],
        ]);
    }
}
