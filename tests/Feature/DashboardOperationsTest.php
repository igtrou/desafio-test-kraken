<?php

namespace Tests\Feature;

use App\Infrastructure\Config\EnvFileEditor;
use App\Services\Dashboard\AutoCollectHealthBaselineService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DashboardOperationsTest extends TestCase
{
    private string $tempEnvFile;

    private string $tempHistoryFile;

    /**
     * Prepara o cenario base para a execucao do teste.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tempEnvFile = storage_path('framework/testing/dashboard-ops.env');
        $this->tempHistoryFile = storage_path('framework/testing/collect-runs.jsonl');

        if (! is_dir(dirname($this->tempEnvFile))) {
            mkdir(dirname($this->tempEnvFile), 0777, true);
        }

        file_put_contents($this->tempEnvFile, implode(PHP_EOL, [
            'QUOTATIONS_AUTO_COLLECT_ENABLED=false',
            'QUOTATIONS_AUTO_COLLECT_INTERVAL_MINUTES=15',
            'QUOTATIONS_AUTO_COLLECT_SYMBOLS=BTC,ETH,MSFT,USD-BRL',
            'QUOTATIONS_AUTO_COLLECT_PROVIDER=',
            '',
        ]));

        config([
            'quotations.auto_collect.enabled' => false,
            'quotations.auto_collect.interval_minutes' => 15,
            'quotations.auto_collect.symbols' => ['BTC', 'ETH', 'MSFT', 'USDBRL'],
            'quotations.auto_collect.provider' => null,
            'quotations.auto_collect.history_path' => $this->tempHistoryFile,
        ]);

        $this->app->bind(EnvFileEditor::class, fn (): EnvFileEditor => new EnvFileEditor($this->tempEnvFile));
        $this->app->make(AutoCollectHealthBaselineService::class)->clear();
    }

    /**
     * Limpa o cenario apos a execucao do teste.
     */
    protected function tearDown(): void
    {
        if (file_exists($this->tempEnvFile)) {
            unlink($this->tempEnvFile);
        }

        if (file_exists($this->tempHistoryFile)) {
            unlink($this->tempHistoryFile);
        }

        Carbon::setTestNow();
        $this->app->make(AutoCollectHealthBaselineService::class)->clear();

        parent::tearDown();
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_can_read_auto_collect_config(): void
    {
        $response = $this->getJson('/dashboard/operations/auto-collect');

        $response->assertOk()
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.interval_minutes', 15)
            ->assertJsonPath('data.symbols.0', 'BTC')
            ->assertJsonPath('data.cron_expression', '*/15 * * * *');
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_can_update_auto_collect_config_and_persist_to_env_file(): void
    {
        $response = $this->putJson('/dashboard/operations/auto-collect', [
            'enabled' => true,
            'interval_minutes' => 5,
            'symbols' => ['btc', 'eth', 'PETR4.SA'],
            'provider' => 'awesome_api',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.interval_minutes', 5)
            ->assertJsonPath('data.symbols.0', 'BTC')
            ->assertJsonPath('data.symbols.1', 'ETH')
            ->assertJsonPath('data.symbols.2', 'PETR4.SA')
            ->assertJsonPath('data.provider', 'awesome_api')
            ->assertJsonPath('data.cron_expression', '*/5 * * * *');

        $updatedEnv = file_get_contents($this->tempEnvFile) ?: '';

        $this->assertStringContainsString('QUOTATIONS_AUTO_COLLECT_ENABLED=true', $updatedEnv);
        $this->assertStringContainsString('QUOTATIONS_AUTO_COLLECT_INTERVAL_MINUTES=5', $updatedEnv);
        $this->assertStringContainsString('QUOTATIONS_AUTO_COLLECT_SYMBOLS=BTC,ETH,PETR4.SA', $updatedEnv);
        $this->assertStringContainsString('QUOTATIONS_AUTO_COLLECT_PROVIDER=awesome_api', $updatedEnv);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_run_auto_collect_applies_fallback_for_mixed_asset_types_when_provider_is_not_forced(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->withArgs(function (string $command, array $arguments): bool {
                if ($command !== 'quotations:collect') {
                    return false;
                }

                if (! isset($arguments['--symbol']) || ! is_array($arguments['--symbol'])) {
                    return false;
                }

                if (isset($arguments['--provider'])) {
                    return false;
                }

                if (($arguments['--ignore-config-provider'] ?? false) !== true) {
                    return false;
                }

                if (($arguments['--allow-partial-success'] ?? false) !== true) {
                    return false;
                }

                if (($arguments['--trigger'] ?? null) !== 'dashboard') {
                    return false;
                }

                return true;
            })
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Done. total=4 success=4 failed=0');

        $response = $this->postJson('/dashboard/operations/auto-collect/run', [
            'symbols' => ['BTC', 'ETH', 'MSFT', 'USD-BRL'],
            'provider' => 'awesome_api',
            'dry_run' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.requested_provider', 'awesome_api')
            ->assertJsonPath('data.effective_provider', null)
            ->assertJsonPath('data.auto_fallback_applied', true)
            ->assertJsonPath('data.allow_partial_success', true)
            ->assertJsonPath('data.summary.success', 4)
            ->assertJsonPath('data.summary.failed', 0);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_run_auto_collect_uses_fallback_when_provider_is_not_informed_even_with_config_provider_set(): void
    {
        config([
            'quotations.auto_collect.provider' => 'awesome_api',
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->withArgs(function (string $command, array $arguments): bool {
                if ($command !== 'quotations:collect') {
                    return false;
                }

                if (isset($arguments['--provider'])) {
                    return false;
                }

                if (($arguments['--ignore-config-provider'] ?? false) !== true) {
                    return false;
                }

                if (($arguments['--allow-partial-success'] ?? false) !== true) {
                    return false;
                }

                return ($arguments['--trigger'] ?? null) === 'dashboard';
            })
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Done. total=4 success=4 failed=0');

        $response = $this->postJson('/dashboard/operations/auto-collect/run', [
            'symbols' => ['BTC', 'ETH', 'MSFT', 'USD-BRL'],
            'dry_run' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.requested_provider', null)
            ->assertJsonPath('data.effective_provider', null)
            ->assertJsonPath('data.auto_fallback_applied', false)
            ->assertJsonPath('data.allow_partial_success', true)
            ->assertJsonPath('data.summary.success', 4)
            ->assertJsonPath('data.summary.failed', 0);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_run_auto_collect_reports_partial_summary_when_command_returns_partial_success(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->withArgs(function (string $command, array $arguments): bool {
                if ($command !== 'quotations:collect') {
                    return false;
                }

                if (($arguments['--allow-partial-success'] ?? false) !== true) {
                    return false;
                }

                return ($arguments['--trigger'] ?? null) === 'dashboard';
            })
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn(implode(PHP_EOL, [
                'Collecting 4 symbol(s) with provider [awesome_api]...',
                'OK: BTC | source=awesome_api | price=1',
                'ERROR: MSFT | Quote not found for symbol [MSFT].',
                'Done. total=4 success=3 failed=1',
                'Partial success mode is enabled: successful symbols were processed and failed symbols were skipped.',
            ]));

        $response = $this->postJson('/dashboard/operations/auto-collect/run', [
            'symbols' => ['BTC', 'ETH', 'MSFT', 'USD-BRL'],
            'provider' => 'awesome_api',
            'force_provider' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.exit_code', 0)
            ->assertJsonPath('data.allow_partial_success', true)
            ->assertJsonPath('data.summary.total', 4)
            ->assertJsonPath('data.summary.success', 3)
            ->assertJsonPath('data.summary.failed', 1);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_can_read_recent_auto_collect_history(): void
    {
        file_put_contents($this->tempHistoryFile, implode(PHP_EOL, [
            json_encode([
                'run_id' => 'run-1',
                'trigger' => 'scheduler',
                'status' => 'success',
                'finished_at' => '2026-02-08T10:00:00+00:00',
                'summary' => ['total' => 2, 'success' => 2, 'failed' => 0],
                'symbols' => ['BTC', 'ETH'],
            ], JSON_UNESCAPED_SLASHES),
            json_encode([
                'run_id' => 'run-2',
                'trigger' => 'dashboard',
                'status' => 'partial',
                'finished_at' => '2026-02-08T10:01:00+00:00',
                'summary' => ['total' => 4, 'success' => 3, 'failed' => 1],
                'symbols' => ['BTC', 'ETH', 'MSFT', 'USDBRL'],
            ], JSON_UNESCAPED_SLASHES),
            '',
        ]));

        $response = $this->getJson('/dashboard/operations/auto-collect/history?limit=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.run_id', 'run-2')
            ->assertJsonPath('data.0.trigger', 'dashboard')
            ->assertJsonPath('data.0.summary.failed', 1);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_can_reset_health_and_filter_history_without_deleting_source_file(): void
    {
        file_put_contents($this->tempHistoryFile, implode(PHP_EOL, [
            json_encode([
                'run_id' => 'run-before-reset',
                'trigger' => 'scheduler',
                'status' => 'success',
                'finished_at' => '2026-02-08T10:00:00+00:00',
                'summary' => ['total' => 2, 'success' => 2, 'failed' => 0],
                'symbols' => ['BTC', 'ETH'],
            ], JSON_UNESCAPED_SLASHES),
            json_encode([
                'run_id' => 'run-after-reset',
                'trigger' => 'dashboard',
                'status' => 'partial',
                'finished_at' => '2026-02-08T10:01:00+00:00',
                'summary' => ['total' => 4, 'success' => 3, 'failed' => 1],
                'symbols' => ['BTC', 'ETH', 'MSFT', 'USDBRL'],
            ], JSON_UNESCAPED_SLASHES),
            '',
        ]));

        Carbon::setTestNow('2026-02-08T10:00:30+00:00');

        $this->postJson('/dashboard/operations/auto-collect/health/reset')
            ->assertOk()
            ->assertJsonPath('health_reset_at', '2026-02-08T10:00:30+00:00');

        $response = $this->getJson('/dashboard/operations/auto-collect/history?limit=10');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.run_id', 'run-after-reset');

        $rawHistory = file($this->tempHistoryFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $this->assertCount(2, $rawHistory);
    }
}
