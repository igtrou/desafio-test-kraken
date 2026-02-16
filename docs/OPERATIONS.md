# Operacao e Runbook

Este documento concentra rotinas operacionais do escopo simplificado.
Use em conjunto com:

1. [`README.md`](README.md)
2. [`API.md`](API.md)
3. [`ARCHITECTURE.md`](ARCHITECTURE.md)
4. [`ARCHITECTURE_GUIDELINES.md`](ARCHITECTURE_GUIDELINES.md)
5. [`DOCUMENTATION_GUIDE.md`](DOCUMENTATION_GUIDE.md)

## Variaveis de ambiente chave

| Variavel | Default | Efeito |
| --- | --- | --- |
| `APP_ENV` | `local` | Controla restricoes de ambiente (ex.: operacoes do dashboard apenas em `local/testing`). |
| `APP_URL` | `http://localhost` | Base URL usada para gerar links internos e Swagger. |
| `APP_BIND_HOST` | `127.0.0.1` | Bind de rede do Laravel no Compose para reduzir exposicao direta no host. |
| `FRONTEND_URL` | `http://localhost:3000` | Base do frontend para redirecionamento de verificacao de e-mail e CORS. |
| `KRAKEND_PORT` | `8080` | Porta HTTP do KrakenD (gateway). |
| `KRAKEND_DEBUG_PORT` | `8090` | Porta do endpoint de debug do KrakenD. |
| `KRAKEND_PROMETHEUS_PORT` | `9091` | Porta do exporter Prometheus do KrakenD. |
| `GATEWAY_ENFORCE_SOURCE` | `false` | Quando `true`, exige segredo interno para aceitar requests da API (bloqueia bypass direto). |
| `GATEWAY_SHARED_SECRET` | `krakend-internal` | Segredo compartilhado entre KrakenD e Laravel para validar origem. |
| `GATEWAY_SHARED_SECRET_HEADER` | `X-Gateway-Secret` | Nome do header interno usado para validar origem do request. |
| `GATEWAY_TRUST_JWT_ASSERTION` | `true` | Permite confiar no marcador de JWT validado pelo gateway. |
| `GATEWAY_JWT_ASSERTION_HEADER` | `X-Gateway-Auth` | Header interno usado para marcar request com JWT validado no gateway. |
| `GATEWAY_JWT_ASSERTION_VALUE` | `jwt` | Valor esperado no header de assercao JWT do gateway. |
| `GATEWAY_JWT_ROLES_HEADER` | `X-Auth-Roles` | Header de roles propagado do JWT validado no gateway. |
| `GATEWAY_JWT_SUBJECT_HEADER` | `X-Auth-Subject` | Header de subject propagado do JWT validado no gateway. |
| `GATEWAY_JWT_MODERATOR_ROLE` | `moderator` | Role que autoriza operacoes administrativas de cotacoes via JWT. |
| `KEYCLOAK_PORT` | `8085` | Porta do Keycloak no perfil `krakend-auth`. |
| `KEYCLOAK_ADMIN_USER` | `admin` | Usuario admin inicial do Keycloak. |
| `KEYCLOAK_ADMIN_PASSWORD` | `admin` | Senha admin inicial do Keycloak. |
| `RABBITMQ_PORT` | `5672` | Porta AMQP do RabbitMQ no perfil `krakend-async`. |
| `RABBITMQ_MANAGEMENT_PORT` | `15672` | Console web do RabbitMQ. |
| `JAEGER_UI_PORT` | `16686` | Porta da UI do Jaeger no perfil `krakend-observability`. |
| `JAEGER_OTLP_HTTP_PORT` | `4318` | Porta OTLP HTTP do Jaeger. |
| `INFLUXDB_PORT` | `8086` | Porta do InfluxDB no perfil `krakend-observability`. |
| `PROMETHEUS_PORT` | `9090` | Porta da UI/API do Prometheus no perfil `krakend-observability`. |
| `GRAFANA_PORT` | `4000` | Porta da UI do Grafana no perfil `krakend-observability`. |
| `GRAFANA_ADMIN_USER` | `admin` | Usuario admin inicial do Grafana. |
| `GRAFANA_ADMIN_PASSWORD` | `admin` | Senha admin inicial do Grafana. |
| `MARKET_DATA_PROVIDER` | `awesome_api` | Provider default quando nao informado explicitamente. |
| `ALPHA_VANTAGE_KEY` | vazio | Chave obrigatoria para consultas via Alpha Vantage. |
| `ALPHA_VANTAGE_URL` | `https://www.alphavantage.co` | Endpoint base do provider Alpha Vantage. |
| `ALPHA_VANTAGE_CURRENCY` | `USD` | Moeda default para retornos do Alpha Vantage. |
| `ALPHA_VANTAGE_TIMEZONE` | `UTC` | Timezone usado para timestamps do Alpha Vantage. |
| `AWESOME_API_URL` | `https://economia.awesomeapi.com.br/json/last` | Endpoint base do provider AwesomeAPI. |
| `AWESOME_QUOTE_CURRENCY` | `USD` | Moeda default para retornos do AwesomeAPI. |
| `AWESOME_API_TIMEZONE` | `America/Sao_Paulo` | Timezone usado para timestamps do AwesomeAPI. |
| `YAHOO_FINANCE_URL` | `https://query1.finance.yahoo.com` | Endpoint base do provider Yahoo Finance. |
| `YAHOO_FINANCE_CURRENCY` | `USD` | Moeda default para retornos do Yahoo Finance. |
| `STOOQ_URL` | `https://stooq.com` | Endpoint base do provider Stooq. |
| `STOOQ_CURRENCY` | `USD` | Moeda default para retornos do provider Stooq. |
| `QUOTATIONS_REQUIRE_AUTH` | `false` | Exige Sanctum nas rotas de cotacao quando `true`. |
| `QUOTATIONS_RATE_LIMIT` | `60,1` | Limite por minuto nas rotas de cotacao. |
| `QUOTATIONS_CACHE_TTL` | `60` | TTL de cache (segundos) para fetch externo. |
| `QUOTATIONS_AUTO_COLLECT_ENABLED` | `false` | Ativa registro do agendamento de coleta. |
| `QUOTATIONS_AUTO_COLLECT_INTERVAL_MINUTES` | `15` | Intervalo da coleta automatica (`1..59`). |
| `QUOTATIONS_AUTO_COLLECT_SYMBOLS` | `BTC,ETH,MSFT,USD-BRL` | Lista default de simbolos para coleta. |
| `QUOTATIONS_AUTO_COLLECT_PROVIDER` | vazio | Provider fixo opcional da auto-coleta. |
| `QUOTATIONS_AUTO_COLLECT_HISTORY_PATH` | `storage/app/operations/collect-runs.jsonl` | Caminho do historico em JSONL usado pelo dashboard de operacoes. |
| `QUOTATIONS_AUTO_COLLECT_HISTORY_FALLBACK_PATH` | `storage/framework/operations/collect-runs.local.jsonl` | Fallback usado quando o historico principal nao pode ser gravado (ex.: permissao). |
| `QUOTATIONS_OUTLIER_GUARD_ENABLED` | `true` | Liga classificacao de outlier. |
| `QUOTATIONS_OUTLIER_GUARD_WINDOW` | `20` | Janela historica para outlier guard. |
| `QUOTATIONS_OUTLIER_GUARD_MIN_POINTS` | `4` | Minimo de pontos para avaliar outlier. |
| `QUOTATIONS_OUTLIER_GUARD_MAX_DEVIATION_RATIO` | `0.85` | Tolerancia de desvio para outlier guard. |
| `ACTIVITY_LOGGER_ENABLED` | `true` | Liga/desliga auditoria via `activity_log`. |
| `ACTIVITY_LOGGER_TABLE_NAME` | `activity_log` | Nome da tabela usada pelo Activity Log. |
| `ACTIVITY_LOGGER_DB_CONNECTION` | vazio | Conexao opcional dedicada para auditoria. |

