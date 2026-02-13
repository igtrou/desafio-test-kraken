# Architecture Guidelines

Este documento define regras de organizacao de pastas e dependencia para manter consistencia arquitetural no projeto.
Indice de navegacao: [`README.md`](README.md).
Fluxos executaveis detalhados: [`ARCHITECTURE.md`](ARCHITECTURE.md).

## Objetivo

1. Reduzir ambiguidade sobre onde cada classe deve ficar.
2. Evitar acoplamento acidental entre camadas.
3. Preservar evolucao com baixo risco de regressao estrutural.

## Estrutura oficial

```text
app/
  Http/{Controllers,Requests,Resources,Middleware}
  Actions/{Quotations,Auth,Dashboard}
  Services/{Quotations,Auth,Dashboard}
  Domain/{Contracts,MarketData,Quotations,Auth,Audit,Exceptions}
  Infrastructure/{MarketData,Quotations,Auth,Config,Console,Observability,Audit}
  Data
  Models
  OpenApi/{Paths}
```

## Responsabilidade por camada

### `Http`

1. Borda HTTP: validacao, normalizacao, serializacao e codigo de status.
2. Deve delegar regra de negocio para `Actions`.
3. Controllers devem permanecer finos.

### `Actions`

1. Casos de uso orientados a fluxo.
2. Coordenam `Services` e mapeiam para DTOs (`Data`).
3. Nao devem conter I/O tecnico direto.

### `Services`

1. Orquestracao reutilizavel de aplicacao.
2. Regra de negocio de processo (nao de framework).
3. Acessam integracoes via portas (`Domain\Contracts`).

### `Domain`

1. Regras de negocio puras e contratos.
2. Sem dependencia de framework, HTTP, Eloquent ou adaptadores.

### `Infrastructure`

1. Implementa portas do dominio.
2. Encapsula framework, SDK, HTTP client, cache, filesystem, DB, logs.

### `Data`

1. DTOs de entrada/saida da camada de aplicacao.

### `Models`

1. Modelos Eloquent e relacoes de persistencia.

### `OpenApi`

1. Define contrato HTTP (documentacao de endpoints, schemas e exemplos).
2. Nao contem regra de negocio nem substitui validacao em `Http/Requests`.

## Regras de dependencia (normativas)

Fluxo alvo:

1. `Http -> Actions -> Services -> Domain`.
2. `Infrastructure -> Domain\Contracts` (implementa portas).
3. `Domain` nao depende de `Http`, `Actions`, `Services`, `Infrastructure`, `Models`, `Data`.

### Regra especifica para `Services`

No estado atual, validado por `ServiceLayerDependencyTest`:

1. Imports internos permitidos em `Services`:
   1. `App\Domain\...`
   2. `App\Services\...`
2. Imports internos proibidos em `Services`:
   1. `App\Infrastructure\...`
   2. `App\Http\...`
   3. `App\Actions\...`
   4. `App\Models\...`
   5. `App\Data\...`

Implicacao pratica:

1. `Service` deve depender da porta (`Domain\Contracts`) e nao do adapter concreto.
2. O binding porta -> adapter ocorre no container (`AppServiceProvider`).

## Convencoes garantidas por testes

1. `Actions`:
   1. arquivos e classes com sufixo `Action`;
   2. apenas `__invoke` como metodo publico (alem de `__construct`);
   3. sem dependencia direta de `Domain`;
   4. sem dependencia de `Illuminate`/`Laravel`;
   5. sem dependencia de `Models`.
2. `Controllers`:
   1. devem depender de `Actions`;
   2. nao devem depender diretamente de `Services` ou `Models`.
3. `Domain`:
   1. isolamento contra camadas externas.

Referencias:

1. `tests/Unit/Architecture/ActionConventionTest.php`
2. `tests/Unit/Architecture/ActionLayerDependencyTest.php`
3. `tests/Unit/Architecture/ActionModelDependencyTest.php`
4. `tests/Unit/Architecture/ControllerLayerDependencyTest.php`
5. `tests/Unit/Architecture/DomainLayerDependencyTest.php`
6. `tests/Unit/Architecture/ServiceLayerDependencyTest.php`

## Como adicionar uma integracao externa (padrao porta/adaptador)

1. Definir interface em `app/Domain/Contracts`.
2. Consumir interface no `Service` (nunca adapter concreto).
3. Implementar adapter em `app/Infrastructure/...`.
4. Registrar binding em `AppServiceProvider::registerPorts()`.
5. Cobrir com teste unitario do service + teste do adapter.

## Como adicionar um caso de uso novo

1. Criar `Action` no contexto correto (`Actions/Quotations`, `Actions/Auth`, etc.).
2. Implementar/estender `Service` para regra de processo.
3. Encapsular regra pura em `Domain` quando aplicavel.
4. Adaptar controller/command apenas para entrada/saida.
5. Se houver I/O novo, criar porta + adapter.

## Anti-patterns proibidos

1. Controller chamando provider externo, query Eloquent ou regra de negocio.
2. Action com acesso direto a `Model`, `DB`, `Http::`, `Cache::`, `Artisan::`.
3. Service importando adapter concreto de `Infrastructure`.
4. Domain usando helpers/facades/framework.
5. Infrastructure decidindo regra de negocio central que deveria estar em `Domain`/`Services`.

## Excecoes controladas

1. Adaptadores de `Infrastructure` podem usar facades/framework.
2. Middleware/Controllers podem usar framework por serem borda de entrada.
3. Se uma regra de dependencia precisar mudar, o teste de arquitetura deve ser ajustado no mesmo PR e a motivacao deve ser explicita.

## Checklist de PR (arquitetura)

1. Classe nova esta na pasta correta por responsabilidade?
2. Dependencias respeitam fluxo `Http -> Actions -> Services -> Domain`?
3. Integracoes tecnicas passam por porta/adaptador?
4. `Action` permanece fina e com `__invoke` unico?
5. `composer run test:architecture` executado e verde?
6. `docs/ARCHITECTURE.md` foi atualizado se fluxo mudou?

## Comando de validacao

```bash
composer run test:architecture
```
