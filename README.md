# Teste Desafio - API de Cotacoes Financeiras (Laravel 12)

API REST para consulta e persistencia de cotacoes de acoes, moedas e criptoativos, com:

1. fallback entre providers de mercado;
2. historico com filtros e soft delete;
3. autenticacao de API via Sanctum;
4. dashboard web para cotacoes e operacoes;
5. comandos de coleta e reconciliacao com trilha operacional.

## Desafio atendido (minimo garantido)

Checklist objetivo dos requisitos minimos do enunciado.

| Status | Requisito minimo | Evidencia (implementacao + teste) |
| :---: | --- | --- |
| `ATENDIDO` | Buscar cotacao em API externa | Endpoint: [`GET /api/quotation/{symbol}`](routes/api.php)<br>Servico: [`app/Services/Quotations/FetchLatestQuoteService.php`](app/Services/Quotations/FetchLatestQuoteService.php)<br>Teste: [`test_can_fetch_quote_from_default_provider`](tests/Feature/QuotationApiTest.php) |
| `ATENDIDO` | Retornar dados formatados pela API | Resources: [`app/Http/Resources/QuoteDataResource.php`](app/Http/Resources/QuoteDataResource.php), [`app/Http/Resources/StoredQuotationDataResource.php`](app/Http/Resources/StoredQuotationDataResource.php), [`app/Http/Resources/QuotationResource.php`](app/Http/Resources/QuotationResource.php)<br>Teste: [`tests/Feature/QuotationApiTest.php`](tests/Feature/QuotationApiTest.php) |
| `ATENDIDO` | Salvar cotacao no banco | Endpoint: [`POST /api/quotation/{symbol}`](routes/api.php)<br>Acao/infra: [`app/Actions/Quotations/StoreQuotationAction.php`](app/Actions/Quotations/StoreQuotationAction.php), [`app/Infrastructure/Quotations/QuotationPersistenceGateway.php`](app/Infrastructure/Quotations/QuotationPersistenceGateway.php)<br>Teste: [`test_can_store_quote_and_prevent_asset_duplication`](tests/Feature/QuotationApiTest.php) |
| `ATENDIDO` | Listar historico salvo | Endpoint: [`GET /api/quotations`](routes/api.php)<br>Query/filtros: [`app/Infrastructure/Quotations/QuotationQueryBuilder.php`](app/Infrastructure/Quotations/QuotationQueryBuilder.php)<br>Teste: [`test_lists_saved_quotations_with_filters_and_pagination`](tests/Feature/QuotationApiTest.php) |
| `ATENDIDO` | Nao duplicar ativo no banco | Unicidade: [`database/migrations/2026_02_07_110650_create_assets_table.php`](database/migrations/2026_02_07_110650_create_assets_table.php)<br>Persistencia: [`firstOrCreate` em `app/Infrastructure/Quotations/QuotationPersistenceGateway.php`](app/Infrastructure/Quotations/QuotationPersistenceGateway.php)<br>Teste: [`test_can_store_quote_and_prevent_asset_duplication`](tests/Feature/QuotationApiTest.php) |
| `ATENDIDO` | Validar simbolo do ativo | Form Request: [`app/Http/Requests/QuotationRequest.php`](app/Http/Requests/QuotationRequest.php)<br>Normalizacao: [`app/Http/Requests/Concerns/NormalizesRequestInput.php`](app/Http/Requests/Concerns/NormalizesRequestInput.php), [`app/Domain/MarketData/SymbolNormalizer.php`](app/Domain/MarketData/SymbolNormalizer.php)<br>Teste: [`test_returns_validation_error_for_invalid_symbol`](tests/Feature/QuotationApiTest.php) |
| `ATENDIDO` | Erro tratado para API externa indisponivel | Handler de excecoes: [`bootstrap/app.php`](bootstrap/app.php)<br>Teste: [`test_returns_provider_unavailable_error_when_external_api_is_down`](tests/Feature/QuotationApiTest.php) |
| `ATENDIDO` | Uso correto de HTTP status codes | Status cobertos: `200`, `201`, `401`, `403`, `404`, `405`, `422`, `429`, `503` em [`bootstrap/app.php`](bootstrap/app.php) e [`tests/Feature/QuotationApiTest.php`](tests/Feature/QuotationApiTest.php) |
| `ATENDIDO` | Arquitetura com controllers finos, services e Form Requests | Controller orquestrador: [`app/Http/Controllers/QuotationController.php`](app/Http/Controllers/QuotationController.php)<br>Fluxo por camadas: Actions/Services/Infrastructure<br>Cobertura fim-a-fim: [`tests/Feature/QuotationApiTest.php`](tests/Feature/QuotationApiTest.php) |