## Rotina diaria

1. Coleta manual critica:
```bash
php artisan quotations:collect --symbol=BTC --symbol=ETH
```
2. Reconciliacao dry-run:
```bash
php artisan quotations:reconcile --dry-run
```
3. Reconciliacao efetiva:
```bash
php artisan quotations:reconcile
```

## Smoke Test Rapido (5 minutos)

1. Health da aplicacao:
```bash
php artisan about
```
2. Buscar cotacao sem persistir:
```bash
curl --request GET --url 'http://localhost:8080/v1/public/quotation/BTC?type=crypto'
```
3. Emitir token Sanctum via gateway:
```bash
curl --request POST --url 'http://localhost:8080/v1/public/auth/token' \
  --header 'Content-Type: application/json' \
  --data '{"email":"test@example.com","password":"password","device_name":"ops-smoke"}'
```
4. Consultar perfil autenticado no gateway:
```bash
curl --request GET --url 'http://localhost:8080/v1/private/user' \
  --header 'Authorization: Bearer SEU_TOKEN_SANCTUM'
```
5. Verificar comandos operacionais:
```bash
php artisan quotations:collect --symbol=BTC --dry-run
php artisan quotations:reconcile --dry-run
```

## KrakenD Playground (Docker profiles)

Subir apenas gateway:

