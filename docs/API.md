# Contrato da API de Cotacoes (Escopo Simplificado)

Base URL (local): `http://localhost` (ajuste via `APP_URL`/`APP_PORT`).
Indice de navegacao da documentacao: [`README.md`](README.md).
Guia de manutencao de docs: [`DOCUMENTATION_GUIDE.md`](DOCUMENTATION_GUIDE.md).

## Escopo desta branch

1. Sem endpoints de alertas.
2. Sem endpoints de carteiras.
3. Dashboards web focados em cotacoes e operacoes.
4. URLs: `/dashboard` (alias), `/dashboard/quotations` e `/dashboard/operations`.
5. Providers ativos: `awesome_api`, `alpha_vantage`, `yahoo_finance`, `stooq`.

## Autenticacao

Token Sanctum:

1. `POST /api/auth/token`
2. `DELETE /api/auth/token`
3. `GET /api/user`

As rotas de cotacao respeitam `QUOTATIONS_REQUIRE_AUTH`.
Quando ativo, o cliente deve enviar `Authorization: Bearer {token}`.

Payload de emissao (`POST /api/auth/token`):

```json
{
  "email": "test@example.com",
  "password": "password",
  "device_name": "cli"
}
```

Resposta `201`:

```json
{
  "message": "Token created successfully.",
  "data": {
    "token": "token",
    "token_type": "Bearer",
    "device_name": "cli"
  }
}
```

Resposta de revogacao (`DELETE /api/auth/token`):

```json
{
  "message": "Token revoked successfully."
}
```

Resposta de perfil (`GET /api/user`):

```json
{
  "data": {
    "id": 1,
    "name": "Test User",
    "email": "test@example.com",
    "is_admin": true,
    "permissions": {
      "delete_quotations": true
    }
  }
}
```

Autenticacao web (sessao):

1. `POST /register`
2. `POST /login`
3. `POST /forgot-password`
4. `POST /reset-password`
5. `GET /verify-email/{id}/{hash}`
6. `POST /email/verification-notification`
7. `POST /logout`

## Headers de observabilidade

1. O cliente pode enviar `X-Request-Id`.
2. A API devolve `X-Request-Id` em todas as respostas.
3. Em erros padronizados, o mesmo valor aparece em `request_id`.

## Headers internos do gateway

1. `X-Gateway-Secret` e restrito ao trafego interno KrakenD -> Laravel.
2. Com `GATEWAY_ENFORCE_SOURCE=true`, requests diretos sem segredo valido retornam `403`.
3. Para rotas privadas JWT, o gateway propaga `X-Gateway-Auth`, `X-Auth-Roles` e `X-Auth-Subject`.

## Endpoints de cotacao

## `GET /api/quotation/{symbol}`

Busca cotacao atual sem persistir.

Query params opcionais:

1. `provider`: `awesome_api|alpha_vantage|yahoo_finance|stooq`
2. `type`: `stock|crypto|currency`

## `POST /api/quotation/{symbol}`

Busca e persiste cotacao no historico.

Body opcional:

```json
{
  "type": "crypto",
  "provider": "awesome_api"
}
```

Campos:

1. `type`: `stock|crypto|currency`
2. `provider`: `awesome_api|alpha_vantage|yahoo_finance|stooq`

Resposta `201` para novo registro e `200` para deduplicacao.

Observacoes:

1. `symbol` e normalizado para uppercase; pares de moeda aceitam `USD-BRL` e persistem como `USDBRL`.
2. Quando `type` nao e informado, o tipo e inferido a partir do simbolo (listas em `config/market-data.php`).

## `GET /api/quotations`

Lista historico com paginacao.
Por padrao retorna somente cotacoes `valid`.

Filtros:

1. `symbol`
2. `type` (`stock|crypto|currency`)
3. `source` (`awesome_api|alpha_vantage|yahoo_finance|stooq`)
4. `status` (`valid|invalid`)
5. `include_invalid` (`true|false|0|1`)
6. `date_from` (`YYYY-MM-DD`)
7. `date_to` (`YYYY-MM-DD`)
8. `per_page` (`1..100`, default `20`)

## `DELETE /api/quotations/{quotation}` (admin obrigatorio)

Soft delete unitario.
Requer uma das opcoes:

1. Usuario Sanctum com `is_admin=true`.
2. Request confiavel vindo do gateway com role JWT `moderator`.

## `POST /api/quotations/bulk-delete` (admin obrigatorio)

Soft delete em lote.
Requer `confirm=true`.
Sem filtros, exige `delete_all=true`.
Autorizacao igual ao endpoint unitario (`is_admin=true` ou role JWT `moderator` via gateway).

## Status Codes por endpoint

1. `GET /api/quotation/{symbol}`
   1. `200` sucesso
   2. `404` simbolo sem cotacao disponivel
   3. `422` validacao/simbolo invalido
   4. `429` rate limit (provider ou throttle da API)
   5. `503` provider indisponivel
2. `POST /api/quotation/{symbol}`
   1. `201` nova cotacao persistida
   2. `200` deduplicacao (registro equivalente ja existente)
   3. `404` simbolo sem cotacao disponivel
   4. `422` validacao/simbolo invalido
   5. `429` rate limit (provider ou throttle da API)
   6. `503` provider indisponivel
3. `GET /api/quotations`
   1. `200` sucesso
   2. `422` filtros invalidos
   3. `401` quando `QUOTATIONS_REQUIRE_AUTH=true`
   4. `429` rate limit (throttle da API)
