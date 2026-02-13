<?php

namespace App\Infrastructure\Config;

use App\Domain\Contracts\ConfigCachePort;
use Illuminate\Support\Facades\Artisan;

/**
 * Encapsula operacoes de cache de configuracao executadas via comandos Artisan.
 */
class ConfigCacheManager implements ConfigCachePort
{
    /**
     * Limpa cache de configuracao para aplicar mudancas persistidas no `.env`.
     */
    /**
     * Executa a rotina principal do metodo clear.
     */
    public function clear(): void
    {
        Artisan::call('config:clear');
    }
}
