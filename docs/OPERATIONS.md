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
| `FRONTEND_URL` | `http://localhost:3000` | Base do frontend para redirecionamento de verificacao de e-mail e CORS. |
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
curl --request GET --url 'http://localhost/api/quotation/BTC'
```
3. Buscar e persistir cotacao:
```bash
curl --request POST --url 'http://localhost/api/quotation/BTC' \
  --header 'Content-Type: application/json' \
  --data '{"type":"crypto"}'
```
4. Listar historico:
```bash
curl --request GET --url 'http://localhost/api/quotations?symbol=BTC&per_page=5'
```
5. Verificar comandos operacionais:
```bash
php artisan quotations:collect --symbol=BTC --dry-run
php artisan quotations:reconcile --dry-run
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
5. Sintoma: `sessions` table does not exist.
   Acao: executar migrations (`php artisan migrate` ou `./vendor/bin/sail artisan migrate`).

## Nota de escopo

Esta branch remove da superficie publica os fluxos de alertas e carteiras.