```bash
docker compose --profile krakend up -d krakend
```

Subir playground completo:

```bash
docker compose \
  --profile krakend \
  --profile krakend-auth \
  --profile krakend-async \
  --profile krakend-observability \
  up -d
```

Logs do gateway:

```bash
docker compose logs -f krakend
```

URLs:

1. Gateway: `http://localhost:8080`
2. Debug KrakenD: `http://localhost:8090`
3. Keycloak: `http://localhost:8085`
4. RabbitMQ: `http://localhost:15672`
5. Jaeger: `http://localhost:16686`
6. Prometheus: `http://localhost:9090`
7. Grafana: `http://localhost:4000`
8. KrakenD metrics: `http://localhost:9091/metrics`

Superficie recomendada no gateway:

1. Publico: `/v1/public/...`
2. Privado versionado: `/v1/private/...` (JWT para cotacoes e Sanctum para perfil/revogacao de token)
3. Rotas internas Laravel (`/api/*`) ficam apenas no backend, sem exposicao direta no gateway.

Guia de uso e rotas prontas: [`KRAKEND_PLAYGROUND.md`](KRAKEND_PLAYGROUND.md).

Nota: o perfil `krakend-observability` provisiona as ferramentas, e o projeto ja inclui exportacao de metricas/traces do KrakenD via `telemetry/opentelemetry` no `docker/krakend/krakend.json`.
Nota de seguranca: em ambientes de producao, ative `GATEWAY_ENFORCE_SOURCE=true`.

## Observabilidade Gateway (Fase 4)

Regras de alerta base (Prometheus):

1. Arquivo: `docker/prometheus/rules/krakend-alerts.yml`.
2. Alertas provisionados:
   1. `KrakenDHigh5xxRate`
   2. `KrakenDHighP95Latency`
   3. `KrakenDUpstreamErrors`

Validacao de regras carregadas:

```bash
curl --request GET --url 'http://localhost:9090/api/v1/rules' \
  | grep -E 'KrakenDHigh5xxRate|KrakenDHighP95Latency|KrakenDUpstreamErrors'
```

Estado atual dos alertas:

```bash
curl --request GET --url 'http://localhost:9090/api/v1/alerts'
```

Probe de observabilidade (InfluxDB write/read):

```bash
scripts/architecture/influx_probe.sh
```

Incidente controlado end-to-end (erro de upstream):

```bash
# 1) derruba upstream Laravel de forma temporaria
docker compose stop laravel.test

# 2) gera erro no gateway com request id rastreavel
REQUEST_ID="phase4-incident-$(date +%s)"
curl --request GET --include \
  --url 'http://localhost:8080/v1/public/quotation/BTC?type=crypto' \
  --header "X-Request-Id: ${REQUEST_ID}"

# 3) aumenta amostra de erro para acionar alerta
scripts/gateway/load_test.sh \
  --url 'http://localhost:8080/v1/public/quotation/BTC?type=crypto' \
  --requests 120 \
  --concurrency 8 \
  --timeout 2 \
  --request-id-prefix phase4-incident-load-

# 4) sobe upstream de volta
docker compose start laravel.test
```

Atalho automatizado do incidente (inclui restore, metricas Prometheus e correlacao Jaeger):

```bash
scripts/architecture/incident_rehearsal.sh
```

Suite detalhada de carga + verificacao de utilizacao dos servicos do gateway:

```bash
scripts/gateway/deep_load_suite.sh
```

Modo rapido (smoke da suite):

```bash
scripts/gateway/deep_load_suite.sh --quick
```

Somente superficie publica (sem auth privada):

```bash
scripts/gateway/deep_load_suite.sh --public-only --skip-prometheus
```

Artefato de relatorio da suite detalhada:

1. `storage/app/operations/load-reports/<timestamp>/deep-load-report.md`
2. A suite trata `429` (throttle) como esperado em cenarios de stress/soak e registra isso em `Notes` (`allowed_all_429=true`).
3. Para endurecer o criterio, use `--strict` (warnings opcionais passam a falhar).
4. Para reexecucoes seguidas, ajuste a recuperacao de rate-limit:
```bash
scripts/gateway/deep_load_suite.sh --throttle-recovery-seconds 65 --throttle-recovery-attempts 2
```

Probe de mensageria (RabbitMQ publish/consume):

```bash
scripts/architecture/rabbitmq_probe.sh
```

