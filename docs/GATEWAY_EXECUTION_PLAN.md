# Plano E2E Gateway + API

Este documento explica o processo completo de migracao para o modelo:

1. Entrada unica no KrakenD.
2. Identidade central no Keycloak.
3. API Laravel mantendo autorizacao de dominio (defense in depth).
4. Migracao gradual sem quebra para clientes existentes.

## Estado atual (13/02/2026)

Status por fase:

1. Fase 1 (Perimetro): `EM PROGRESSO` (base pronta em dev).
2. Fase 2 (Seguranca hibrida): `EM PROGRESSO` (base pronta em dev).
3. Fase 3 (Resiliencia/performance): `EM PROGRESSO` (guardrails aplicados; carga curta pendente).
4. Fase 4 (Observabilidade): `EM PROGRESSO` (telemetria + alertas base prontos em dev).
5. Fase 5 (Assincrono): `PENDENTE`.
6. Fase 6 (Decisao Sanctum externo): `PENDENTE`.

Ja implementado:

1. Superficie versionada no gateway (`/v1/public/*` e `/v1/private/*`).
2. Rotas privadas com JWT validation no KrakenD.
3. Propagacao de claims (`roles`, `sub`) do JWT para headers internos.
4. Middleware `gateway.only` para bloquear bypass direto quando `GATEWAY_ENFORCE_SOURCE=true`.
5. Modelo hibrido de auth:
   1. `quotation.auth`: JWT confiado no gateway **ou** Sanctum.
   2. `quotation.admin`: admin Sanctum **ou** role `moderator` confiada no gateway.
6. Timeout explicito por endpoint v1 critico (`/v1/public/quotation`, `/v1/private/quotation`, `/v1/private/quotations`).
7. Rate limit por rota e por cliente (`client_max_rate` + `strategy=ip`) nas rotas v1 privadas/publicas.
8. Cache curto (`cache_ttl=15s`) para `GET /v1/public/quotation/{symbol}` e `GET /v1/private/quotation/{symbol}`.
9. Telemetria no gateway via `telemetry/opentelemetry`:
   1. exporter Prometheus em `:9091/metrics`;
   2. export OTLP para Jaeger.
10. Logging estruturado no KrakenD via `telemetry/logging` com `X-Request-Id` no access log.
11. Stack observabilidade com Prometheus no profile `krakend-observability`.
12. Provisionamento Grafana com datasource Prometheus e dashboard inicial `KrakenD Overview`.
13. Regras base de alerta no Prometheus (`KrakenDHigh5xxRate`, `KrakenDHighP95Latency`, `KrakenDUpstreamErrors`).

Arquivos-chave dessa base:

1. `docker/krakend/krakend.json`
2. `routes/api.php`
3. `app/Http/Middleware/EnsureRequestFromGateway.php`
4. `app/Http/Middleware/EnsureQuotationApiAuthentication.php`
5. `app/Http/Middleware/EnsureQuotationAdminAuthorization.php`
6. `config/gateway.php`

## Fluxo ponta-a-ponta (visao unica)

### Fluxo publico (`/v1/public/*`)

1. Cliente chama KrakenD.
2. KrakenD aplica politicas de trafego (ex.: ratelimit/circuit-breaker).
3. KrakenD encaminha para Laravel com header interno `X-Gateway-Secret`.
4. Laravel valida origem (`gateway.only`) e executa regra de negocio.
5. Resposta retorna com `X-Request-Id` para correlacao.

### Fluxo privado JWT (`/v1/private/*`)

1. Cliente chama KrakenD com `Authorization: Bearer <keycloak_jwt>`.
2. KrakenD valida JWT no JWK do Keycloak.
3. KrakenD propaga claims para headers internos (`X-Auth-Roles`, `X-Auth-Subject`) e marca `X-Gateway-Auth: jwt`.
4. Laravel valida origem interna.
5. Laravel aplica autorizacao de dominio:
   1. leitura/persistencia conforme politica da rota;
   2. delete/bulk-delete exige role `moderator` ou admin Sanctum.

### Fluxo interno (`/api/*`)

1. Permanece interno entre KrakenD e Laravel.
2. Nao deve ser exposto como superficie publica do gateway.

## Fases de execucao (com DoD)

## Fase 1 - Perimetro

Objetivo:

1. Garantir entrada unica via gateway.

Entregaveis:

1. Rotas versionadas no KrakenD.
2. Bind do Laravel restrito no host (`APP_BIND_HOST=127.0.0.1`).
3. Contrato de headers internos definido (`X-Gateway-Secret`, `X-Request-Id`).

