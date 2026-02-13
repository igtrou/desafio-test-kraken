<?php

namespace Tests\Feature;

use App\Domain\Exceptions\ForbiddenOperationException;
use App\Services\Dashboard\DashboardOperationsAuthorizationService;
use Mockery\MockInterface;
use Tests\TestCase;

class DashboardOperationsAuthorizationTest extends TestCase
{
    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_operations_page_returns_forbidden_when_authorization_service_blocks_access(): void
    {
        $this->mockAuthorizationAsForbidden();

        $this->get('/dashboard/operations')
            ->assertForbidden();
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_operations_json_endpoints_return_forbidden_when_authorization_service_blocks_access(): void
    {
        $this->mockAuthorizationAsForbidden();

        $this->getJson('/dashboard/operations/auto-collect')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'forbidden');

        $this->putJson('/dashboard/operations/auto-collect', [
            'enabled' => true,
            'interval_minutes' => 5,
            'symbols' => ['BTC'],
            'provider' => 'awesome_api',
        ])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'forbidden');

        $this->postJson('/dashboard/operations/auto-collect/run', [
            'symbols' => ['BTC'],
            'provider' => 'awesome_api',
            'dry_run' => true,
            'force_provider' => false,
        ])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'forbidden');

        $this->getJson('/dashboard/operations/auto-collect/history?limit=10')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'forbidden');

        $this->postJson('/dashboard/operations/auto-collect/health/reset')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'forbidden');
    }

    /**
     * Executa a rotina principal do metodo mockAuthorizationAsForbidden.
     */
    private function mockAuthorizationAsForbidden(): void
    {
        $this->mock(
            DashboardOperationsAuthorizationService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('ensureLocalOrTesting')
                    ->andThrow(new ForbiddenOperationException('Blocked for this environment.'));
            }
        );
    }
}
