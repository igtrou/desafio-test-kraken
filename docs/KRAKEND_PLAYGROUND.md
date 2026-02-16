# KrakenD Playground no Projeto

Este guia adiciona um playground de API Gateway com KrakenD no mesmo `docker compose` do Laravel.

Objetivos desta integracao:

1. Expor suas APIs pelo gateway em `http://localhost:8080`.
2. Ter exemplos prontos de proxy simples, agregacao e protecao JWT.
3. Subir opcoes adicionais (Keycloak, RabbitMQ e observabilidade) por perfil, sem impactar o fluxo atual do projeto.

## Perfis Docker disponiveis

| Perfil | Servicos | Uso |
| --- | --- | --- |
| `krakend` | `krakend` | Gateway principal para suas APIs Laravel |
| `krakend-auth` | `keycloak` | Testes de JWT e autorizacao por role |
| `krakend-async` | `rabbitmq` | Testes de fluxo assincrono / fila |
| `krakend-observability` | `jaeger`, `prometheus`, `influxdb`, `grafana` | Stack de observabilidade com scraping Prometheus + dashboard e alertas base do KrakenD |

## Subindo o playground

1. Subir stack base Laravel (Sail):

```bash
./vendor/bin/sail up -d
```

2. Subir somente o KrakenD:

```bash
docker compose --profile krakend up -d krakend
```

3. Subir stack completa do playground (gateway + opcoes):

```bash
docker compose \
  --profile krakend \
  --profile krakend-auth \
  --profile krakend-async \
  --profile krakend-observability \
  up -d
```

4. Acompanhar logs do gateway:

```bash
docker compose logs -f krakend
```

5. Encerrar tudo:

```bash
docker compose down
```

## URLs uteis

1. Laravel API direta (interna, nao recomendada para clientes): `http://localhost`
2. KrakenD Gateway (base da API versionada): `http://localhost:8080`
3. KrakenD Debug endpoint: `http://localhost:8090`
4. Keycloak (perfil `krakend-auth`): `http://localhost:8085`
5. RabbitMQ (perfil `krakend-async`): `http://localhost:15672` (`guest` / `guest`)
6. Jaeger (perfil `krakend-observability`): `http://localhost:16686`
7. Prometheus (perfil `krakend-observability`): `http://localhost:9090`
8. Grafana (perfil `krakend-observability`): `http://localhost:4000` (`admin` / `admin` por default)
9. Exporter Prometheus do KrakenD: `http://localhost:9091/metrics`

Nota: o `krakend.json` do projeto ja inclui `telemetry/opentelemetry` (Prometheus + OTLP/Jaeger) e `telemetry/logging`. As regras base de alerta ficam em `docker/prometheus/rules/krakend-alerts.yml`.

## Rotas ja configuradas no gateway

Arquivo de configuracao: `docker/krakend/krakend.json`.

| Tipo | Endpoint KrakenD | Backend Laravel |
| --- | --- | --- |
| Publico v1 | `POST /v1/public/auth/token` | `POST /api/auth/token` |
| Privado v1 (Sanctum) | `DELETE /v1/private/auth/token` | `DELETE /api/auth/token` |
| Privado v1 (Sanctum) | `GET /v1/private/user` | `GET /api/user` |
| Publico v1 | `GET /v1/public/quotation/{symbol}` | `GET /api/quotation/{symbol}` |
| Privado v1 (JWT) | `GET /v1/private/quotation/{symbol}` | `GET /api/quotation/{symbol}` |
| Privado v1 (JWT) | `POST /v1/private/quotation/{symbol}` | `POST /api/quotation/{symbol}` |
| Privado v1 (JWT) | `GET /v1/private/quotations` | `GET /api/quotations` |
| Privado v1 (JWT + moderator) | `POST /v1/private/quotations/bulk-delete` | `POST /api/quotations/bulk-delete` |
| Privado v1 (JWT + moderator) | `DELETE /v1/private/quotations/{quotation}` | `DELETE /api/quotations/{quotation}` |

Nota: os endpoints legados `/api/*` e os endpoints de playground (`/playground/*`) nao estao expostos no KrakenD; use somente a superficie `/v1/*`.

Contrato interno de seguranca:

1. O KrakenD injeta `X-Gateway-Secret` para o Laravel.
2. Para rotas JWT privadas, o KrakenD injeta `X-Gateway-Auth: jwt` e propaga claims (`roles`, `sub`).
3. A API pode bloquear bypass direto com `GATEWAY_ENFORCE_SOURCE=true`.
4. Clientes externos nao devem enviar `X-Gateway-Secret`.

## Como usar suas APIs no KrakenD

### 1. Proxy simples

Crie um novo bloco em `endpoints` com:

1. `endpoint`: rota publica no gateway.
2. `method`: verbo HTTP exposto.
3. `backend.host`: URL da sua API upstream.
4. `backend.url_pattern`: rota real no backend.

Exemplo:

```json
{
  "endpoint": "/v1/public/minha-api/{id}",
  "method": "GET",
  "input_query_strings": ["lang", "verbose"],
  "input_headers": ["Authorization", "X-Request-Id"],
  "backend": [
    {
      "host": ["http://laravel.test"],
      "url_pattern": "/api/minha-api/{id}",
      "encoding": "json"
    }
  ]
}
```

### 2. Agregacao de multiplas APIs

Use varios objetos em `backend` e defina `group` para cada fonte. A agregacao continua suportada para endpoints customizados versionados em `/v1/...`.

### 3. JWT validation e roles

No endpoint, adicione `extra_config.auth/validator` com `jwk_url`, algoritmo e roles permitidas.

Exemplo real no projeto:

