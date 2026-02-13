<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Comando utilitario que imprime uma citacao inspiradora.
 */
class InspireCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'inspire';

    /**
     * @var string
     */
    protected $description = 'Display an inspiring quote';

    /**
     * Escreve uma citacao no terminal e retorna sucesso.
     */
    public function handle(): int
    {
        $this->comment(Inspiring::quote());

        return SymfonyCommand::SUCCESS;
    }
}
