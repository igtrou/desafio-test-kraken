<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Financial Quotation API',
    description: 'Documentacao OpenAPI da API de cotacoes e operacoes.'
)]
#[OA\Server(
    url: '/',
    description: 'Servidor atual'
)]
#[OA\Tag(
    name: 'Auth',
    description: 'Autenticacao e token Sanctum'
)]
#[OA\Tag(
    name: 'Users',
    description: 'Perfil do usuario autenticado'
)]
#[OA\Tag(
    name: 'Quotations',
    description: 'Consulta e gestao de historico de cotacoes'
)]
#[OA\Tag(
    name: 'Operations',
    description: 'Operacoes do dashboard para auto-coleta (local/testing)'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Bearer token',
    description: 'Informe o token no cabecalho Authorization: Bearer {token}'
)]
class OpenApiSpec {}
