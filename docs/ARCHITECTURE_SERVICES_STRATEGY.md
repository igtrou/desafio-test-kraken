# Estrategia de Teste Arquitetural (Todos os Servicos)

Este documento define uma estrategia pratica para usar **todos** os servicos do `compose.yaml` como laboratorio arquitetural.
Objetivo: validar contratos, observabilidade, seguranca e resiliancia agora, para reaproveitar a arquitetura em outro momento com menos risco.

## Escopo atual

Servicos cobertos:

1. `laravel.test`
2. `mysql`
3. `redis`
4. `krakend`
5. `keycloak`
6. `rabbitmq`
7. `jaeger`
8. `influxdb`
9. `prometheus`
10. `grafana`

## Principio da estrategia

Executar em ondas curtas, com criterio de pronto claro por onda.
Cada onda deve gerar evidencia tecnica (comando executado + resultado esperado) para evitar "container de pe sem valor arquitetural".

## Onda 1: Fundacao e perimetro

Objetivo:

1. Validar base da aplicacao e entrada via gateway.

Comandos:

```bash
./vendor/bin/sail up -d
scripts/architecture/up_full_stack.sh up
```

Validacoes minimas:

1. `laravel.test` responde `200` em `/dashboard/quotations`.
2. `mysql` responde `mysqladmin ping`.
3. `redis` responde `PONG`.
4. `krakend` responde `401` em `/v1/private/user` sem token (prova de superficie API ativa).

DoD da onda:

1. API e gateway funcionam com roteamento basico.
2. Dependencias core estao acessiveis.

## Onda 2: Seguranca e identidade

Objetivo:

1. Exercitar fluxo JWT completo com Keycloak + KrakenD + API.

Validacoes minimas:

1. Keycloak emite token (`reader` ou `moderator`).
2. Rota privada em `/v1/private/...` responde com token valido.
3. Contrato interno do gateway permanece (headers internos e controle de origem).

DoD da onda:

1. Fluxo privado via gateway validado ponta a ponta.
2. Base pronta para endurecer `GATEWAY_ENFORCE_SOURCE=true` na fase de hardening.

## Onda 3: Observabilidade operacional

Objetivo:

1. Garantir que metrica, trace e dashboard conseguem explicar comportamento do gateway.

Validacoes minimas:

1. Prometheus coleta `krakend:9091`.
2. Regras de alerta carregadas:
   1. `KrakenDHigh5xxRate`
   2. `KrakenDHighP95Latency`
   3. `KrakenDUpstreamErrors`
3. Jaeger lista `krakend_gateway`.
4. Grafana responde com health `database=ok`.
5. InfluxDB responde em `/ping`.
6. InfluxDB aceita escrita e leitura com `scripts/architecture/influx_probe.sh`.

DoD da onda:

1. Existe trilha observavel para incidente e capacidade de diagnostico.

## Onda 4: Assincrono e resiliancia controlada

Objetivo:

1. Usar RabbitMQ como base de testes para fluxo assincrono futuro.

Estado atual:

1. O projeto sobe RabbitMQ, mas ainda nao integra AMQP no `QUEUE_CONNECTION`.
2. Esta onda valida plataforma (broker + publish/consume via Management API), nao processamento de jobs AMQP no Laravel.

Validacoes minimas:

1. RabbitMQ management API responde (`/api/overview`).
2. Credenciais padrao de laboratorio funcionam.
3. Publicacao e consumo de mensagem com `scripts/architecture/rabbitmq_probe.sh`.

DoD da onda:

1. Broker pronto para fase de implementacao do fluxo `202 Accepted + status endpoint + worker`.

## Onda 5: Ensaios de incidente

Objetivo:

1. Provar que observabilidade e alertas reagem sob falha controlada.

Cenario recomendado:

1. Derrubar temporariamente `laravel.test`.
2. Gerar carga no gateway (`scripts/gateway/load_test.sh`).
3. Verificar alertas no Prometheus e traces no Jaeger.
4. Subir `laravel.test` novamente.

Atalho automatizado:

```bash
scripts/architecture/incident_rehearsal.sh
```

DoD da onda:

1. Time consegue reproduzir, detectar e correlacionar incidente em minutos.

## Script de execucao recomendada

Para rodar a validacao base de todos os servicos:

```bash
scripts/architecture/smoke_all_services.sh --up
```

Pipeline unico com relatorio:

```bash
scripts/architecture/run_validation_pipeline.sh --up --include-incident
composer run architecture:pipeline
```

Relatorio gerado em:

1. `storage/app/operations/architecture-reports/validation-<timestamp>.md`

Probes individuais:

```bash
scripts/architecture/rabbitmq_probe.sh
scripts/architecture/influx_probe.sh
```

Para subir/descer stack completa:

```bash
scripts/architecture/up_full_stack.sh up
scripts/architecture/up_full_stack.sh down
```

## Criterio de aprovacao para "usar em outro momento"

Considere arquitetura aprovada para proxima iniciativa quando:

1. `scripts/architecture/smoke_all_services.sh` passar sem falhas em execucoes repetidas.
2. Onda 5 (incidente) estiver documentada com evidencias.
3. Houver backlog fechado para integrar RabbitMQ de fato no app (fila AMQP + worker + status).
4. Politica final de seguranca (`GATEWAY_ENFORCE_SOURCE`) estiver definida por ambiente.
