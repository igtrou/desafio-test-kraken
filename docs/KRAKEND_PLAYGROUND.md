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
| `krakend-observability` | `jaeger`, `influxdb`, `grafana` | Stack de observabilidade pronta para integrar exporters do KrakenD |

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

1. Laravel API direta: `http://localhost`
2. KrakenD Gateway: `http://localhost:8080`
3. KrakenD Debug endpoint: `http://localhost:8090`
4. Keycloak (perfil `krakend-auth`): `http://localhost:8085`
5. RabbitMQ (perfil `krakend-async`): `http://localhost:15672` (`guest` / `guest`)
6. Jaeger (perfil `krakend-observability`): `http://localhost:16686`
7. Grafana (perfil `krakend-observability`): `http://localhost:4000` (`admin` / `admin` por default)

Nota: o perfil `krakend-observability` sobe as ferramentas; para popular dashboards/traces voce deve configurar exporters no `krakend.json` conforme sua estrategia de telemetria.

## Rotas ja configuradas no gateway

Arquivo de configuracao: `docker/krakend/krakend.json`.

| Tipo | Endpoint KrakenD | Backend Laravel |
| --- | --- | --- |
| Auth token | `POST /api/auth/token` | `POST /api/auth/token` |
| Auth revoke | `DELETE /api/auth/token` | `DELETE /api/auth/token` |
| Perfil autenticado | `GET /api/user` | `GET /api/user` |
| Buscar cotacao | `GET /api/quotation/{symbol}` | `GET /api/quotation/{symbol}` |
| Persistir cotacao | `POST /api/quotation/{symbol}` | `POST /api/quotation/{symbol}` |
| Historico | `GET /api/quotations` | `GET /api/quotations` |
| Excluir em lote | `POST /api/quotations/bulk-delete` | `POST /api/quotations/bulk-delete` |
| Excluir unitario | `DELETE /api/quotations/{quotation}` | `DELETE /api/quotations/{quotation}` |
| Agregado (playground) | `GET /playground/quotation/{symbol}/snapshot` | `GET /api/quotation/{symbol}` + `GET /api/user` |
| JWT + role (playground) | `GET /playground/private/quotation/{symbol}` | `GET /api/quotation/{symbol}` |

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
  "endpoint": "/api/minha-api/{id}",
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

Use varios objetos em `backend` e defina `group` para cada fonte. O endpoint `GET /playground/quotation/{symbol}/snapshot` ja mostra esse padrao.

### 3. JWT validation e roles

No endpoint, adicione `extra_config.auth/validator` com `jwk_url`, algoritmo e roles permitidas.

Exemplo real no projeto:

1. Endpoint: `/playground/private/quotation/{symbol}`
2. JWK URL: `http://keycloak:8080/realms/krakend/protocol/openid-connect/certs`
3. Roles aceitas: `reader` e `moderator`

### 4. Rate limit e resiliencia

No endpoint `GET /api/quotation/{symbol}` ja existem:

1. `qos/ratelimit/router`: limita throughput por rota no gateway.
2. `qos/circuit-breaker`: reduz impacto quando backend falha em sequencia.

### 5. CORS

Configurado no nivel global (`extra_config.security/cors`) para facilitar uso web via browser.

## Fluxo rapido com curl

1. Emitir token:

```bash
curl --request POST --url 'http://localhost:8080/api/auth/token' \
  --header 'Content-Type: application/json' \
  --data '{"email":"test@example.com","password":"password","device_name":"krakend-cli"}'
```

2. Buscar cotacao via gateway:

```bash
curl --request GET --url 'http://localhost:8080/api/quotation/BTC?type=crypto'
```

3. Buscar cotacao autenticada com token:

```bash
curl --request GET --url 'http://localhost:8080/api/quotations?symbol=BTC&per_page=5' \
  --header 'Authorization: Bearer SEU_TOKEN'
```

4. Testar endpoint agregado:

```bash
curl --request GET --url 'http://localhost:8080/playground/quotation/BTC/snapshot?type=crypto' \
  --header 'Authorization: Bearer SEU_TOKEN'
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
3. Gateway nao sobe: valide JSON com `jq . docker/krakend/krakend.json`.
4. Porta ocupada: ajuste `KRAKEND_PORT`, `KRAKEND_DEBUG_PORT` e afins no `.env`.