4. `DELETE /api/quotations/{quotation}`
   1. `200` sucesso
   2. `401` sem token
   3. `403` sem permissao (`is_admin=false` ou role JWT sem privilegio admin)
   4. `404` id nao encontrado
   5. `429` rate limit (throttle da API)
5. `POST /api/quotations/bulk-delete`
   1. `200` sucesso
   2. `401` sem token
   3. `403` sem permissao (`is_admin=false` ou role JWT sem privilegio admin)
   4. `422` payload invalido (`confirm/delete_all/filtros`)
   5. `429` rate limit (throttle da API)
6. `POST /api/auth/token`
   1. `201` token emitido
   2. `422` credenciais invalidas/payload invalido
7. `DELETE /api/auth/token`
   1. `200` token revogado
   2. `401` sem token
8. `GET /api/user`
   1. `200` sucesso
   2. `401` sem token
9. `GET /dashboard/operations/auto-collect`
   1. `200` sucesso
   2. `403` bloqueado fora de `local/testing`
10. `PUT /dashboard/operations/auto-collect`
   1. `200` sucesso
   2. `403` bloqueado fora de `local/testing`
   3. `422` payload invalido
   4. `419` CSRF ausente/invalido
11. `POST /dashboard/operations/auto-collect/run`
   1. `200` sucesso
   2. `403` bloqueado fora de `local/testing`
   3. `422` payload invalido
   4. `419` CSRF ausente/invalido
12. `GET /dashboard/operations/auto-collect/history`
   1. `200` sucesso
   2. `403` bloqueado fora de `local/testing`
   3. `422` parametro `limit` invalido
13. `GET /dashboard/operations`
   1. `200` sucesso
   2. `403` bloqueado fora de `local/testing`

Observacao:

1. Quando `QUOTATIONS_REQUIRE_AUTH=true`, os endpoints de cotacao (`GET /api/quotation/{symbol}`, `POST /api/quotation/{symbol}`, `GET /api/quotations`) tambem podem retornar `401`.

## Dashboard de operacoes (rotas web JSON)

1. `GET /dashboard/operations/auto-collect`
2. `PUT /dashboard/operations/auto-collect`
3. `POST /dashboard/operations/auto-collect/run`
4. `GET /dashboard/operations/auto-collect/history?limit=20`

As rotas existem em qualquer ambiente, mas a autorizacao do dashboard bloqueia chamadas fora de `local/testing` com `403`.

Payloads e respostas (resumo):

### `GET /dashboard/operations/auto-collect`

Retorna `enabled`, `interval_minutes`, `symbols`, `provider`, `available_providers`, `cron_expression`, `requires_scheduler_restart` e `scheduler_restart_note`.

### `PUT /dashboard/operations/auto-collect`

Payload:

```json
{
  "enabled": true,
  "interval_minutes": 15,
  "symbols": ["BTC", "ETH"],
  "provider": "awesome_api"
}
```

`symbols` aceita array ou CSV; `provider` pode ser vazio para limpar. Resposta igual ao `GET`.

### `POST /dashboard/operations/auto-collect/run`

Payload:

```json
{
  "symbols": ["BTC", "ETH"],
  "provider": "awesome_api",
  "dry_run": true,
  "force_provider": false
}
```

Resposta inclui `exit_code`, `summary`, `output`, `requested_provider`, `effective_provider`, `auto_fallback_applied` e `warnings`.

### `GET /dashboard/operations/auto-collect/history?limit=20`

`limit` entre `1..100` (default `20`). Retorna entradas com `run_id`, `trigger`, `status`, `started_at`, `finished_at`, `summary`, `exit_code`, `symbols` e metadados do comando.

## Coleta automatica

Comandos:

1. `php artisan quotations:collect`
2. `php artisan quotations:collect --symbol=BTC --dry-run`
3. `php artisan quotations:collect --symbol=BTC --provider=awesome_api --allow-partial-success`
4. `php artisan quotations:collect --ignore-config-provider --trigger=scheduler`
5. `php artisan quotations:reconcile`
6. `php artisan quotations:reconcile --dry-run`

Regras:

1. O agendamento de `quotations:collect` so existe quando `QUOTATIONS_AUTO_COLLECT_ENABLED=true`.
2. O intervalo vem de `QUOTATIONS_AUTO_COLLECT_INTERVAL_MINUTES` (limitado para `1..59`).
3. `quotations:collect` aceita `--symbol=*`, `--provider=`, `--dry-run`, `--ignore-config-provider`, `--allow-partial-success` e `--trigger=manual|dashboard|scheduler`.
4. Por padrao, o comando retorna exit code `1` quando ha falhas.
5. Com `--allow-partial-success`, retorna exit code `0` quando ao menos um simbolo for processado com sucesso.

## Regras de fallback de provider

Sem `provider` explicito:

1. `stock`: `alpha_vantage -> yahoo_finance -> awesome_api`
2. `crypto`: `awesome_api -> alpha_vantage`
3. `currency`: `awesome_api -> alpha_vantage`

Com `provider` explicito:

1. Sem fallback (fail-fast) tanto no `GET` quanto no `POST` de cotacao.

## Erros padronizados

Formato:

```json
{
  "message": "Validation failed.",
  "error_code": "validation_error",
  "request_id": "uuid",
  "details": {}
}
```

`details` so aparece com `APP_DEBUG=true`.

Catalogo atual de `error_code`:

1. `invalid_symbol`
2. `quote_not_found`
3. `provider_unavailable`
4. `provider_rate_limited`
5. `validation_error`
6. `unauthenticated`
7. `forbidden`
8. `too_many_requests`
9. `not_found`
10. `method_not_allowed`
11. `csrf_mismatch`
12. `http_error`
13. `internal_error`
