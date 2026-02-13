<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiError',
    type: 'object',
    required: ['message', 'error_code', 'request_id'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Validation failed.'),
        new OA\Property(property: 'error_code', type: 'string', example: 'validation_error'),
        new OA\Property(property: 'request_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'details', type: 'object', nullable: true, additionalProperties: true),
    ]
)]
#[OA\Schema(
    schema: 'MessageResponse',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Token revoked successfully.'),
    ]
)]
#[OA\Schema(
    schema: 'AuthTokenRequest',
    type: 'object',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'test@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
        new OA\Property(property: 'device_name', type: 'string', maxLength: 100, nullable: true, example: 'postman'),
    ]
)]
#[OA\Schema(
    schema: 'AuthTokenData',
    type: 'object',
    required: ['token', 'token_type', 'device_name'],
    properties: [
        new OA\Property(property: 'token', type: 'string', example: '1|exampletoken'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'device_name', type: 'string', example: 'postman'),
    ]
)]
#[OA\Schema(
    schema: 'AuthTokenResponse',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Token created successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/AuthTokenData'),
    ]
)]
#[OA\Schema(
    schema: 'UserPermissions',
    type: 'object',
    required: ['delete_quotations'],
    properties: [
        new OA\Property(property: 'delete_quotations', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'UserProfile',
    type: 'object',
    required: ['id', 'name', 'email', 'is_admin', 'permissions'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Test User'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'test@example.com'),
        new OA\Property(property: 'is_admin', type: 'boolean', example: true),
        new OA\Property(property: 'permissions', ref: '#/components/schemas/UserPermissions'),
    ]
)]
#[OA\Schema(
    schema: 'UserProfileResponse',
    type: 'object',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/UserProfile'),
    ]
)]
#[OA\Schema(
    schema: 'QuoteData',
    type: 'object',
    required: ['symbol', 'name', 'type', 'price', 'currency', 'source', 'quoted_at'],
    properties: [
        new OA\Property(property: 'symbol', type: 'string', example: 'BTC'),
        new OA\Property(property: 'name', type: 'string', example: 'BTC/USD'),
        new OA\Property(property: 'type', type: 'string', enum: ['stock', 'crypto', 'currency'], example: 'crypto'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 51000.35),
        new OA\Property(property: 'currency', type: 'string', example: 'USD'),
        new OA\Property(property: 'source', type: 'string', enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'], example: 'awesome_api'),
        new OA\Property(property: 'quoted_at', type: 'string', format: 'date-time', example: '2026-02-07T13:00:00+00:00'),
    ]
)]
#[OA\Schema(
    schema: 'QuoteDataResponse',
    type: 'object',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/QuoteData'),
    ]
)]
#[OA\Schema(
    schema: 'QuotationPersistRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['stock', 'crypto', 'currency'], nullable: true, example: 'crypto'),
        new OA\Property(property: 'provider', type: 'string', enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'], nullable: true, example: 'awesome_api'),
    ]
)]
#[OA\Schema(
    schema: 'Quotation',
    type: 'object',
    required: ['id', 'symbol', 'name', 'type', 'price', 'currency', 'source', 'status', 'quoted_at', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 123),
        new OA\Property(property: 'symbol', type: 'string', example: 'BTC'),
        new OA\Property(property: 'name', type: 'string', example: 'BTC/USD'),
        new OA\Property(property: 'type', type: 'string', enum: ['stock', 'crypto', 'currency'], example: 'crypto'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 51000.35),
        new OA\Property(property: 'currency', type: 'string', example: 'USD'),
        new OA\Property(property: 'source', type: 'string', enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'], example: 'awesome_api'),
        new OA\Property(property: 'status', type: 'string', enum: ['valid', 'invalid'], example: 'valid'),
        new OA\Property(property: 'invalid_reason', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'invalidated_at', type: 'string', format: 'date-time', nullable: true, example: null),
        new OA\Property(property: 'quoted_at', type: 'string', format: 'date-time', nullable: true, example: '2026-02-07T13:00:00+00:00'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true, example: '2026-02-07T13:00:05+00:00'),
    ]
)]
#[OA\Schema(
    schema: 'QuotationResponse',
    type: 'object',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Quotation'),
    ]
)]
#[OA\Schema(
    schema: 'PaginationLinks',
    type: 'object',
    required: ['first', 'last'],
    properties: [
        new OA\Property(property: 'first', type: 'string', format: 'uri', nullable: true, example: 'http://localhost/api/quotations?page=1'),
        new OA\Property(property: 'last', type: 'string', format: 'uri', nullable: true, example: 'http://localhost/api/quotations?page=3'),
        new OA\Property(property: 'prev', type: 'string', format: 'uri', nullable: true, example: null),
        new OA\Property(property: 'next', type: 'string', format: 'uri', nullable: true, example: 'http://localhost/api/quotations?page=2'),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMetaLink',
    type: 'object',
    required: ['url', 'label', 'active'],
    properties: [
        new OA\Property(property: 'url', type: 'string', format: 'uri', nullable: true, example: 'http://localhost/api/quotations?page=1'),
        new OA\Property(property: 'label', type: 'string', example: '1'),
        new OA\Property(property: 'active', type: 'boolean', example: false),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    type: 'object',
    required: ['current_page', 'last_page', 'per_page', 'total'],
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'from', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(property: 'path', type: 'string', format: 'uri', example: 'http://localhost/api/quotations'),
        new OA\Property(property: 'per_page', type: 'integer', example: 20),
        new OA\Property(property: 'to', type: 'integer', nullable: true, example: 20),
        new OA\Property(property: 'total', type: 'integer', example: 42),
        new OA\Property(
            property: 'links',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PaginationMetaLink')
        ),
    ]
)]
#[OA\Schema(
    schema: 'QuotationCollectionResponse',
    type: 'object',
    required: ['data', 'links', 'meta'],
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Quotation')),
        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
