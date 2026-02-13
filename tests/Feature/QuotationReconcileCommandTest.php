<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationReconcileCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara o cenario base para a execucao do teste.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'quotations.quality.outlier_guard.enabled' => true,
            'quotations.quality.outlier_guard.min_reference_points' => 3,
            'quotations.quality.outlier_guard.max_deviation_ratio' => 0.5,
        ]);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_reconcile_command_invalidates_duplicates_and_outliers(): void
    {
        $asset = Asset::factory()->create([
            'symbol' => 'BTC',
            'type' => 'crypto',
        ]);

        Quotation::factory()->create([
            'asset_id' => $asset->id,
            'price' => 68800.10,
            'source' => 'alpha_vantage',
            'quoted_at' => Carbon::parse('2026-02-07 10:00:00'),
        ]);

        $duplicate = Quotation::factory()->create([
            'asset_id' => $asset->id,
            'price' => 68800.10,
            'source' => 'alpha_vantage',
            'quoted_at' => Carbon::parse('2026-02-07 10:00:00'),
        ]);

        Quotation::factory()->create([
            'asset_id' => $asset->id,
            'price' => 68845.22,
            'source' => 'alpha_vantage',
            'quoted_at' => Carbon::parse('2026-02-07 11:00:00'),
        ]);

        Quotation::factory()->create([
            'asset_id' => $asset->id,
            'price' => 68910.15,
            'source' => 'alpha_vantage',
            'quoted_at' => Carbon::parse('2026-02-07 12:00:00'),
        ]);

        $outlier = Quotation::factory()->create([
            'asset_id' => $asset->id,
            'price' => 30.99,
            'source' => 'alpha_vantage',
            'quoted_at' => Carbon::parse('2026-02-07 13:00:00'),
        ]);

        $this->artisan('quotations:reconcile --symbol=BTC')
            ->expectsOutputToContain('Scanned: 5')
            ->expectsOutputToContain('Duplicates invalidated: 1')
            ->expectsOutputToContain('Outliers invalidated: 1')
            ->expectsOutputToContain('Total invalidated: 2')
            ->assertExitCode(0);

        $this->assertDatabaseHas((new Quotation)->getTable(), [
            'id' => $duplicate->id,
            'status' => Quotation::STATUS_INVALID,
            'invalid_reason' => Quotation::INVALID_REASON_DUPLICATE,
        ]);

        $this->assertDatabaseHas((new Quotation)->getTable(), [
            'id' => $outlier->id,
            'status' => Quotation::STATUS_INVALID,
            'invalid_reason' => Quotation::INVALID_REASON_OUTLIER,
        ]);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_reconcile_command_dry_run_does_not_persist_changes(): void
    {
        $asset = Asset::factory()->create([
            'symbol' => 'BTC',
            'type' => 'crypto',
        ]);

        Quotation::factory()->create([
            'asset_id' => $asset->id,
            'price' => 68800.10,
            'source' => 'alpha_vantage',
            'quoted_at' => Carbon::parse('2026-02-07 10:00:00'),
        ]);

        Quotation::factory()->create([
            'asset_id' => $asset->id,
            'price' => 68800.10,
            'source' => 'alpha_vantage',
            'quoted_at' => Carbon::parse('2026-02-07 10:00:00'),
        ]);

        Quotation::factory()->create([
            'asset_id' => $asset->id,
            'price' => 68845.22,
            'source' => 'alpha_vantage',
            'quoted_at' => Carbon::parse('2026-02-07 11:00:00'),
        ]);

        Quotation::factory()->create([
            'asset_id' => $asset->id,
            'price' => 68910.15,
            'source' => 'alpha_vantage',
            'quoted_at' => Carbon::parse('2026-02-07 12:00:00'),
        ]);

        Quotation::factory()->create([
            'asset_id' => $asset->id,
            'price' => 30.99,
            'source' => 'alpha_vantage',
            'quoted_at' => Carbon::parse('2026-02-07 13:00:00'),
        ]);

        $this->artisan('quotations:reconcile --symbol=BTC --dry-run')
            ->expectsOutputToContain('Dry-run complete. No records were changed.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing((new Quotation)->getTable(), [
            'status' => Quotation::STATUS_INVALID,
        ]);
    }
}
