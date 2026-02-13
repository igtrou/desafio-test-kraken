<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class OperationsPaths
{
    #[OA\Get(
        path: '/dashboard/operations/auto-collect',
        operationId: 'showAutoCollectConfig',
        tags: ['Operations'],
        summary: 'Retorna configuracao atual de auto-coleta',
        description: 'Disponivel apenas em ambiente local/testing.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Configuracao atual',
                content: new OA\JsonContent(ref: '#/components/schemas/AutoCollectSettingsResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Endpoint bloqueado fora de local/testing',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Exibe os dados solicitados.
     */
    public function showAutoCollectConfig(): void {}

    #[OA\Put(
        path: '/dashboard/operations/auto-collect',
        operationId: 'updateAutoCollectConfig',
        tags: ['Operations'],
        summary: 'Atualiza configuracao de auto-coleta',
        description: 'Disponivel apenas em ambiente local/testing.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AutoCollectSettingsUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Configuracao salva',
                content: new OA\JsonContent(ref: '#/components/schemas/AutoCollectSettingsUpdateResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Endpoint bloqueado fora de local/testing',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'Payload invalido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 419,
                description: 'CSRF token invalido ou ausente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Atualiza dados existentes conforme os parametros recebidos.
     */
    public function updateAutoCollectConfig(): void {}

    #[OA\Post(
        path: '/dashboard/operations/auto-collect/run',
        operationId: 'runDashboardAutoCollect',
        tags: ['Operations'],
        summary: 'Executa quotations:collect com trigger dashboard',
        description: 'Disponivel apenas em ambiente local/testing.',
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/AutoCollectRunRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Execucao concluida',
                content: new OA\JsonContent(ref: '#/components/schemas/AutoCollectRunResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Endpoint bloqueado fora de local/testing',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'Payload invalido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 419,
                description: 'CSRF token invalido ou ausente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Executa o processo configurado.
     */
    public function runAutoCollect(): void {}

    #[OA\Get(
        path: '/dashboard/operations/auto-collect/status',
        operationId: 'showAutoCollectStatus',
        tags: ['Operations'],
        summary: 'Retorna status da execucao corrente da auto-coleta',
        description: 'Disponivel apenas em ambiente local/testing.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status retornado',
                content: new OA\JsonContent(ref: '#/components/schemas/AutoCollectStatusResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Endpoint bloqueado fora de local/testing',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Exibe os dados solicitados.
     */
    public function showAutoCollectStatus(): void {}

    #[OA\Get(
        path: '/dashboard/operations/auto-collect/history',
        operationId: 'listAutoCollectHistory',
        tags: ['Operations'],
        summary: 'Lista historico de execucoes de auto-coleta',
        description: 'Disponivel apenas em ambiente local/testing.',
        parameters: [
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                description: 'Quantidade maxima de registros (1..100)',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Historico retornado',
                content: new OA\JsonContent(ref: '#/components/schemas/AutoCollectHistoryResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Endpoint bloqueado fora de local/testing',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'Parametro limit invalido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Lista os registros conforme os filtros informados.
     */
    public function listAutoCollectHistory(): void {}

    #[OA\Post(
        path: '/dashboard/operations/auto-collect/cancel',
        operationId: 'cancelAutoCollect',
        tags: ['Operations'],
        summary: 'Solicita cancelamento da execucao de auto-coleta em andamento',
        description: 'Disponivel apenas em ambiente local/testing.',
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/AutoCollectCancelRequest')
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: 'Cancelamento solicitado',
                content: new OA\JsonContent(ref: '#/components/schemas/AutoCollectCancelResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Endpoint bloqueado fora de local/testing',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'Payload invalido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 419,
                description: 'CSRF token invalido ou ausente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Atualiza dados existentes conforme os parametros recebidos.
     */
    public function cancelAutoCollect(): void {}

    #[OA\Post(
        path: '/dashboard/operations/auto-collect/health/reset',
        operationId: 'resetAutoCollectHealth',
        tags: ['Operations'],
        summary: 'Reinicia os indicadores de saúde do painel',
        description: 'Disponivel apenas em ambiente local/testing. Não remove o histórico bruto.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Marco de saúde reiniciado',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['message', 'health_reset_at'],
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Saúde reiniciada. Os indicadores agora consideram apenas execuções após este momento.'
                        ),
                        new OA\Property(
                            property: 'health_reset_at',
                            type: 'string',
                            format: 'date-time',
                            example: '2026-02-09T19:10:00+00:00'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Endpoint bloqueado fora de local/testing',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 419,
                description: 'CSRF token invalido ou ausente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Reinicia o marco de saúde exibido no painel.
     */
    public function resetAutoCollectHealth(): void {}
}
