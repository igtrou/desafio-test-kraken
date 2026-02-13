<?php

namespace App\Infrastructure\Console;

use App\Domain\Contracts\QuotationCollectCommandRunnerPort;
use Illuminate\Support\Facades\Artisan;

/**
 * Encapsula execucao programatica do comando `quotations:collect`.
 */
class QuotationCollectCommandRunner implements QuotationCollectCommandRunnerPort
{
    /**
     * Executa o comando de coleta e devolve codigo de saida com output capturado.
     *
     * @param  array<string, mixed>  $arguments
     * @return array{exit_code: int, output: string}
     */
    public function run(array $arguments): array
    {
        $exitCode = Artisan::call('quotations:collect', $arguments);

        return [
            'exit_code' => $exitCode,
            'output' => trim(Artisan::output()),
        ];
    }
}