DoD:

1. Requisicao externa para API direta bloqueada em prod.
2. Requisicao via KrakenD funcionando para rotas necessarias.

## Fase 2 - Seguranca hibrida

Objetivo:

1. Migrar auth sem quebrar clientes.

Entregaveis:

1. JWT obrigatorio nas rotas privadas v1 no KrakenD.
2. Sanctum mantido para compatibilidade.
3. Autorizacao de dominio final na API.
4. Controle "só vem do gateway" pronto para enforce.

DoD:

1. `GATEWAY_ENFORCE_SOURCE=true` bloqueia bypass direto.
2. Operacoes admin funcionam com:
   1. admin Sanctum, ou
   2. JWT com role `moderator`.

## Fase 3 - Resiliencia e performance

Objetivo:

1. Reduzir falhas em cascata e picos de latencia.

Entregaveis esperados:

1. Timeout por endpoint critico no gateway.
2. Circuit-breaker em dependencias instaveis.
3. Rate limit por rota/cliente.
4. Cache curto para `GET /quotation`.

Status de execucao (13/02/2026):

1. Guardrails de timeout/rate/circuit/cache aplicados no `docker/krakend/krakend.json`.
2. Smoke manual validado em `/v1/public/quotation` e `/v1/private/quotation` com `200` + `Cache-Control: max-age=15`.
3. Backend `429` agora e propagado como `429` no gateway (evita falso `500` sob throttle interno).
4. Teste de carga curto executado com script versionado e baseline registrado.
5. Pendente para fechar DoD: repetir baseline em janela operacional acordada e validar p95 sob variacao real de carga.

DoD:

1. p95 estavel sob carga.
2. Menos erro 5xx em falha parcial de upstream.

## Fase 4 - Observabilidade

Objetivo:

1. Operar por metricas e tracing.

Entregaveis esperados:

1. Dashboard com:
   1. RPS
   2. p95
   3. 4xx/5xx
   4. upstream errors
2. Traco ponta-a-ponta com `X-Request-Id`.
3. Alertas de erro/latencia.

Status de execucao (13/02/2026):

1. Base de metricas pronta:
   1. `http_server_duration_*` (gateway request latency + status);
   2. `krakend_backend_duration_*` (latencia/erro de upstream).
2. Base de tracing pronta:
   1. KrakenD exportando spans via OTLP para Jaeger;
   2. headers reportados em spans (inclui `X-Request-Id` quando enviado).
3. Dashboard inicial provisionado no Grafana com:
   1. RPS;
   2. p95 gateway;
   3. 4xx/5xx;
   4. upstream errors.
4. Regras de alerta acionaveis provisionadas no Prometheus:
   1. `KrakenDHigh5xxRate`;
   2. `KrakenDHighP95Latency`;
   3. `KrakenDUpstreamErrors`.
5. Validacao operacional de incidente end-to-end documentada no runbook (`docs/OPERATIONS.md`).
6. Validacao dev executada (13/02/2026):
   1. incidente controlado com upstream Laravel temporariamente indisponivel;
   2. expressoes de alerta acima do limiar (`5xx_rate=15.86/s`, `upstream_error_rate=15.86/s`);
   3. alertas observados em `firing` no Prometheus (`KrakenDHigh5xxRate`, `KrakenDUpstreamErrors`);
   4. trace encontrado no Jaeger com `X-Request-Id`.
7. Pendente para fechar DoD:
   1. tuning final de limiares para reduzir ruido por ambiente;
   2. integrar destino de notificacao (Alertmanager/webhook) na janela operacional acordada.

DoD:

1. Incidente reproduzivel via trace em minutos.
2. Alertas acionando sem ruido excessivo.

## Fase 5 - Assincrono

Objetivo:

1. Tirar fluxos longos da request sincrona.

Entregaveis esperados:

1. Endpoints longos retornando `202 Accepted`.
2. Publicacao de job no RabbitMQ.
3. Worker Laravel consumindo fila.
4. Endpoint de status/resultado.

DoD:

1. Sem timeout de cliente em operacoes longas.
2. Reprocessamento e rastreabilidade de jobs.

## Fase 6 - Decisao Sanctum externo

Objetivo:

1. Remover emissao externa Sanctum so quando migracao estiver completa.

Gate de decisao:

1. 100% dos clientes externos em JWT Keycloak.
2. Nenhuma dependencia ativa do fluxo legado `/api/auth/token`.
3. KPIs estaveis por janela acordada (ex.: 2-4 semanas).

