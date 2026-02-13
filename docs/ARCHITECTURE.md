# Arquitetura Interna e Fluxos

Este documento descreve a arquitetura executavel da base atual, com foco nos fluxos reais do codigo.
Indice de navegacao da documentacao: [`README.md`](README.md).
Regras de classificacao e dependencia: [`ARCHITECTURE_GUIDELINES.md`](ARCHITECTURE_GUIDELINES.md).
Guia de manutencao de docs: [`DOCUMENTATION_GUIDE.md`](DOCUMENTATION_GUIDE.md).

## Estilo arquitetural adotado

A aplicacao segue uma arquitetura em camadas orientada a casos de uso, inspirada em Clean Architecture, com portas e adaptadores (Hexagonal) aplicados de forma pragmatica:

1. Entrada por adaptadores (`Http`, `Console`, scheduler).
2. Casos de uso em `Actions`.
3. Orquestracao em `Services`.
4. Regras e contratos em `Domain`.
5. Implementacoes tecnicas em `Infrastructure`.

Objetivo pratico: manter regra de negocio e fluxo de aplicacao estaveis, trocando detalhes tecnicos (provider externo, persistencia, cache, autenticao, etc.) via portas (`Domain\Contracts`) e adaptadores (`Infrastructure`).

## Superficie ativa

1. API de cotacoes e historico (`/api/quotation`, `/api/quotations`).
2. Auth API com Sanctum (`/api/auth/token`, `/api/user`).
3. Exclusao de cotacoes (unitaria e em lote) com auditoria.
4. Dashboard web de cotacoes e painel de operacoes (`/dashboard/quotations`, `/dashboard/operations`).
5. Auth web (`/register`, `/login`, `/forgot-password`, `/reset-password`, verificacao de email e logout).
6. Console/scheduler (`quotations:collect`, `quotations:reconcile`, `schedule`).
7. Sem superficie publica de alertas/carteiras (ver `tests/Feature/SimplifiedSurfaceTest.php`).

## Camadas e regra de dependencia

### 1) Borda de entrada (`Http`, `Console`, scheduler)

Responsabilidades:
1. Parse de input (request/argument/options).
2. Validacao/normalizacao (`FormRequest`).
3. Delegacao para `Action`.
4. Serializacao de resposta (`Resource`, `JsonResponse`, output CLI).

Exemplos:
1. `app/Http/Controllers/QuotationController.php`
2. `app/Http/Controllers/DashboardOperationsController.php`
3. `app/Console/Commands/CollectQuotationsCommand.php`
4. `routes/api.php`, `routes/web.php`, `routes/auth.php`, `routes/console.php`

### 2) Casos de uso (`Actions`)

Responsabilidades:
1. Coordenar um caso de uso especifico.
2. Chamar `Services`.
3. Mapear payload para DTOs de `app/Data`.

Regras:
1. `Action` nao depende diretamente de `Domain` (guardrail automatizado).
2. `Action` nao depende de `Illuminate`/`Laravel`.
3. Cada action expoe apenas `__invoke` publico.

### 3) Aplicacao (`Services`)

Responsabilidades:
1. Orquestrar fluxo reutilizavel entre actions.
2. Aplicar regras de processo (fallback, autorizacao de operacao, consolidacao de resultado, etc.).
3. Usar portas do dominio para I/O externo.

Regra real validada por teste:
1. Imports internos permitidos em `Services`: apenas `App\Domain\...` e `App\Services\...`.
2. `Services` nao importam `App\Infrastructure`, `App\Http`, `App\Models`, `App\Data`, `App\Actions`.

### 4) Dominio (`Domain`)

Responsabilidades:
1. Regras de negocio puras (`AssetTypeResolver`, `SymbolNormalizer`, `QuotationQualityService`).
2. Value objects e excecoes de dominio.
3. Definicao de portas (`Domain\Contracts\*Port`).

Regra:
1. Dominio nao depende de `Actions`, `Services`, `Infrastructure`, `Http`, `Models`, `Data`.

### 5) Infraestrutura (`Infrastructure`)

Responsabilidades:
1. Implementar portas do dominio.
2. Integrar framework, Eloquent, cache, HTTP clients, arquivo, logs, comandos Artisan.

Exemplos:
1. Market data providers (`AwesomeApiProvider`, `AlphaVantageProvider`, `YahooFinanceProvider`, `StooqProvider`).
2. Persistencia (`QuotationPersistenceGateway`, `QuotationQueryBuilder`, `QuotationDeletionRepository`, `QuotationReconciliationRepository`).
3. Config/ambiente (`EnvFileEditor`, `ConfigCacheManager`, `ApplicationEnvironment`, `QuotationsConfig`).
4. Auth adapters (`UserRepository`, `WebSessionAuthenticator`, `PasswordResetBroker`, etc.).
5. Observabilidade (`QuotationCollectExecutionLogger`, `ApplicationLogger`, `AuditLogger`).

