# Mapa de Fluxo da API

Objetivo: documentar o fluxo de execucao de todas as rotas de `routes/api.php` com classes reais por camada.
Hub principal: [`README.md`](README.md).

## Como ler esta pagina

1. Use a secao "Visao executiva" para entender o desenho geral.
2. Use a secao "Fluxos detalhados" para abrir apenas o endpoint que voce quer estudar.
3. Em cada endpoint, leia na ordem: middleware -> controller -> action -> service -> ports -> adapters -> resposta.

## Cobertura

1. Escopo: somente rotas de `routes/api.php`.
2. Total de endpoints mapeados: `8`.
3. Modelo de camadas: `Http -> Actions -> Services -> Domain (ports) -> Infrastructure (adapters)`.

## Inventario de endpoints

| Grupo | Endpoint | Controller | Action principal | Service principal |
| --- | --- | --- | --- | --- |
| Auth | `POST /api/auth/token` | `AuthTokenController@store` | `IssueAuthTokenAction` | `AuthTokenService` |
| Auth | `DELETE /api/auth/token` | `AuthTokenController@destroy` | `RevokeAuthTokenAction` | `AuthTokenService` |
| Auth | `GET /api/user` | `AuthenticatedUserController@show` | `GetAuthenticatedUserProfileAction` | `AuthenticatedUserProfileService` |
| Quotations | `GET /api/quotation/{symbol}` | `QuotationController@show` | `ShowQuotationAction` | `FetchLatestQuoteService` |
| Quotations | `POST /api/quotation/{symbol}` | `QuotationController@store` | `StoreQuotationAction` | `PersistQuotationService` |
| Quotations | `GET /api/quotations` | `QuotationController@index` | `IndexQuotationHistoryAction` | `ListQuotationsService` |
| Quotations | `POST /api/quotations/bulk-delete` | `QuotationController@destroyBatch` | `DeleteQuotationBatchAction` | `QuotationDeletionService` |
| Quotations | `DELETE /api/quotations/{quotation}` | `QuotationController@destroy` | `DeleteSingleQuotationAction` | `QuotationDeletionService` |

## Visao executiva

### Camadas

```mermaid
flowchart TB
    R["routes/api.php"] --> M["Middlewares"]
    M --> H["Http Controllers"]
    H --> A["Actions"]
    A --> S["Services"]
    S --> D["Domain: regras + ports"]
    D --> I["Infrastructure: adapters"]
    I --> O["Resource / JsonResponse"]
```

### Macrofluxo por dominio

```mermaid
flowchart TB
    subgraph AUTH["Auth API"]
        A1["POST /auth/token"]
        A2["DELETE /auth/token"]
        A3["GET /user"]
    end

    subgraph Q["Quotations API"]
        Q1["GET /quotation/{symbol}"]
        Q2["POST /quotation/{symbol}"]
        Q3["GET /quotations"]
        Q4["POST /quotations/bulk-delete"]
        Q5["DELETE /quotations/{quotation}"]
    end

    AUTH --> C1["Api controllers"]
    Q --> C2["QuotationController"]
    C1 --> ACT["Actions"]
    C2 --> ACT
    ACT --> SRV["Services"]
    SRV --> PORTS["Domain ports"]
    PORTS --> ADP["Infrastructure adapters"]
    ADP --> RESP["JSON responses"]
```

## Fluxos detalhados (expandir)

<details>
<summary><code>POST /api/auth/token</code></summary>

Middlewares:
1. `throttle:10,1`

```mermaid
flowchart TB
    R["POST /api/auth/token"] --> MW["throttle:10,1"]
    MW --> REQ["AuthTokenStoreRequest"]
    REQ --> CTRL["AuthTokenController::store"]
    CTRL --> ACT["IssueAuthTokenAction"]
    ACT --> SVC["AuthTokenService::issue"]
    SVC --> PORTS["UserRepositoryPort + PasswordHasherPort"]
    PORTS --> ADP["UserRepository + PasswordHasher"]
    ACT --> AUDIT["AuthTokenService::logIssuedToken"]
    AUDIT --> APORT["AuditLoggerPort"]
    APORT --> AADP["AuditLogger"]
    AADP --> OUT["JSON 201"]
```

</details>

