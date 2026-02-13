<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class QuotationPaths
{
    #[OA\Get(
        path: '/api/quotation/{symbol}',
        operationId: 'getLatestQuotation',
        tags: ['Quotations'],
        summary: 'Busca cotacao atual sem persistir',
        description: 'Dependendo de QUOTATIONS_REQUIRE_AUTH, este endpoint pode exigir token Sanctum.',
        parameters: [
            new OA\Parameter(
                name: 'symbol',
                in: 'path',
                required: true,
                description: 'Ativo consultado',
                schema: new OA\Schema(type: 'string', example: 'BTC')
            ),
            new OA\Parameter(
                name: 'provider',
                in: 'query',
                required: false,
                description: 'Provider explicito (desativa fallback automatico)',
                schema: new OA\Schema(type: 'string', enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'])
            ),
            new OA\Parameter(
                name: 'type',
                in: 'query',
                required: false,
                description: 'Tipo do ativo',
                schema: new OA\Schema(type: 'string', enum: ['stock', 'crypto', 'currency'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cotacao atual',
                content: new OA\JsonContent(ref: '#/components/schemas/QuoteDataResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Nao autenticado quando QUOTATIONS_REQUIRE_AUTH=true',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 404,
                description: 'Cotacao nao encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'Parametros invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 429,
                description: 'Rate limit atingido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 503,
                description: 'Provider indisponivel',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Exibe os dados solicitados.
     */
    public function show(): void {}

    #[OA\Post(
        path: '/api/quotation/{symbol}',
        operationId: 'storeQuotation',
        tags: ['Quotations'],
        summary: 'Busca e persiste cotacao',
        description: 'Retorna 201 quando cria novo registro e 200 quando deduplica cotacao existente.',
        parameters: [
            new OA\Parameter(
                name: 'symbol',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'BTC')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/QuotationPersistRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Cotacao persistida',
                content: new OA\JsonContent(ref: '#/components/schemas/QuotationResponse')
            ),
            new OA\Response(
                response: 200,
                description: 'Cotacao deduplicada',
                content: new OA\JsonContent(ref: '#/components/schemas/QuotationResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Nao autenticado quando QUOTATIONS_REQUIRE_AUTH=true',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 404,
                description: 'Cotacao nao encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'Parametros invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 429,
                description: 'Rate limit atingido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 503,
                description: 'Provider indisponivel',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Persiste os dados recebidos.
     */
    public function store(): void {}

    #[OA\Get(
        path: '/api/quotations',
        operationId: 'listQuotations',
        tags: ['Quotations'],
        summary: 'Lista historico de cotacoes',
        description: 'Retorna apenas cotacoes validas por padrao. Dependendo de QUOTATIONS_REQUIRE_AUTH, este endpoint pode exigir token Sanctum.',
        parameters: [
            new OA\Parameter(name: 'symbol', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'BTC')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['stock', 'crypto', 'currency'])),
            new OA\Parameter(name: 'source', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'])),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['valid', 'invalid'])),
            new OA\Parameter(name: 'include_invalid', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Historico paginado',
                content: new OA\JsonContent(ref: '#/components/schemas/QuotationCollectionResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Filtros invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 401,
                description: 'Nao autenticado quando QUOTATIONS_REQUIRE_AUTH=true',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 429,
                description: 'Rate limit atingido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Lista os recursos disponiveis.
     */
    public function index(): void {}

    #[OA\Delete(
        path: '/api/quotations/{quotation}',
        operationId: 'deleteQuotation',
        tags: ['Quotations'],
        summary: 'Remove uma cotacao (soft delete)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'quotation',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 123)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cotacao removida',
                content: new OA\JsonContent(ref: '#/components/schemas/DeleteQuotationResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Nao autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 403,
                description: 'Usuario sem permissao (admin requerido)',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 404,
                description: 'Cotacao nao encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 429,
                description: 'Rate limit atingido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Remove dados conforme os filtros informados.
     */
    public function destroy(): void {}

    #[OA\Post(
        path: '/api/quotations/bulk-delete',
        operationId: 'bulkDeleteQuotations',
        tags: ['Quotations'],
        summary: 'Remove cotacoes por filtros (soft delete, endpoint em lote)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/DeleteQuotationBatchRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Exclusao em lote executada',
                content: new OA\JsonContent(ref: '#/components/schemas/DeleteQuotationBatchResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Nao autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 403,
                description: 'Usuario sem permissao (admin requerido)',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'Payload invalido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 429,
                description: 'Rate limit atingido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Remove dados conforme os filtros informados.
     */
    public function destroyBatch(): void {}
}
