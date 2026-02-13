<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class AuthPaths
{
    #[OA\Post(
        path: '/api/auth/token',
        operationId: 'createApiToken',
        tags: ['Auth'],
        summary: 'Cria um token Sanctum',
        description: 'Troca credenciais por um token de acesso para chamadas autenticadas.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Token criado',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Falha de validacao ou credenciais invalidas',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 429,
                description: 'Rate limit excedido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Cria um novo registro com os dados recebidos.
     */
    public function createToken(): void {}

    #[OA\Delete(
        path: '/api/auth/token',
        operationId: 'revokeApiToken',
        tags: ['Auth'],
        summary: 'Revoga o token atual',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token revogado',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Nao autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Revoga o recurso informado.
     */
    public function revokeToken(): void {}

    #[OA\Get(
        path: '/api/user',
        operationId: 'getCurrentUser',
        tags: ['Users'],
        summary: 'Retorna o perfil autenticado',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil do usuario',
                content: new OA\JsonContent(ref: '#/components/schemas/UserProfileResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Nao autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    /**
     * Retorna os dados do usuario autenticado.
     */
    public function me(): void {}
}
