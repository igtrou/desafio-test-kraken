<?php

namespace App\Infrastructure\Config;

use App\Domain\Contracts\EnvFileEditorPort;
use RuntimeException;

/**
 * Edita variaveis no arquivo `.env` preservando formato de linhas existente.
 */
class EnvFileEditor implements EnvFileEditorPort
{
    /**
     * Permite injetar caminho customizado do arquivo de ambiente.
     */
    public function __construct(
        private readonly string $envFilePath = '',
    ) {}

    /**
     * Atualiza ou adiciona variaveis no arquivo de ambiente.
     *
     * @param  array<string, bool|int|string|null>  $variables
     */
    public function update(array $variables): void
    {
        $filePath = $this->resolveFilePath();

        if (! file_exists($filePath)) {
            throw new RuntimeException("Environment file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new RuntimeException("Unable to read environment file: {$filePath}");
        }

        $lines = preg_split('/\R/', $content) ?: [];
        $remainingKeys = array_fill_keys(array_keys($variables), true);

        foreach ($lines as $lineIndex => $line) {
            foreach ($variables as $key => $value) {
                if (! isset($remainingKeys[$key])) {
                    continue;
                }

                if (! $this->isVariableLine($line, $key)) {
                    continue;
                }

                $lines[$lineIndex] = sprintf('%s=%s', $key, $this->formatValue($value));
                unset($remainingKeys[$key]);
            }
        }

        foreach (array_keys($remainingKeys) as $missingKey) {
            $lines[] = sprintf('%s=%s', $missingKey, $this->formatValue($variables[$missingKey]));
        }

        $normalized = implode(PHP_EOL, $lines);

        if (! str_ends_with($normalized, PHP_EOL)) {
            $normalized .= PHP_EOL;
        }

        $written = file_put_contents($filePath, $normalized);

        if ($written === false) {
            throw new RuntimeException("Unable to write environment file: {$filePath}");
        }
    }

    /**
     * Resolve o caminho efetivo do arquivo `.env`.
     */
    private function resolveFilePath(): string
    {
        return $this->envFilePath !== '' ? $this->envFilePath : base_path('.env');
    }

    /**
     * Verifica se uma linha corresponde a definicao da variavel informada.
     */
    private function isVariableLine(string $line, string $key): bool
    {
        $pattern = '/^\s*'.preg_quote($key, '/').'\s*=/';

        return preg_match($pattern, $line) === 1;
    }

    /**
     * Converte valores escalares para representacao textual de `.env`.
     */
    private function formatValue(bool|int|string|null $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
