# Guia de Testes

Este documento padroniza como rodar, escrever e revisar testes automatizados.
Este guia cobre convencoes e comandos executaveis para a suite atual.

## Escopo e stack

1. Framework de testes: PHPUnit 11 (`php artisan test`).
2. Suites ativas: `tests/Unit` e `tests/Feature` (definidas em `phpunit.xml`).
3. Guardas arquiteturais: `tests/Unit/Architecture`.
4. Banco de teste default: SQLite em memoria (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` via `phpunit.xml`).

## Pre-requisitos

1. Dependencias instaladas:
```bash
composer install
```
2. Ambiente de app preparado (quando necessario para testes de feature que usam schema):
```bash
php artisan key:generate
php artisan migrate
```
3. Opcional com Sail:
```bash
./vendor/bin/sail artisan test
```

## Comandos recomendados

### Execucao completa

```bash
php artisan test
```

### Unit e Feature separadas

```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

### Guardas de arquitetura

```bash
composer run test:architecture
```

### Pipeline local semelhante ao CI

```bash
composer run test:ci
```

## Filtros uteis para produtividade

1. Rodar um arquivo especifico:
```bash
php artisan test tests/Feature/QuotationCollectorCommandTest.php
```
2. Rodar uma pasta:
```bash
php artisan test tests/Unit/Services
```
3. Rodar por nome do teste/metodo:
```bash
php artisan test --filter=collect_command
```
4. Parar na primeira falha:
```bash
php artisan test --stop-on-failure
```

## Convencoes do projeto

1. `tests/Feature`: fluxo HTTP, comandos Artisan, middleware e integracao entre camadas.
2. `tests/Unit`: regras de dominio, services e infraestrutura isolada.
3. `tests/Unit/Architecture`: regras de dependencia e convencoes estruturais.
4. Nomeie metodos de teste com comportamento esperado claro (ex.: `test_collect_command_dry_run_does_not_persist`).
5. Use `RefreshDatabase` quando houver escrita/leitura de banco.
6. Nao dependa de rede externa real: use `Http::fake()` para providers e integracoes HTTP.
7. Controle tempo em cenarios sensiveis com `CarbonImmutable::setTestNow()` e limpe no `tearDown()`.
8. Mantenha Arrange/Act/Assert legivel, evitando logica de dominio dentro do teste.

## Checklist pre-PR

1. Rodar pelo menos o escopo alterado (arquivo/pasta) antes de subir.
2. Rodar suite completa quando houver mudanca em fluxo central:
```bash
php artisan test
```
3. Validar padrao de codigo:
```bash
./vendor/bin/pint --test
```
4. Se alterou contratos de arquitetura/camadas, validar:
```bash
composer run test:architecture
```
5. Atualizar este guia quando novos comandos, suites ou convencoes forem introduzidos.

## Relacao com outros documentos

1. Operacao diaria e runbook: [`OPERATIONS.md`](OPERATIONS.md).
2. Fluxo arquitetural e responsabilidades por camada: [`ARCHITECTURE.md`](ARCHITECTURE.md).
3. Padrao de manutencao da documentacao: [`DOCUMENTATION_GUIDE.md`](DOCUMENTATION_GUIDE.md).