## Fluxo macro

### HTTP/API

`Client -> Route -> Middleware -> FormRequest -> Controller -> Action -> Service -> Domain (regras + portas) -> Infrastructure (adapters) -> Action (DTO) -> Resource/JSON`

### Console

`Scheduler/CLI -> Command -> Action -> Service -> Domain (regras + portas) -> Infrastructure (adapters) -> output/log/history/exit code`

## Mapa de portas e adaptadores

| Porta (`Domain\Contracts`) | Adapter principal (`Infrastructure`) | Uso principal |
| --- | --- | --- |
| `MarketDataProviderManagerPort` | `MarketDataProviderManager` | Resolver provider e ordem de fallback |
| `QuoteCachePort` | `QuoteCache` | Cache de quote por provider/tipo/simbolo |
| `QuotationsConfigPort` | `QuotationsConfig` | Config de cache/auto-collect/providers |
| `QuotationPersistencePort` | `QuotationPersistenceGateway` | Persistencia atomica com deduplicacao/qualidade |
| `QuotationQueryBuilderPort` | `QuotationQueryBuilder` | Paginacao e filtros de historico |
| `QuotationDeletionRepositoryPort` | `QuotationDeletionRepository` | Soft delete unitario |
| `QuotationReconciliationRepositoryPort` | `QuotationReconciliationRepository` | Reconciliacao de historico |
| `QuotationCollectExecutionLoggerPort` | `QuotationCollectExecutionLogger` | Eventos de coleta + historico JSONL |
| `QuotationCollectCommandRunnerPort` | `QuotationCollectCommandRunner` | Execucao programatica de `quotations:collect` |
| `UserRepositoryPort` | `UserRepository` | Auth API/web e tokens |
| `PasswordHasherPort` | `PasswordHasher` | Hash/check de senha |
| `WebSessionAuthenticatorPort` | `WebSessionAuthenticator` | Login/logout web |
| `WebSessionStatePort` | `WebSessionState` | Regeneracao/invalidate de sessao |
| `LoginRateLimiterPort` | `LoginRateLimiter` | Throttle de login web |
| `PasswordResetBrokerPort` | `PasswordResetBroker` | Fluxo de reset |
| `AuthLifecycleEventsPort` | `AuthLifecycleEvents` | Eventos registered/verified/password reset |
| `AuditLoggerPort` | `AuditLogger` | Trilha de auditoria |
| `ApplicationLoggerPort` | `ApplicationLogger` | Logging estruturado de aplicacao |
| `EnvFileEditorPort` | `EnvFileEditor` | Escrita de `.env` |
| `ConfigCachePort` | `ConfigCacheManager` | `config:clear` |
| `ApplicationEnvironmentPort` | `ApplicationEnvironment` | Gate de ambiente (local/testing) |
| `RememberTokenGeneratorPort` | `RememberTokenGenerator` | Gera remember token no fluxo de autenticacao |

Bindings centralizados em `app/Providers/AppServiceProvider.php`.

## Fluxos ponta a ponta

### 1) `GET /api/quotation/{symbol}` (consulta sem persistir)

1. `QuotationRequest` normaliza `symbol` da rota e valida `provider/type`.
2. `QuotationController@show` delega para `ShowQuotationAction`.
3. `ShowQuotationAction` chama `FetchLatestQuoteService`.
4. `FetchLatestQuoteService`:
   1. normaliza simbolo (`SymbolNormalizer`);
   2. resolve tipo (`AssetTypeResolver` quando `type` nao informado);
   3. resolve ordem de providers via `MarketDataProviderManagerPort`;
   4. consulta cache via `QuoteCachePort`;
   5. aplica fallback por provider quando nao ha provider explicito;
   6. aplica fail-fast quando provider explicito foi informado.
5. `QuoteDataResource` serializa retorno `200`.

### 2) `POST /api/quotation/{symbol}` (consulta + persistencia)

1. `QuotationRequest` valida input.
2. `StoreQuotationAction` chama `FetchLatestQuoteService`.
3. `PersistQuotationService` delega para `QuotationPersistencePort`.
4. `QuotationPersistenceGateway` (`Infrastructure`):
   1. abre transacao (`DB::transaction`);
   2. cria/atualiza `Asset`;
   3. serializa concorrencia por ativo (`lockForUpdate`);
   4. deduplica por `asset_id + source + quoted_at + price + currency`;
   5. classifica qualidade (`QuotationQualityService`) e persiste status;
   6. reconcilia janela recente para outlier/non-positive.
