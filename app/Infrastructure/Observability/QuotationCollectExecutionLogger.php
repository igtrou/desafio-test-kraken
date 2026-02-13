<?php

namespace App\Infrastructure\Observability;

use App\Domain\Contracts\QuotationCollectExecutionLoggerPort;
use Illuminate\Support\Facades\Log;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Registra eventos e historico de execucoes de coleta de cotacoes.
 */
class QuotationCollectExecutionLogger implements QuotationCollectExecutionLoggerPort
{
    /**
     * Registra evento de inicio da execucao.
     *
     * @param  array<string, mixed>  $context
     */
    public function started(array $context): void
    {
        $this->writeLog('collect_started', $context);
    }

    /**
     * Registra evento de finalizacao e persiste entrada no historico.
     *
     * @param  array<string, mixed>  $context
     */
    public function finished(array $context): void
    {
        $this->writeLog('collect_finished', $context);
        $this->appendHistoryEntry($context);
    }

    /**
     * Retorna entradas mais recentes consolidadas de arquivos principal e fallback.
     *
     * @return array<int, array<string, mixed>>
     */
    public function latest(int $limit = 20): array
    {
        $safeLimit = max(1, min(100, $limit));
        $entries = [];
        $sequence = 0;

        foreach ($this->historyPaths() as $path) {
            if (! is_file($path)) {
                continue;
            }

            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if (! is_array($lines) || $lines === []) {
                continue;
            }

            $recentLines = array_slice($lines, -$safeLimit);

            foreach ($recentLines as $line) {
                $decoded = json_decode($line, true);

                if (! is_array($decoded)) {
                    continue;
                }

                $entries[] = [
                    'entry' => $decoded,
                    'timestamp' => $this->entryTimestamp($decoded),
                    'sequence' => $sequence++,
                ];
            }
        }

        if ($entries === []) {
            return [];
        }

        usort(
            $entries,
            static fn (array $left, array $right): int => ($right['timestamp'] <=> $left['timestamp'])
                ?: ($right['sequence'] <=> $left['sequence'])
        );

        return array_values(array_map(
            static fn (array $item): array => $item['entry'],
            array_slice($entries, 0, $safeLimit)
        ));
    }

    /**
     * Tenta persistir contexto em JSONL usando estrategia best-effort.
     *
     * @param  array<string, mixed>  $context
     */
    private function appendHistoryEntry(array $context): void
    {
        try {
            $encodedContext = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (! is_string($encodedContext) || $encodedContext === '') {
                return;
            }

            foreach ($this->historyPaths() as $path) {
                if ($this->appendToPath($path, $encodedContext)) {
                    return;
                }
            }
        } catch (Throwable) {
            // Best-effort history persistence; never break collection execution flow.
        }
    }

    /**
     * Retorna caminho primario de persistencia do historico.
     */
    private function historyPath(): string
    {
        return (string) config(
            'quotations.auto_collect.history_path',
            storage_path('app/operations/collect-runs.jsonl')
        );
    }

    /**
     * Retorna caminho fallback usado quando escrita no primario falha.
     */
    private function fallbackHistoryPath(): string
    {
        return (string) config(
            'quotations.auto_collect.history_fallback_path',
            storage_path('framework/operations/collect-runs.local.jsonl')
        );
    }

    /**
     * Retorna lista ordenada de caminhos elegiveis para historico.
     *
     * @return array<int, string>
     */
    private function historyPaths(): array
    {
        return array_values(array_unique(array_filter([
            $this->historyPath(),
            $this->fallbackHistoryPath(),
        ], static fn (string $path): bool => $path !== '')));
    }

    /**
     * Faz append atomico de uma linha JSONL no caminho informado.
     */
    private function appendToPath(string $path, string $encodedContext): bool
    {
        try {
            $directory = dirname($path);

            if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
                return false;
            }

            return file_put_contents($path, $encodedContext.PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Extrai timestamp da entrada para ordenacao das execucoes.
     *
     * @param  array<string, mixed>  $entry
     */
    private function entryTimestamp(array $entry): int
    {
        $rawDate = $entry['finished_at'] ?? $entry['started_at'] ?? null;

        if (! is_string($rawDate) || $rawDate === '') {
            return 0;
        }

        try {
            return (new DateTimeImmutable($rawDate, new DateTimeZone('UTC')))->getTimestamp();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Escreve evento no canal de log dedicado sem impactar fluxo principal.
     *
     * @param  array<string, mixed>  $context
     */
    private function writeLog(string $event, array $context): void
    {
        try {
            Log::channel('quotation_collect')->info($event, $context);
        } catch (Throwable) {
            // Best-effort logging; command flow should not depend on log write permissions.
        }
    }
}
