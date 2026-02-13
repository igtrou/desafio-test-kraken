<?php

namespace Tests\Unit\Services\Quotations;

use App\Domain\Contracts\ApplicationLoggerPort;
use App\Domain\Contracts\AuditLoggerPort;
use App\Domain\Contracts\QuotationDeletionRepositoryPort;
use App\Domain\Exceptions\ForbiddenOperationException;
use App\Infrastructure\Audit\AuditLogger;
use App\Infrastructure\Observability\ApplicationLogger;
use App\Infrastructure\Quotations\QuotationDeletionRepository;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Quotations\DeleteQuotationsService;
use App\Services\Quotations\QuotationDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class QuotationDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_blocks_non_admin_user_from_deleting_single_quotation(): void
    {
        $quotation = Quotation::factory()->create();

        $deleteQuotations = Mockery::mock(DeleteQuotationsService::class);
        $deleteQuotations->shouldNotReceive('handle');

        $quotationDeletionRepository = Mockery::mock(QuotationDeletionRepositoryPort::class);
        $quotationDeletionRepository->shouldNotReceive('deleteByIdOrFail');

        $auditLogger = Mockery::mock(AuditLoggerPort::class);
        $auditLogger->shouldNotReceive('log');

        $applicationLogger = Mockery::mock(ApplicationLoggerPort::class);
        $applicationLogger->shouldNotReceive('info');

        $service = new QuotationDeletionService(
            $deleteQuotations,
            $quotationDeletionRepository,
            $auditLogger,
            $applicationLogger
        );

        $this->expectException(ForbiddenOperationException::class);

        try {
            $service->deleteSingle(
                quotationId: $quotation->id,
                canDelete: false,
                userId: null,
                auditContext: ['request_id' => 'req-unit-test', 'ip' => '127.0.0.1']
            );
        } finally {
            $this->assertDatabaseHas((new Quotation)->getTable(), [
                'id' => $quotation->id,
                'deleted_at' => null,
            ]);
        }
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_delete_all_adds_include_invalid_filter_when_status_is_not_informed(): void
    {
        $deleteQuotations = Mockery::mock(DeleteQuotationsService::class);
        $deleteQuotations
            ->shouldReceive('handle')
            ->once()
            ->with(['include_invalid' => true])
            ->andReturn(2);

        $quotationDeletionRepository = Mockery::mock(QuotationDeletionRepositoryPort::class);
        $quotationDeletionRepository->shouldNotReceive('deleteByIdOrFail');

        $auditLogger = Mockery::mock(AuditLoggerPort::class);
        $auditLogger->shouldReceive('log')->once();

        $applicationLogger = Mockery::mock(ApplicationLoggerPort::class);
        $applicationLogger->shouldReceive('info')->once();

        $service = new QuotationDeletionService(
            $deleteQuotations,
            $quotationDeletionRepository,
            $auditLogger,
            $applicationLogger
        );

        $response = $service->deleteBatch(
            validatedPayload: [
                'confirm' => true,
                'delete_all' => true,
            ],
            canDelete: true,
            userId: 1,
            auditContext: ['request_id' => 'req-unit-test', 'ip' => '127.0.0.1']
        );

        $this->assertSame('Quotations deleted successfully.', $response->message);
        $this->assertSame(2, $response->deletedCount);
    }

    /**
     * Valida o comportamento esperado deste cenario de teste.
     */
    public function test_deletion_flow_succeeds_when_audit_persistence_fails(): void
    {
        $quotation = Quotation::factory()->create();
        $admin = User::factory()->admin()->create();

        Schema::connection(config('activitylog.database_connection'))
            ->dropIfExists(config('activitylog.table_name'));

        $service = new QuotationDeletionService(
            deleteQuotations: Mockery::mock(DeleteQuotationsService::class),
            quotationDeletionRepository: new QuotationDeletionRepository,
            auditLogger: new AuditLogger,
            applicationLogger: new ApplicationLogger
        );

        $response = $service->deleteSingle(
            quotationId: $quotation->id,
            canDelete: true,
            userId: $admin->id,
            auditContext: [
                'request_id' => 'req-unit-test',
                'ip' => '127.0.0.1',
                'method' => 'DELETE',
                'path' => 'api/quotations/'.$quotation->id,
            ]
        );

        $this->assertSame('Quotation deleted successfully.', $response->message);
        $this->assertSame($quotation->id, $response->quotationId);
        $this->assertSoftDeleted((new Quotation)->getTable(), [
            'id' => $quotation->id,
        ]);
    }
}