Pipeline unico de validacao (smoke + incidente opcional + relatorio):

```bash
scripts/architecture/run_validation_pipeline.sh --up --include-incident
composer run architecture:pipeline
```

Artefato de relatorio:

1. `storage/app/operations/architecture-reports/validation-<timestamp>.md`

Correlacao no Jaeger:

```bash
curl --request GET \
  --url 'http://localhost:16686/api/traces?service=krakend_gateway&lookback=1h&limit=20' \
  | grep "${REQUEST_ID}"
```

## Scheduler

Desenvolvimento local:

```bash
php artisan schedule:work
```

Com Sail:

```bash
./vendor/bin/sail artisan schedule:work
```

Producao (cron Laravel):

```cron
* * * * * php /caminho/para/projeto/artisan schedule:run >> /dev/null 2>&1
```

Notas:

1. O job so entra no scheduler com `QUOTATIONS_AUTO_COLLECT_ENABLED=true`.
2. O intervalo vem de `QUOTATIONS_AUTO_COLLECT_INTERVAL_MINUTES`.
3. Por padrao, falhas de coleta retornam exit code `1`.
4. `--allow-partial-success` permite sucesso parcial (exit code `0` quando houver ao menos um simbolo bem-sucedido).

## Dashboard de Operacoes

URL:

1. `GET /dashboard/quotations`
2. `GET /dashboard` (alias para `/dashboard/quotations`)
3. `GET /dashboard/operations`
4. `GET /dashboard/operations/auto-collect`
5. `PUT /dashboard/operations/auto-collect`
6. `POST /dashboard/operations/auto-collect/run`
7. `GET /dashboard/operations/auto-collect/history`

Controle de acesso:

1. As rotas web existem no roteamento sempre.
2. A restricao de ambiente e aplicada na action da pagina de operacoes e nas actions/services dos endpoints JSON.
3. Fora de `local/testing`, `GET /dashboard/operations` e os endpoints JSON de operacoes retornam `403` (`DashboardOperationsAuthorizationService`).
4. `GET /dashboard/quotations` permanece acessivel para a interface principal de cotacoes.
5. O gate depende de `APP_ENV` (ajuste para `local` ou `testing` quando precisar operar o painel).

Acoes principais:

1. Salvar configuracao de auto-coleta (escreve `.env`).
2. Recarregar configuracao ativa.
3. Rodar `quotations:collect` sob demanda com output no painel.
4. Sempre envia `--allow-partial-success` e `--trigger=dashboard` na execucao manual do painel.
5. Quando `provider` for informado e `force_provider=false`, tipos mistos fazem o painel ignorar provider fixo e aplicar fallback automatico (`--ignore-config-provider`).
6. O endpoint de execucao manual aceita `symbols`, `provider`, `dry_run` e `force_provider` (`symbols` pode ser array ou CSV).
7. Consultar historico de execucoes recentes (inclui trigger, simbolos e resumo sucesso/falha).

## Logs de execucao da coleta

Arquivos:

1. `storage/logs/quotation-collect-YYYY-MM-DD.log`: eventos `collect_started` e `collect_finished`.
2. `storage/app/operations/collect-runs.jsonl`: caminho principal do historico.
3. `storage/framework/operations/collect-runs.local.jsonl`: caminho de fallback quando o principal nao puder ser gravado.
4. O dashboard le principal + fallback e ordena por `finished_at`/`started_at` (mais recente primeiro).

## Troubleshooting

1. Sintoma: `SQLSTATE[HY000] [2002] ... host mysql`.
   Acao: usar `./vendor/bin/sail artisan ...` ou ajustar `.env` para ambiente sem Docker.
2. Sintoma: auto-coleta nao executa.
   Acao: validar `QUOTATIONS_AUTO_COLLECT_ENABLED=true` e processo `schedule:work` ativo.
3. Sintoma: cotacao atrasada.
   Acao: revisar `QUOTATIONS_CACHE_TTL` e provider escolhido.
4. Sintoma: `401` nas rotas de cotacao.
   Acao: revisar `QUOTATIONS_REQUIRE_AUTH` e token Sanctum.
5. Sintoma: `403` nas rotas `/api/*` apos ativar enforcement de gateway.
   Acao: validar `GATEWAY_ENFORCE_SOURCE=true`, `GATEWAY_SHARED_SECRET` e o header interno injetado pelo KrakenD.
6. Sintoma: `sessions` table does not exist.
   Acao: executar migrations (`php artisan migrate` ou `./vendor/bin/sail artisan migrate`).

## Nota de escopo

Esta branch remove da superficie publica os fluxos de alertas e carteiras.
