<?php

namespace Tests\Unit\Services\Dashboard;

use App\Domain\Contracts\ApplicationEnvironmentPort;
use App\Domain\Exceptions\ForbiddenOperationException;
use App\Services\Dashboard\DashboardOperationsAuthorizationService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DashboardOperationsAuthorizationServiceTest extends TestCase
{
    /**
     * Limpa o cenario apos a execucao do teste.
     */
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_allows_local_or_testing_environments(): void
    {
        $applicationEnvironment = Mockery::mock(ApplicationEnvironmentPort::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isLocalOrTesting')
                ->once()
                ->andReturnTrue();
        });

        $service = new DashboardOperationsAuthorizationService($applicationEnvironment);

        $service->ensureLocalOrTesting();

        $this->assertTrue(true);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_blocks_non_local_and_non_testing_environments(): void
    {
        $applicationEnvironment = Mockery::mock(ApplicationEnvironmentPort::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isLocalOrTesting')
                ->once()
                ->andReturnFalse();
        });

        $service = new DashboardOperationsAuthorizationService($applicationEnvironment);

        $this->expectException(ForbiddenOperationException::class);

        $service->ensureLocalOrTesting();
    }
}