Validacao de regressao desta checklist:

1. `./vendor/bin/sail artisan test tests/Feature/QuotationApiTest.php`
2. Resultado validado: `26 passed`, `128 assertions`

## Navegacao rapida

1. Hub de documentacao: [`docs/README.md`](docs/README.md)
2. Contrato de API: [`docs/API.md`](docs/API.md)
3. Mapa de fluxo da API (Mermaid): [`docs/API_FLOW_MAP.md`](docs/API_FLOW_MAP.md)
4. Arquitetura e fluxos: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
5. Regras de camadas/dependencias: [`docs/ARCHITECTURE_GUIDELINES.md`](docs/ARCHITECTURE_GUIDELINES.md)
6. Runbook operacional: [`docs/OPERATIONS.md`](docs/OPERATIONS.md)
7. Guia de execucao e padrao de testes: [`docs/TESTING.md`](docs/TESTING.md)
8. Guia de manutencao da documentacao: [`docs/DOCUMENTATION_GUIDE.md`](docs/DOCUMENTATION_GUIDE.md)
9. Colecao Postman: [`docs/postman/financial-quotation-api.postman_collection.json`](docs/postman/financial-quotation-api.postman_collection.json)
10. KrakenD Playground (API Gateway): [`docs/KRAKEND_PLAYGROUND.md`](docs/KRAKEND_PLAYGROUND.md)
11. Plano de execucao Gateway + Auth (fim-a-fim): [`docs/GATEWAY_EXECUTION_PLAN.md`](docs/GATEWAY_EXECUTION_PLAN.md)
12. Estrategia para validar todos os servicos: [`docs/ARCHITECTURE_SERVICES_STRATEGY.md`](docs/ARCHITECTURE_SERVICES_STRATEGY.md)

## Trilhas por objetivo

1. Quero subir e validar localmente em poucos minutos:
`README.md` (este arquivo) -> [`docs/OPERATIONS.md`](docs/OPERATIONS.md) -> [`docs/API.md`](docs/API.md).
2. Quero implementar ou alterar endpoint:
[`docs/API.md`](docs/API.md) -> [`docs/API_FLOW_MAP.md`](docs/API_FLOW_MAP.md) -> [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) -> [`docs/ARCHITECTURE_GUIDELINES.md`](docs/ARCHITECTURE_GUIDELINES.md).
3. Quero alterar regras/servicos sem quebrar padrao:
[`docs/ARCHITECTURE_GUIDELINES.md`](docs/ARCHITECTURE_GUIDELINES.md) -> [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) -> [`docs/TESTING.md`](docs/TESTING.md).

## Stack

1. PHP 8.2+
2. Laravel 12
3. Laravel Sanctum
4. MySQL (Sail) e SQLite para cenarios de teste
5. Providers: `awesome_api`, `alpha_vantage`, `yahoo_finance`, `stooq`
6. Swagger/OpenAPI com `l5-swagger`

## Inicio rapido com Sail (Docker)

```bash
cd /caminho/para/o-projeto
composer install
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan l5-swagger:generate
```

URLs:

1. API: `http://localhost`
2. Swagger UI: `http://localhost/api/documentation`
3. OpenAPI JSON: `http://localhost/api/docs`
4. Dashboard de cotacoes: `http://localhost/dashboard/quotations`
5. Dashboard de operacoes: `http://localhost/dashboard/operations`

Nota: as rotas de operacoes do dashboard retornam `403` fora de `APP_ENV=local/testing`.

## KrakenD Playground (API Gateway)

O projeto agora inclui um playground de KrakenD no mesmo `compose.yaml`, com perfis opcionais:

1. `krakend`: API Gateway principal.
2. `krakend-auth`: Keycloak para JWT/roles.
3. `krakend-async`: RabbitMQ para testes async.
4. `krakend-observability`: stack base Jaeger + Prometheus + InfluxDB + Grafana + alertas Prometheus.

Subir somente o gateway:

```bash
docker compose --profile krakend up -d krakend
```

Gateway disponivel em:

1. `http://localhost:8080` (API Gateway)
2. `http://localhost:8090` (debug endpoint do KrakenD)
3. `http://localhost:9091/metrics` (exporter Prometheus do KrakenD)

Superficie recomendada no gateway:

1. Publico versionado: `/v1/public/...`
2. Privado versionado (JWT Keycloak): `/v1/private/...`
3. Compatibilidade temporaria: `/api/...`

Guia completo de rotas e configuracoes para suas APIs:
[`docs/KRAKEND_PLAYGROUND.md`](docs/KRAKEND_PLAYGROUND.md).
Regras de alerta base do gateway: `docker/prometheus/rules/krakend-alerts.yml`.

## Inicio rapido sem Docker

Com banco local configurado no `.env`:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan optimize:clear
php artisan l5-swagger:generate
php artisan serve
```

Atalho de bootstrap local:

```bash
composer setup
```

Observacao: `composer setup` nao executa seeds. Para dados de exemplo:

```bash
php artisan db:seed
```

URLs locais (servidor artisan):

1. API: `http://127.0.0.1:8000`
2. Swagger UI: `http://127.0.0.1:8000/api/documentation`
3. Dashboard: `http://127.0.0.1:8000/dashboard/quotations`

## Dados de exemplo (seed)

Quando usar `php artisan migrate --seed` ou `php artisan db:seed`, a base recebe:

1. Usuario admin: `test@example.com` / `password`
2. Ativos e cotacoes exemplo: `BTC`, `ETH`, `MSFT`, `USDBRL` (janela no mes anterior)

## Escopo funcional

1. `GET /api/quotation/{symbol}`: busca cotacao sem persistir.
2. `POST /api/quotation/{symbol}`: busca e persiste cotacao (`201` novo, `200` deduplicado).
3. `GET /api/quotations`: lista historico com filtros (`symbol`, `type`, `source`, `status`, datas, paginacao).
4. `DELETE /api/quotations/{quotation}`: soft delete unitario (admin + Sanctum).
5. `POST /api/quotations/bulk-delete`: soft delete em lote (admin + Sanctum).
6. `POST /api/auth/token`, `DELETE /api/auth/token`, `GET /api/user`: ciclo de token de API.
7. Dashboard web: `GET /dashboard/quotations`, `GET /dashboard/operations`.
8. Operacoes do dashboard: `GET/PUT /dashboard/operations/auto-collect`, `POST /dashboard/operations/auto-collect/run`, `GET /dashboard/operations/auto-collect/history` (gate `local/testing`).

Detalhamento de payloads, status e erros: [`docs/API.md`](docs/API.md).

## Arquitetura em 1 minuto

Fluxo HTTP (execucao):

`Route -> Middleware -> FormRequest -> Controller -> Action -> Service -> Domain (regras + portas) -> Infrastructure (adapters via portas) -> Action (DTO de saida) -> Resource/JSON`

Regra de dependencia (estrutura):

`Http -> Actions -> Services -> Domain`

`Infrastructure -> Domain\Contracts` (implementa portas)

Diretrizes implementadas:

1. `Controllers` ficam finos: validacao/serializacao e delegacao.
2. `Actions` representam casos de uso e orquestram `Services`.
3. `Services` aplicam regra de processo e dependem de portas do `Domain`.
4. `Domain` concentra regra de negocio pura e nao conhece framework/adapters.
5. `Infrastructure` implementa integracoes tecnicas (DB, HTTP, cache, logs, auth).
6. Erros de API padronizados com `message`, `error_code`, `request_id` e `details` (`APP_DEBUG=true`).
7. `X-Request-Id` propagado em request, logs e response.

Arquitetura detalhada: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

## Operacao diaria

Coleta/reconciliacao:

```bash
php artisan quotations:collect --symbol=BTC --symbol=ETH
php artisan quotations:collect --symbol=BTC --dry-run
php artisan quotations:collect --symbol=BTC --provider=awesome_api --allow-partial-success
php artisan quotations:reconcile --dry-run
php artisan quotations:reconcile
```

Qualidade e CI local:

```bash
php artisan test
./vendor/bin/pint --test
composer run test:architecture
composer run test:ci
```

Swagger/OpenAPI:

```bash
composer docs:generate
```

Runbook completo: [`docs/OPERATIONS.md`](docs/OPERATIONS.md).

## Variaveis de ambiente chave

1. `APP_ENV`: controla gates de ambiente (ex.: dashboard de operacoes em `local/testing`).
2. `APP_URL`: base URL da aplicacao (links/Swagger).
3. `FRONTEND_URL`: URL usada em redirecionamento de verificacao de e-mail e CORS.
4. `QUOTATIONS_REQUIRE_AUTH`: exige token nas rotas de cotacao quando `true`.
5. `GATEWAY_ENFORCE_SOURCE`: quando `true`, bloqueia chamadas diretas da API sem segredo interno do gateway.
6. `GATEWAY_SHARED_SECRET` e `GATEWAY_SHARED_SECRET_HEADER`: contrato interno de origem confiavel entre KrakenD e Laravel.
7. `GATEWAY_TRUST_JWT_ASSERTION`, `GATEWAY_JWT_ASSERTION_HEADER` e `GATEWAY_JWT_ASSERTION_VALUE`: habilitam autenticacao delegada de JWT validado no gateway.
8. `GATEWAY_JWT_ROLES_HEADER` e `GATEWAY_JWT_MODERATOR_ROLE`: autorizacao de operacoes administrativas por role propagada do JWT.
9. `QUOTATIONS_RATE_LIMIT`: throttle das rotas de cotacao.
10. `QUOTATIONS_CACHE_TTL`: TTL do cache de cotacoes externas.
11. `QUOTATIONS_AUTO_COLLECT_ENABLED`: habilita registro da coleta no scheduler.
12. `QUOTATIONS_AUTO_COLLECT_INTERVAL_MINUTES`: intervalo da coleta automatica (`1..59`).
13. `QUOTATIONS_AUTO_COLLECT_SYMBOLS`: simbolos default da auto-coleta.
14. `QUOTATIONS_AUTO_COLLECT_PROVIDER`: provider fixo opcional para auto-coleta.
15. `QUOTATIONS_AUTO_COLLECT_HISTORY_PATH` e `QUOTATIONS_AUTO_COLLECT_HISTORY_FALLBACK_PATH`: caminhos do historico operacional (JSONL).
16. `MARKET_DATA_PROVIDER`: provider default quando nao informado explicitamente.
17. `ALPHA_VANTAGE_KEY`: obrigatoria para consultas via Alpha Vantage.
18. `ACTIVITY_LOGGER_ENABLED`: ativa/desativa auditoria (`activity_log`).
19. `SESSION_DRIVER=database`: exige migrations para tabela `sessions`.

Matriz completa e recomendacoes operacionais: [`docs/OPERATIONS.md`](docs/OPERATIONS.md).

## Troubleshooting rapido

1. Erro `sessions` table does not exist: rode `php artisan migrate`.
2. Erro de conexao com `mysql`: use Sail (`./vendor/bin/sail artisan ...`) ou ajuste `DB_HOST` para seu ambiente local.
3. Falha no provider `alpha_vantage`: configure `ALPHA_VANTAGE_KEY`.
4. `401` nas rotas de cotacao: valide `QUOTATIONS_REQUIRE_AUTH` e envie `Authorization: Bearer {token}`.
5. Coleta automatica nao roda: confirme `QUOTATIONS_AUTO_COLLECT_ENABLED=true` e processo de scheduler ativo.
6. `403` em `/dashboard/operations`: confira `APP_ENV` e use `local` ou `testing`.

## Manutencao da documentacao

Sempre que alterar API, arquitetura, operacao ou testes, revise os documentos correspondentes usando a matriz em [`docs/DOCUMENTATION_GUIDE.md`](docs/DOCUMENTATION_GUIDE.md).