5. `StoreQuotationAction` monta `StoredQuotationData`.
6. `StoredQuotationDataResource` responde `201` (novo) ou `200` (deduplicado).

### 3) `GET /api/quotations` (historico paginado)

1. `QuotationIndexRequest` valida filtros.
2. `IndexQuotationHistoryAction -> ListQuotationsService -> BuildQuotationQueryService`.
3. `BuildQuotationQueryService` usa `QuotationQueryBuilderPort`.
4. Default de filtro:
   1. `status` informado: usa status informado;
   2. sem `status` e sem `include_invalid=true`: retorna apenas `valid`.
5. Ordenacao por `quoted_at desc`, depois `id desc`.

### 4) Exclusao unitario/lote

1. Rotas exigem `quotation.admin` (admin Sanctum ou role `moderator` confiada pelo gateway).
2. `DeleteSingleQuotationAction` e `DeleteQuotationBatchAction` delegam para `QuotationDeletionService`.
3. `QuotationDeletionService`:
   1. bloqueia quando `canDelete=false` (admin gate);
   2. executa soft delete via portas de repositorio/query;
   3. registra log de aplicacao;
   4. registra auditoria (`quotation.deleted`, `quotation.batch_deleted`).
4. `DeleteQuotationBatchRequest` exige `confirm=true` e impede delete total sem `delete_all=true`.

### 5) Auth API (`/api/auth/token`, `/api/user`)

1. `AuthTokenController@store` -> `IssueAuthTokenAction` -> `AuthTokenService`.
2. `AuthTokenService` valida credenciais via `UserRepositoryPort` + `PasswordHasherPort`.
3. Emite token Sanctum via adapter e registra auditoria (`token.created`).
4. `AuthTokenController@destroy` -> `RevokeAuthTokenAction` -> `AuthTokenService`.
5. Revogacao registra auditoria (`token.revoked`).
6. `/api/user` retorna perfil autenticado via `GetAuthenticatedUserProfileAction`.

### 6) Auth web (`routes/auth.php`)

1. Login: `AuthenticatedSessionController@store` -> `AuthenticateSessionAction` -> `WebSessionService`.
2. `WebSessionService` aplica throttling via `LoginRateLimiterPort`, autentica por `WebSessionAuthenticatorPort` e regenera sessao via `WebSessionStatePort`.
3. Registro: `RegisteredUserController@store` -> `RegisterUserAction` -> `UserRegistrationService`.
4. `UserRegistrationService` cria usuario, dispara evento de ciclo de auth e autentica sessao web.
5. Reset de senha: `SendPasswordResetLinkAction` / `ResetPasswordAction` -> `PasswordResetService`.
6. Verificacao de email: `VerifyEmailAddressAction` / `SendEmailVerificationNotificationAction` -> `EmailVerificationService`.

### 7) Dashboard de operacoes (`/dashboard/operations`)

1. `DashboardOperationsController` expoe pagina + endpoints JSON de auto-collect.
2. Todas as actions operacionais chamam `DashboardOperationsAuthorizationService::ensureLocalOrTesting()`.
3. `UpdateAutoCollectSettingsRequest` valida/normaliza `enabled`, `interval_minutes`, `symbols`, `provider`.
4. `RunAutoCollectRequest` valida/normaliza `symbols`, `provider`, `dry_run`, `force_provider`.
5. `CancelAutoCollectRequest` valida `run_id` opcional para cancelamento cooperativo.
6. `DashboardAutoCollectService`:
   1. le config efetiva via `QuotationsConfigPort`;
   2. persiste alteracoes no `.env` via `EnvFileEditorPort`;
   3. limpa config cache via `ConfigCachePort` fora de ambiente de teste;
   4. dispara `quotations:collect` via `QuotationCollectCommandRunnerPort`;
   5. aplica fallback automatico para tipos mistos quando `provider` fixo foi enviado e `force_provider=false`;
   6. lista historico via `QuotationCollectExecutionLoggerPort`.
   7. permite resetar marco de saude do painel sem apagar historico bruto.
7. `ShowAutoCollectStatusAction` retorna execucao em andamento via `AutoCollectRunStateService`.
8. `CancelDashboardAutoCollectAction` solicita cancelamento via `AutoCollectCancellationService`.
9. `ResetAutoCollectHealthAction` redefine baseline do painel para exibicao de saude.

### 8) Comando `quotations:collect`

1. Entrada: opcoes `--symbol=*`, `--provider=`, `--dry-run`, `--ignore-config-provider`, `--allow-partial-success`, `--trigger=`.
2. Determina provider efetivo (`option` > `config` > fallback).
3. Registra inicio via `RecordQuotationCollectionStartedAction` (inclui estado em andamento para o dashboard).
4. Coleta por simbolo via `CollectConfiguredQuotationsAction`.
5. Antes de cada simbolo verifica cancelamento cooperativo via `AutoCollectCancellationService`.
6. Em dry-run consulta providers sem persistir.
7. Registra fim via `RecordQuotationCollectionFinishedAction` com resumo e `exit_code`.
8. Em cancelamento, finaliza com status `canceled` e codigo de falha.
9. `--allow-partial-success` retorna sucesso quando houver ao menos um simbolo processado com sucesso.