<details>
<summary><code>DELETE /api/auth/token</code></summary>

Middlewares:
1. `auth:sanctum`

```mermaid
flowchart TB
    R["DELETE /api/auth/token"] --> MW["auth:sanctum"]
    MW --> CTRL["AuthTokenController::destroy"]
    CTRL --> ACT["RevokeAuthTokenAction"]
    ACT --> SVC["AuthTokenService::revokeCurrentToken"]
    SVC --> PORT["UserRepositoryPort"]
    PORT --> ADP["UserRepository"]
    ACT --> AUDIT["AuthTokenService::logRevokedToken"]
    AUDIT --> APORT["AuditLoggerPort"]
    APORT --> AADP["AuditLogger"]
    AADP --> OUT["JSON 200"]
```

</details>

<details>
<summary><code>GET /api/user</code></summary>

Middlewares:
1. `auth:sanctum`

```mermaid
flowchart TB
    R["GET /api/user"] --> MW["auth:sanctum"]
    MW --> CTRL["AuthenticatedUserController::show"]
    CTRL --> ACT["GetAuthenticatedUserProfileAction"]
    ACT --> DTO["AuthenticatedUserProfileInput"]
    DTO --> SVC["AuthenticatedUserProfileService::build"]
    SVC --> OUT["JSON data"]
```

</details>

<details>
<summary><code>GET /api/quotation/{symbol}</code></summary>

Middlewares:
1. `quotation.auth`
2. `throttle:config(quotations.rate_limit)`

```mermaid
flowchart TB
    R["GET /api/quotation/{symbol}"] --> MW1["quotation.auth"]
    MW1 --> MW2["throttle"]
    MW2 --> REQ["QuotationRequest"]
    REQ --> CTRL["QuotationController::show"]
    CTRL --> ACT["ShowQuotationAction"]
    ACT --> SVC["FetchLatestQuoteService::handle"]
    SVC --> DOM["SymbolNormalizer + AssetTypeResolver"]
    SVC --> PORTS["MarketDataProviderManagerPort + QuoteCachePort + QuotationsConfigPort"]
    PORTS --> ADP["MarketDataProviderManager + QuoteCache + QuotationsConfig"]
    ADP --> EXT["Providers externos"]
    EXT --> RES["QuoteDataResource"]
    RES --> OUT["JSON 200"]
```

</details>

<details>
<summary><code>POST /api/quotation/{symbol}</code></summary>

Middlewares:
1. `quotation.auth`
2. `throttle:config(quotations.rate_limit)`

```mermaid
flowchart TB
    R["POST /api/quotation/{symbol}"] --> MW1["quotation.auth"]
    MW1 --> MW2["throttle"]
    MW2 --> REQ["QuotationRequest"]
    REQ --> CTRL["QuotationController::store"]
    CTRL --> ACT["StoreQuotationAction"]
    ACT --> FQ["FetchLatestQuoteService::handle"]
    ACT --> PS["PersistQuotationService::handle"]
    PS --> PORT["QuotationPersistencePort"]
    PORT --> ADP["QuotationPersistenceGateway"]
    ADP --> RULES["transaction + dedupe + quality"]
    RULES --> RES["StoredQuotationDataResource"]
    RES --> OUT["JSON 201/200"]
```

</details>

<details>
<summary><code>GET /api/quotations</code></summary>

Middlewares:
1. `quotation.auth`
2. `throttle:config(quotations.rate_limit)`

```mermaid
flowchart TB
    R["GET /api/quotations"] --> MW1["quotation.auth"]
    MW1 --> MW2["throttle"]
    MW2 --> REQ["QuotationIndexRequest"]
    REQ --> CTRL["QuotationController::index"]
    CTRL --> ACT["IndexQuotationHistoryAction"]
    ACT --> SVC["ListQuotationsService::handle"]
    SVC --> BS["BuildQuotationQueryService::paginate"]
    BS --> PORT["QuotationQueryBuilderPort"]
    PORT --> ADP["QuotationQueryBuilder"]
    ADP --> RES["QuotationResource::collection"]
    RES --> OUT["JSON 200 paginado"]
```

</details>

<details>
<summary><code>POST /api/quotations/bulk-delete</code></summary>