#[OA\Schema(
    schema: 'DeleteQuotationBatchRequest',
    type: 'object',
    required: ['confirm'],
    properties: [
        new OA\Property(property: 'confirm', type: 'boolean', example: true),
        new OA\Property(property: 'delete_all', type: 'boolean', nullable: true, example: false),
        new OA\Property(property: 'symbol', type: 'string', nullable: true, example: 'BTC'),
        new OA\Property(property: 'type', type: 'string', enum: ['stock', 'crypto', 'currency'], nullable: true, example: 'crypto'),
        new OA\Property(property: 'source', type: 'string', enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'], nullable: true, example: 'awesome_api'),
        new OA\Property(property: 'status', type: 'string', enum: ['valid', 'invalid'], nullable: true, example: 'valid'),
        new OA\Property(property: 'include_invalid', type: 'boolean', nullable: true, example: false),
        new OA\Property(property: 'date_from', type: 'string', format: 'date', nullable: true, example: '2026-02-01'),
        new OA\Property(property: 'date_to', type: 'string', format: 'date', nullable: true, example: '2026-02-07'),
    ]
)]
#[OA\Schema(
    schema: 'DeleteQuotationResponse',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Quotation deleted successfully.'),
        new OA\Property(
            property: 'data',
            type: 'object',
            required: ['id'],
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 123),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'DeleteQuotationBatchResponse',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Quotations deleted successfully.'),
        new OA\Property(
            property: 'data',
            type: 'object',
            required: ['deleted_count'],
            properties: [
                new OA\Property(property: 'deleted_count', type: 'integer', example: 2),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'AutoCollectSettings',
    type: 'object',
    required: [
        'enabled',
        'interval_minutes',
        'symbols',
        'symbols_csv',
        'provider',
        'available_providers',
        'cron_expression',
        'requires_scheduler_restart',
        'scheduler_restart_note',
    ],
    properties: [
        new OA\Property(property: 'enabled', type: 'boolean', example: true),
        new OA\Property(property: 'interval_minutes', type: 'integer', minimum: 1, maximum: 59, example: 15),
        new OA\Property(
            property: 'symbols',
            type: 'array',
            items: new OA\Items(type: 'string', example: 'BTC')
        ),
        new OA\Property(property: 'symbols_csv', type: 'string', example: 'BTC,ETH,MSFT'),
        new OA\Property(
            property: 'provider',
            type: 'string',
            enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'],
            nullable: true,
            example: null
        ),
        new OA\Property(
            property: 'available_providers',
            type: 'array',
            items: new OA\Items(type: 'string', enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'])
        ),
        new OA\Property(property: 'cron_expression', type: 'string', example: '*/15 * * * *'),
        new OA\Property(property: 'requires_scheduler_restart', type: 'boolean', example: true),
        new OA\Property(
            property: 'scheduler_restart_note',
            type: 'string',
            example: 'If schedule:work is running, restart it after saving schedule settings.'
        ),
    ]
)]
#[OA\Schema(
    schema: 'AutoCollectSettingsResponse',
    type: 'object',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/AutoCollectSettings'),
    ]
)]
#[OA\Schema(
    schema: 'AutoCollectSettingsUpdateRequest',
    type: 'object',
    required: ['enabled', 'interval_minutes', 'symbols'],
    properties: [
        new OA\Property(property: 'enabled', type: 'boolean', example: true),
        new OA\Property(property: 'interval_minutes', type: 'integer', minimum: 1, maximum: 59, example: 15),
        new OA\Property(
            property: 'symbols',
            type: 'array',
            minItems: 1,
            items: new OA\Items(type: 'string', example: 'BTC')
        ),
        new OA\Property(
            property: 'provider',
            type: 'string',
            enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'],
            nullable: true,
            example: null
        ),
    ]
)]
#[OA\Schema(
    schema: 'AutoCollectSettingsUpdateResponse',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Auto-collect settings saved successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/AutoCollectSettings'),
    ]
)]
#[OA\Schema(
    schema: 'AutoCollectRunRequest',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'symbols',
            type: 'array',
            nullable: true,
            items: new OA\Items(type: 'string', example: 'BTC')
        ),
        new OA\Property(
            property: 'provider',
            type: 'string',
            enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'],
            nullable: true,
            example: null
        ),
        new OA\Property(property: 'dry_run', type: 'boolean', nullable: true, example: false),
        new OA\Property(property: 'force_provider', type: 'boolean', nullable: true, example: false),
    ]
)]
#[OA\Schema(
    schema: 'AutoCollectRunData',
    type: 'object',
    required: [
        'exit_code',
        'dry_run',
        'force_provider',
        'allow_partial_success',
        'symbols',
        'requested_provider',
        'effective_provider',
        'auto_fallback_applied',
        'warnings',
        'summary',
        'output',
    ],
    properties: [
        new OA\Property(property: 'exit_code', type: 'integer', example: 0),
        new OA\Property(property: 'dry_run', type: 'boolean', example: false),
        new OA\Property(property: 'force_provider', type: 'boolean', example: false),
        new OA\Property(property: 'allow_partial_success', type: 'boolean', example: true),
        new OA\Property(property: 'symbols', type: 'array', items: new OA\Items(type: 'string', example: 'BTC')),
        new OA\Property(
            property: 'requested_provider',
            type: 'string',
            enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'],
            nullable: true,
            example: null
        ),
        new OA\Property(
            property: 'effective_provider',
            type: 'string',
            enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'],
            nullable: true,
            example: null
        ),
        new OA\Property(property: 'auto_fallback_applied', type: 'boolean', example: true),
        new OA\Property(property: 'warnings', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(
            property: 'summary',
            type: 'object',
            nullable: true,
            required: ['total', 'success', 'failed'],
            properties: [
                new OA\Property(property: 'total', type: 'integer', example: 4),
                new OA\Property(property: 'success', type: 'integer', example: 3),
                new OA\Property(property: 'failed', type: 'integer', example: 1),
            ]
        ),
        new OA\Property(property: 'output', type: 'array', items: new OA\Items(type: 'string')),
    ]
)]
#[OA\Schema(
    schema: 'AutoCollectRunResponse',
    type: 'object',
    required: ['message', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Collection command executed successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/AutoCollectRunData'),
    ]
)]
#[OA\Schema(
    schema: 'AutoCollectStatusData',
    type: 'object',
    required: [
        'run_id',
        'trigger',
        'started_at',
        'requested_provider',
        'effective_provider',
        'provider_source',
        'dry_run',
        'ignore_config_provider',
        'allow_partial_success',
        'symbols',
    ],
    properties: [
        new OA\Property(property: 'run_id', type: 'string', format: 'uuid', example: '680ce9d6-cd7f-451f-b4d8-f1276c245362'),
        new OA\Property(property: 'trigger', type: 'string', enum: ['manual', 'dashboard', 'scheduler'], example: 'dashboard'),
        new OA\Property(property: 'started_at', type: 'string', format: 'date-time', example: '2026-02-09T19:00:00+00:00'),
        new OA\Property(
            property: 'requested_provider',
            type: 'string',
            enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'],
            nullable: true,
            example: null
        ),
        new OA\Property(
            property: 'effective_provider',
            type: 'string',
            enum: ['awesome_api', 'alpha_vantage', 'yahoo_finance', 'stooq'],
            nullable: true,
            example: null
        ),
        new OA\Property(property: 'provider_source', type: 'string', enum: ['option', 'config', 'fallback'], example: 'fallback'),
        new OA\Property(property: 'dry_run', type: 'boolean', example: false),
        new OA\Property(property: 'ignore_config_provider', type: 'boolean', example: true),
        new OA\Property(property: 'allow_partial_success', type: 'boolean', example: true),
        new OA\Property(
            property: 'symbols',
            type: 'array',
            items: new OA\Items(type: 'string', example: 'BTC')
        ),
    ]
)]
#[OA\Schema(
    schema: 'AutoCollectStatusResponse',
    type: 'object',
    required: ['running', 'data'],
    properties: [
        new OA\Property(property: 'running', type: 'boolean', example: true),
        new OA\Property(property: 'data', ref: '#/components/schemas/AutoCollectStatusData', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'AutoCollectCancelRequest',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'run_id',
            type: 'string',
            nullable: true,
            description: 'Quando informado, solicita cancelamento apenas para esta execucao.',
            example: '680ce9d6-cd7f-451f-b4d8-f1276c245362'
        ),
    ]
)]
#[OA\Schema(
    schema: 'AutoCollectCancelResponse',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Cancelamento solicitado. A execução atual será interrompida na próxima verificação.'
        ),
    ]
)]
#[OA\Schema(
    schema: 'AutoCollectHistoryEntry',
    type: 'object',
    additionalProperties: true
)]
#[OA\Schema(
    schema: 'AutoCollectHistoryResponse',
    type: 'object',
    required: ['data'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/AutoCollectHistoryEntry')
        ),
    ]
)]
class Schemas {}
