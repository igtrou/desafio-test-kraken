<?php

namespace Tests\Unit;

use App\Domain\MarketData\Exceptions\InvalidSymbolException;
use App\Domain\MarketData\SymbolNormalizer;
use PHPUnit\Framework\TestCase;

class SymbolNormalizerTest extends TestCase
{
    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_normalizes_symbols_to_expected_format(): void
    {
        $normalizer = new SymbolNormalizer;

        $this->assertSame('BTC', $normalizer->normalize('btc'));
        $this->assertSame('USDBRL', $normalizer->normalize('usdbrl'));
        $this->assertSame('USDBRL', $normalizer->normalize('USD-BRL'));
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_throws_for_invalid_symbol(): void
    {
        $this->expectException(InvalidSymbolException::class);

        (new SymbolNormalizer)->normalize('BTC$');
    }
}