DoD:

1. Sanctum externo desligado sem regressao.
2. Autorizacao de dominio na API mantida.

## Sequencia recomendada de PRs

1. PR-1 Perimetro/base v1 (feito).
2. PR-2 Seguranca hibrida e gateway-only (feito).
3. PR-3 Resiliencia (timeouts/cb/rate/cache) + testes de carga curtos (em progresso).
4. PR-4 Observabilidade (dashboards, traces, alertas) (em progresso).
5. PR-5 Assincrono RabbitMQ (202 + status endpoint + worker).
6. PR-6 Deprecacao controlada do legado e decisao Sanctum externo.

## Validacao operacional rapida

Pre-requisito:

```bash
docker compose \
  --profile krakend \
  --profile krakend-auth \
  --profile krakend-observability \
  up -d
```

Smoke publico:

```bash
curl --request GET \
  --include \
  --url 'http://localhost:8080/v1/public/quotation/BTC?type=crypto' \
  --header 'X-Request-Id: phase3-smoke-public-1'
```

Validacao de cache curto no gateway:

```bash
curl --request GET \
  --silent --show-error --include \
  --url 'http://localhost:8080/v1/public/quotation/BTC?type=crypto' \
  --header 'X-Request-Id: phase3-cache-check-1' \
  --output /dev/null | grep -i 'cache-control'
```

Teste de carga curto (baseline de execucao):

```bash
scripts/gateway/load_test.sh \
  --url 'http://localhost:8080/v1/public/quotation/BTC?type=crypto' \
  --requests 15 \
  --concurrency 1 \
  --timeout 6 \
  --no-request-id
```

Resultado de referencia (13/02/2026):

1. `2xx=15`, `4xx=0`, `5xx=0`.
2. `p95=0.028384s`.

Teste de stress controlado (esperado acionar throttle sem 5xx):

```bash
scripts/gateway/load_test.sh \
  --url 'http://localhost:8080/v1/public/quotation/BTC?type=crypto' \
  --requests 300 \
  --concurrency 20 \
  --timeout 6 \
  --request-id-prefix phase3-stress-
```

Resultado de referencia (13/02/2026):

1. `2xx=34`, `4xx=266`, `5xx=0`.
2. `p95=0.358223s`.

Validacao observabilidade base:

```bash
# 1) gerar trafego no gateway
curl --request GET \
  --url 'http://localhost:8080/v1/public/quotation/BTC?type=crypto' \
  --header 'X-Request-Id: phase4-observability-check-1'

# 2) confirmar metrica de RPS no Prometheus
curl --request GET \
  --url 'http://localhost:9090/api/v1/query?query=sum(rate(http_server_duration_count%7Bhttp_route%3D~%22%2Fv1%2F.*%22%7D%5B1m%5D))'

# 3) confirmar servico no Jaeger (traces do gateway)
curl --request GET \
  --url 'http://localhost:16686/api/services'

# 4) confirmar regras de alerta carregadas no Prometheus
curl --request GET \
  --url 'http://localhost:9090/api/v1/rules'
```

Token Keycloak (reader):

```bash
curl --request POST \
  --url 'http://localhost:8085/realms/krakend/protocol/openid-connect/token' \
  --header 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'client_id=krakend-playground' \
  --data-urlencode 'username=reader' \
  --data-urlencode 'password=reader' \
  --data-urlencode 'grant_type=password'
```

Smoke privado:

```bash
curl --request GET \
  --url 'http://localhost:8080/v1/private/quotations?symbol=BTC&per_page=5' \
  --header 'Authorization: Bearer <ACCESS_TOKEN>'
```

Enforce de origem na API:

```bash
# .env
GATEWAY_ENFORCE_SOURCE=true
```

Validacao esperada:

1. `GET http://localhost/api/quotation/BTC` direto -> `403`.
2. `GET http://localhost:8080/v1/public/quotation/BTC?type=crypto` via gateway -> `200`.

## Riscos e rollback

Riscos principais:

1. Clientes ainda usando superficie legada.
2. Politicas de auth muito restritivas em rotas privadas.
3. Falta de observabilidade antes de endurecer politicas.

Rollback pratico:

1. Desativar enforce temporariamente (`GATEWAY_ENFORCE_SOURCE=false`).
2. Reativar temporariamente endpoints legados `/api/*` no KrakenD apenas em rollback controlado.
3. Reverter apenas configuracao do gateway sem remover middlewares de dominio.