### 9) Comando `quotations:reconcile`

1. Entrada: `--symbol=*`, `--dry-run`.
2. `ReconcileQuotationHistoryAction` delega para `ReconcileQuotationHistoryService`.
3. O service invalida duplicatas, outliers e precos nao positivos (ou apenas simula no dry-run).
4. Comando sempre conclui com resumo de contagens no terminal.

## Scheduler e automacao

1. Scheduler registra `quotations:collect` apenas quando `QUOTATIONS_AUTO_COLLECT_ENABLED=true`.
2. Intervalo e limitado para `1..59` minutos.
3. Trigger do scheduler envia `--trigger=scheduler`.
4. `withoutOverlapping()` evita execucoes concorrentes sobrepostas.

## Observabilidade, erro e auditoria

### `X-Request-Id`

1. `AssignRequestId` gera/propaga `X-Request-Id`.
2. Valor aparece em responses e payload de erro API.

### Protecao de perimetro (Gateway)

1. `EnsureRequestFromGateway` (`gateway.only`) valida `X-Gateway-Secret` quando `GATEWAY_ENFORCE_SOURCE=true`.
2. `EnsureQuotationApiAuthentication` aceita Sanctum ou JWT ja validado no gateway (`X-Gateway-Auth: jwt`) quando configurado.
3. `EnsureQuotationAdminAuthorization` exige admin Sanctum ou role `moderator` propagada pelo gateway para operacoes de delete.

### Erro padronizado em API

1. Centralizado em `bootstrap/app.php`.
2. Mapeia excecoes de dominio/infra para `message`, `error_code`, `request_id`.
3. `details` so e exposto quando `APP_DEBUG=true`.

### Auditoria

1. Operacoes sensiveis (token e exclusao) geram eventos em `activity_log`.
2. Falha de escrita de auditoria nao interrompe fluxo de negocio (best-effort).

### Historico operacional de coleta

1. `QuotationCollectExecutionLogger` grava evento de inicio/fim em canal dedicado.
2. Finalizacao faz append JSONL em caminho principal e fallback.
3. Dashboard le ambos os caminhos e ordena por timestamp.

### Execucao corrente e cancelamento cooperativo

1. `AutoCollectRunStateService` mantem contexto volatil da execucao em andamento.
2. `AutoCollectCancellationService` recebe solicitacoes de cancelamento e expoe flag por `run_id`.
3. Fluxo de coleta consulta flag entre simbolos para interromper sem perder consistencia.

## Modelo de dados e indices relevantes

1. `quotations`: `status`, `invalid_reason`, `invalidated_at`, `deleted_at`.
2. Indices importantes:
   1. `quotations_status_quoted_at_idx`
   2. `quotations_asset_source_quoted_at_idx`
   3. `quotations_dedupe_lookup_idx`
3. `activity_log` com indices por `event`, `created_at`, `causer` e `subject`.

## Guardrails automatizados

1. `tests/Unit/Architecture/DomainLayerDependencyTest.php`
   1. garante isolamento do dominio.
2. `tests/Unit/Architecture/ServiceLayerDependencyTest.php`
   1. garante imports de `Services` restritos a `Domain` e `Services`.
3. `tests/Unit/Architecture/ActionLayerDependencyTest.php`
   1. bloqueia dependencia direta de `Actions` para `Domain`;
   2. bloqueia dependencia de framework em `Actions`.
4. `tests/Unit/Architecture/ActionModelDependencyTest.php`
   1. bloqueia import de `Models` em `Actions`.
5. `tests/Unit/Architecture/ControllerLayerDependencyTest.php`
   1. garante controllers dependentes de actions e nao de services/models.
6. `tests/Unit/Architecture/ActionConventionTest.php`
   1. padrao de naming `*Action`;
   2. apenas `__invoke` publico.

Validacao recomendada antes de merge:

```bash
composer run test:architecture
```

## Checklist rapido para evolucao arquitetural

1. Mudou rota/command? Atualizar fluxo correspondente neste documento.
2. Criou integracao tecnica nova? Criar porta em `Domain\Contracts` e adapter em `Infrastructure`.
3. Criou caso de uso? Adicionar `Action` + `Service` e manter controller/command fino.
4. Mudou regra de dependencia? Ajustar testes de arquitetura junto da implementacao.
5. Validar `composer run test:architecture` antes do PR.
