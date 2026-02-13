<?php

namespace Tests\Feature;

use App\Infrastructure\Observability\QuotationCollectExecutionLogger;
use Tests\TestCase;

class QuotationCollectExecutionLoggerTest extends TestCase
{
    private string $primaryHistoryPath;

    private string $fallbackHistoryPath;

    /**
     * Prepara o cenario base para a execucao do teste.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $baseDirectory = storage_path('framework/testing/quotation-collect-history');
        $this->primaryHistoryPath = $baseDirectory.'/primary/collect-runs.jsonl';
        $this->fallbackHistoryPath = $baseDirectory.'/fallback/collect-runs.local.jsonl';

        $this->cleanupPath($this->primaryHistoryPath);
        $this->cleanupPath($this->fallbackHistoryPath);

        config([
            'quotations.auto_collect.history_path' => $this->primaryHistoryPath,
            'quotations.auto_collect.history_fallback_path' => $this->fallbackHistoryPath,
        ]);
    }

    /**
     * Limpa o cenario apos a execucao do teste.
     */
    protected function tearDown(): void
    {
        $this->cleanupPath($this->primaryHistoryPath);
        $this->cleanupPath($this->fallbackHistoryPath);

        parent::tearDown();
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_finished_writes_to_fallback_when_primary_history_file_is_not_writable(): void
    {
        $primaryDirectory = dirname($this->primaryHistoryPath);

        if (! is_dir($primaryDirectory)) {
            mkdir($primaryDirectory, 0777, true);
        }

        file_put_contents($this->primaryHistoryPath, '');
        chmod($this->primaryHistoryPath, 0444);

        try {
            $logger = app(QuotationCollectExecutionLogger::class);
            $logger->finished([
                'run_id' => 'dashboard-run-fallback',
                'finished_at' => '2026-02-08T10:00:00+00:00',
                'status' => 'success',
                'summary' => ['total' => 1, 'success' => 1, 'failed' => 0],
                'symbols' => ['BTC'],
            ]);
        } finally {
            chmod($this->primaryHistoryPath, 0644);
        }

        $this->assertFileExists($this->fallbackHistoryPath);
        $entries = $this->readJsonl($this->fallbackHistoryPath);
        $this->assertCount(1, $entries);
        $this->assertSame('dashboard-run-fallback', $entries[0]['run_id'] ?? null);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_latest_reads_primary_and_fallback_history_and_returns_newest_entries(): void
    {
        $this->writeJsonl($this->primaryHistoryPath, [
            [
                'run_id' => 'run-old',
                'finished_at' => '2026-02-08T09:58:00+00:00',
                'status' => 'success',
            ],
            [
                'run_id' => 'run-middle',
                'finished_at' => '2026-02-08T09:59:00+00:00',
                'status' => 'partial',
            ],
        ]);

        $this->writeJsonl($this->fallbackHistoryPath, [
            [
                'run_id' => 'run-newest',
                'finished_at' => '2026-02-08T10:00:00+00:00',
                'status' => 'failed',
            ],
        ]);

        $logger = app(QuotationCollectExecutionLogger::class);
        $entries = $logger->latest(2);

        $this->assertCount(2, $entries);
        $this->assertSame('run-newest', $entries[0]['run_id'] ?? null);
        $this->assertSame('run-middle', $entries[1]['run_id'] ?? null);
    }

    /**
     * Escreve os dados na fonte configurada.
     */
    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    private function writeJsonl(string $path, array $entries): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $encoded = array_map(
            static fn (array $entry): string => (string) json_encode($entry, JSON_UNESCAPED_SLASHES),
            $entries
        );

        file_put_contents($path, implode(PHP_EOL, $encoded).PHP_EOL);
    }

    /**
     * Le os dados da fonte configurada.
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJsonl(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (! is_array($lines)) {
            return [];
        }

        $entries = [];

        foreach ($lines as $line) {
            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return $entries;
    }

    /**
     * Executa a rotina principal do metodo cleanupPath.
     */
    private function cleanupPath(string $path): void
    {
        if (is_file($path)) {
            chmod($path, 0644);
            unlink($path);
        }

        $directory = dirname($path);

        if (is_dir($directory)) {
            rmdir($directory);
        }

        $parentDirectory = dirname($directory);

        if (is_dir($parentDirectory)) {
            @rmdir($parentDirectory);
        }
    }
}