1. Endpoint: `/v1/private/quotation/{symbol}`
2. JWK URL: `http://keycloak:8080/realms/krakend/protocol/openid-connect/certs`
3. Roles aceitas: `reader` e `moderator`
4. Realm importado automaticamente: `krakend`
5. Usuarios prontos:
   - `reader` / `reader`
   - `moderator` / `moderator`

### 4. Rate limit e resiliencia

No pacote v1 de cotacoes (`/v1/public/quotation/{symbol}` e `/v1/private/*`) ja existem:

1. `timeout` por endpoint/backend para evitar requests longas.
2. `qos/ratelimit/router` por rota e por cliente (`client_max_rate` + `strategy: ip`).
3. `qos/circuit-breaker` para leitura critica (`/quotation` e `/quotations`).
4. `cache_ttl: 15s` + `qos/http-cache` em `GET /v1/public/quotation/{symbol}` e `GET /v1/private/quotation/{symbol}`.
5. `backend/http.return_error_code: true` nas rotas v1 para preservar status de erro upstream (ex.: `429`) sem mascarar como `500`.
6. `telemetry/opentelemetry` para metricas/traces (`/metrics` em `:9091`, export OTLP para Jaeger).

### 5. CORS

Configurado no nivel global (`extra_config.security/cors`) para facilitar uso web via browser.

## Fluxo rapido com curl

1. Emitir token:

```bash
curl --request POST --url 'http://localhost:8080/v1/public/auth/token' \
  --header 'Content-Type: application/json' \
  --data '{"email":"test@example.com","password":"password","device_name":"krakend-cli"}'
```

2. Buscar cotacao publica via gateway:

```bash
curl --request GET --include --url 'http://localhost:8080/v1/public/quotation/BTC?type=crypto' \
  --header 'X-Request-Id: krakend-smoke-public-1'
```

3. Gerar token Keycloak para rotas privadas (`/v1/private/...`):

```bash
curl --request POST --url 'http://localhost:8085/realms/krakend/protocol/openid-connect/token' \
  --header 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'client_id=krakend-playground' \
  --data-urlencode 'username=reader' \
  --data-urlencode 'password=reader' \
  --data-urlencode 'grant_type=password'
```

4. Buscar cotacao privada com JWT:

```bash
curl --request GET --url 'http://localhost:8080/v1/private/quotation/BTC?type=crypto' \
  --header 'Authorization: Bearer SEU_ACCESS_TOKEN_KEYCLOAK'
```

5. Buscar historico privado com JWT:

```bash
curl --request GET --url 'http://localhost:8080/v1/private/quotations?symbol=BTC&per_page=5' \
  --header 'Authorization: Bearer SEU_ACCESS_TOKEN_KEYCLOAK'
```

6. Excluir cotacao (role `moderator`):

```bash
curl --request DELETE --url 'http://localhost:8080/v1/private/quotations/1' \
  --header 'Authorization: Bearer SEU_ACCESS_TOKEN_KEYCLOAK'
```

7. Rodar baseline de carga curta no gateway:

```bash
scripts/gateway/load_test.sh \
  --url 'http://localhost:8080/v1/public/quotation/BTC?type=crypto' \
  --requests 15 \
  --concurrency 1 \
  --timeout 6 \
  --no-request-id
```

8. Validar metricas no Prometheus:

```bash
curl --request GET --url 'http://localhost:9090/api/v1/query?query=sum(rate(http_server_duration_count%7Bhttp_route%3D~%22%2Fv1%2F.*%22%7D%5B1m%5D))'
```

9. Dashboard inicial no Grafana:

1. Dashboard provisionado: `KrakenD Overview`.
2. Datasource provisionado: `Prometheus`.

10. Validar regras de alerta carregadas no Prometheus:

```bash
curl --request GET --url 'http://localhost:9090/api/v1/rules' \
  | grep -E 'KrakenDHigh5xxRate|KrakenDHighP95Latency|KrakenDUpstreamErrors'
```

11. Teste rapido de incidente controlado (deve gerar alertas de erro de upstream):

```bash
docker compose stop laravel.test

scripts/gateway/load_test.sh \
  --url 'http://localhost:8080/v1/public/quotation/BTC?type=crypto' \
  --requests 120 \
  --concurrency 8 \
  --timeout 2 \
  --request-id-prefix phase4-incident-

docker compose start laravel.test
```

## Ajustes recomendados para suas APIs

1. Padronize `X-Request-Id` no client e repasse no gateway.
2. Restrinja `input_headers` e `input_query_strings` para reduzir superficie.
3. Separe endpoints publicos e privados no KrakenD com validacao JWT explicita.
4. Comece com timeout curto no gateway e aumente apenas quando necessario.
5. Versione rotas no gateway (`/v1/...`) para evolucao sem quebra de clientes.

## Troubleshooting

1. `502/503` no gateway: confirme se `laravel.test` esta no ar (`./vendor/bin/sail ps`).
2. JWT endpoint falhando: suba o perfil `krakend-auth` e confira realm/usuarios no Keycloak.
3. Token Keycloak falhando em `/v1/private/...`: remova e recrie o container do Keycloak para reimportar o realm:
   `docker compose rm -sf keycloak && docker compose --profile krakend-auth up -d keycloak`
4. Gateway nao sobe: valide JSON com `jq . docker/krakend/krakend.json`.
5. Bypass direto da API bloqueado: confira `GATEWAY_ENFORCE_SOURCE=true` e consistencia entre `GATEWAY_SHARED_SECRET` e o valor do `X-Gateway-Secret` injetado no KrakenD.
6. Porta ocupada: ajuste `KRAKEND_PORT`, `KRAKEND_DEBUG_PORT` e afins no `.env`.
7. Mudou `docker/krakend/krakend.json` e nao refletiu: recarregue o gateway com `docker compose --profile krakend up -d --force-recreate krakend`.
