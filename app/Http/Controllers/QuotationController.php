<?php

namespace App\Http\Controllers;

use App\Actions\Quotations\DeleteQuotationBatchAction;
use App\Actions\Quotations\DeleteSingleQuotationAction;
use App\Actions\Quotations\IndexQuotationHistoryAction;
use App\Actions\Quotations\ShowQuotationAction;
use App\Actions\Quotations\StoreQuotationAction;
use App\Http\Controllers\Concerns\BuildsAuditContext;
use App\Http\Requests\DeleteQuotationBatchRequest;
use App\Http\Requests\QuotationIndexRequest;
use App\Http\Requests\QuotationRequest;
use App\Http\Resources\QuotationResource;
use App\Http\Resources\QuoteDataResource;
use App\Http\Resources\StoredQuotationDataResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Exposes quotation read/store endpoints with minimal orchestration logic.
 */
class QuotationController extends Controller
{
    use BuildsAuditContext;

    /**
     * Retrieves the latest quote from external providers without persisting it.
     */
    public function show(
        QuotationRequest $request,
        ShowQuotationAction $showQuotation
    ): JsonResponse|QuoteDataResource {
        $quote = $showQuotation($request->validated());

        return (new QuoteDataResource($quote))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Fetches and persists a quotation for a symbol.
     */
    public function store(
        QuotationRequest $request,
        StoreQuotationAction $storeQuotation
    ): JsonResponse|StoredQuotationDataResource {
        $storedQuotation = $storeQuotation($request->validated());

        return (new StoredQuotationDataResource($storedQuotation))
            ->response()
            ->setStatusCode($storedQuotation->statusCode);
    }

    /**
     * Returns paginated historical quotations with optional filters.
     */
    public function index(
        QuotationIndexRequest $request,
        IndexQuotationHistoryAction $indexQuotationHistory
    ): ResourceCollection {
        $quotations = $indexQuotationHistory($request->validated());
        $paginator = new LengthAwarePaginator(
            items: $quotations->items,
            total: $quotations->total,
            perPage: $quotations->perPage,
            currentPage: $quotations->currentPage,
            options: [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );
        $paginator->appends($request->query());

        return QuotationResource::collection($paginator);
    }

    /**
     * Soft deletes a single quotation from historical data.
     */
    public function destroy(
        Request $request,
        int $quotation,
        DeleteSingleQuotationAction $deleteSingleQuotation
    ): JsonResponse
    {
        $user = $request->user();
        $response = $deleteSingleQuotation(
            quotationId: $quotation,
            canDelete: (bool) $user?->is_admin,
            userId: $user?->id,
            auditContext: $this->buildAuditContext($request)
        );

        return response()->json($response->toArray(), $response->statusCode);
    }

    /**
     * Soft deletes a filtered quotation subset.
     */
    public function destroyBatch(
        DeleteQuotationBatchRequest $request,
        DeleteQuotationBatchAction $deleteQuotationBatch
    ): JsonResponse {
        $user = $request->user();
        $response = $deleteQuotationBatch(
            validatedPayload: $request->validated(),
            canDelete: (bool) $user?->is_admin,
            userId: $user?->id,
            auditContext: $this->buildAuditContext($request)
        );

        return response()->json($response->toArray(), $response->statusCode);
    }
}
