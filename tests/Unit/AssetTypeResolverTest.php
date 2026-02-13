<?php

namespace Tests\Unit;

use App\Domain\MarketData\AssetType;
use App\Domain\MarketData\AssetTypeResolver;
use PHPUnit\Framework\TestCase;

class AssetTypeResolverTest extends TestCase
{
    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_resolves_asset_types_consistently(): void
    {
        $resolver = new AssetTypeResolver(
            cryptoSymbols: ['BTC', 'ETH'],
            currencyCodes: ['USD', 'BRL', 'EUR']
        );

        $this->assertSame(AssetType::Crypto, $resolver->resolve('BTC'));
        $this->assertSame(AssetType::Crypto, $resolver->resolve('BTCUSD'));
        $this->assertSame(AssetType::Currency, $resolver->resolve('USDBRL'));
        $this->assertSame(AssetType::Stock, $resolver->resolve('MSFT'));
    }
}