Middlewares:
1. `quotation.auth`
2. `throttle:config(quotations.rate_limit)`
3. `auth:sanctum`

```mermaid
flowchart TB
    R["POST /api/quotations/bulk-delete"] --> MW1["quotation.auth"]
    MW1 --> MW2["throttle"]
    MW2 --> MW3["auth:sanctum"]
    MW3 --> REQ["DeleteQuotationBatchRequest"]
    REQ --> CTRL["QuotationController::destroyBatch"]
    CTRL --> ACT["DeleteQuotationBatchAction"]
    ACT --> SVC["QuotationDeletionService::deleteBatch"]
    SVC --> DS["DeleteQuotationsService::handle"]
    DS --> BS["BuildQuotationQueryService::delete"]
    BS --> PORT["QuotationQueryBuilderPort"]
    PORT --> ADP["QuotationQueryBuilder"]
    SVC --> LOGPORT["AuditLoggerPort + ApplicationLoggerPort"]
    LOGPORT --> LOGADP["AuditLogger + ApplicationLogger"]
    ADP --> OUT["JSON 200"]
```

</details>

<details>
<summary><code>DELETE /api/quotations/{quotation}</code></summary>

Middlewares:
1. `quotation.auth`
2. `throttle:config(quotations.rate_limit)`
3. `auth:sanctum`
4. `whereNumber(quotation)`

```mermaid
flowchart TB
    R["DELETE /api/quotations/{quotation}"] --> MW1["quotation.auth"]
    MW1 --> MW2["throttle"]
    MW2 --> MW3["auth:sanctum"]
    MW3 --> MW4["whereNumber"]
    MW4 --> CTRL["QuotationController::destroy"]
    CTRL --> ACT["DeleteSingleQuotationAction"]
    ACT --> SVC["QuotationDeletionService::deleteSingle"]
    SVC --> PORT["QuotationDeletionRepositoryPort"]
    PORT --> ADP["QuotationDeletionRepository"]
    SVC --> LOGPORT["AuditLoggerPort + ApplicationLoggerPort"]
    LOGPORT --> LOGADP["AuditLogger + ApplicationLogger"]
    ADP --> OUT["JSON 200"]
```

</details>

## Regras transversais

1. Middleware global de correlacao: `AssignRequestId` em `bootstrap/app.php` e `app/Http/Middleware/AssignRequestId.php`.
2. Alias `quotation.auth` em `bootstrap/app.php`, implementado por `app/Http/Middleware/EnsureQuotationApiAuthentication.php`.
3. Chave de auth condicional para cotacoes: `config/quotations.php` (`QUOTATIONS_REQUIRE_AUTH`).
4. Tratamento padronizado de erros da API: `bootstrap/app.php` (`message`, `error_code`, `request_id`).

## Ports e adapters usados por estas rotas

| Port (Domain) | Adapter (Infrastructure) | Uso principal |
| --- | --- | --- |
| `UserRepositoryPort` | `UserRepository` | emissao/revogacao de token |
| `PasswordHasherPort` | `PasswordHasher` | validacao de credenciais |
| `AuditLoggerPort` | `AuditLogger` | trilha de auditoria |
| `ApplicationLoggerPort` | `ApplicationLogger` | logs de aplicacao |
| `MarketDataProviderManagerPort` | `MarketDataProviderManager` | ordem de fallback de provider |
| `QuoteCachePort` | `QuoteCache` | cache de cotacoes |
| `QuotationsConfigPort` | `QuotationsConfig` | configuracoes de cotacoes |
| `QuotationPersistencePort` | `QuotationPersistenceGateway` | persistencia de cotacoes |
| `QuotationQueryBuilderPort` | `QuotationQueryBuilder` | listagem e delete em lote |
| `QuotationDeletionRepositoryPort` | `QuotationDeletionRepository` | delete unitario |

Bindings centralizados em `app/Providers/AppServiceProvider.php`.

## Relacao com outros documentos

1. Contrato HTTP (payloads e status): [`API.md`](API.md)
2. Arquitetura executavel completa: [`ARCHITECTURE.md`](ARCHITECTURE.md)
3. Regras de dependencia por camada: [`ARCHITECTURE_GUIDELINES.md`](ARCHITECTURE_GUIDELINES.md)
