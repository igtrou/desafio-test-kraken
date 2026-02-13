# Hub de Documentacao

Este diretorio concentra a documentacao tecnica do projeto. Use este arquivo como ponto de entrada para onboarding, operacao e manutencao.

## Mapa dos documentos

| Documento | Quando ler | Conteudo principal |
| --- | --- | --- |
| [`../README.md`](../README.md) | Inicio do projeto | Setup rapido (Sail/local), comandos base, visao geral de escopo e arquitetura. |
| [`API.md`](API.md) | Integracao cliente/API | Endpoints, payloads, autenticacao, status HTTP e `error_code`. |
| [`API_FLOW_MAP.md`](API_FLOW_MAP.md) | Visualizar fluxo tecnico por rota | Diagramas Mermaid de todas as rotas de `routes/api.php`, com chain `Http -> Actions -> Services -> Domain -> Infrastructure`. |
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | Entender arquitetura executavel | Estilo arquitetural, mapa de portas/adaptadores, fluxos ponta a ponta HTTP/Console/Dashboard/Auth e guardrails automatizados. |
| [`ARCHITECTURE_GUIDELINES.md`](ARCHITECTURE_GUIDELINES.md) | Evoluir o codigo sem quebrar padrao | Regras normativas por camada, matriz de dependencia, anti-patterns e checklist de PR arquitetural. |
| [`OPERATIONS.md`](OPERATIONS.md) | Operacao diaria e suporte | Variaveis de ambiente, runbook, scheduler, logs e troubleshooting. |
| [`TESTING.md`](TESTING.md) | Execucao e convencoes de teste | Comandos da suite, filtros, padroes e checklist pre-PR. |
| [`DOCUMENTATION_GUIDE.md`](DOCUMENTATION_GUIDE.md) | Manter docs consistentes | Padrao de escrita, matriz de impacto e checklist de PR. |
| [`postman/financial-quotation-api.postman_collection.json`](postman/financial-quotation-api.postman_collection.json) | Teste manual rapido | Colecao Postman com rotas da API. |

## Trilhas recomendadas por perfil

### Backend

1. [`../README.md`](../README.md)
2. [`API.md`](API.md)
3. [`API_FLOW_MAP.md`](API_FLOW_MAP.md)
4. [`ARCHITECTURE.md`](ARCHITECTURE.md)
5. [`ARCHITECTURE_GUIDELINES.md`](ARCHITECTURE_GUIDELINES.md)
6. [`OPERATIONS.md`](OPERATIONS.md)

### Arquitetura (aprofundado)

1. [`ARCHITECTURE.md`](ARCHITECTURE.md)
2. [`ARCHITECTURE_GUIDELINES.md`](ARCHITECTURE_GUIDELINES.md)
3. `tests/Unit/Architecture/*`
4. `app/Providers/AppServiceProvider.php`

### QA / testes manuais

1. [`API.md`](API.md)
2. [`postman/financial-quotation-api.postman_collection.json`](postman/financial-quotation-api.postman_collection.json)
3. [`TESTING.md`](TESTING.md)
4. [`OPERATIONS.md`](OPERATIONS.md)

### Operacao / suporte

1. [`OPERATIONS.md`](OPERATIONS.md)
2. [`API.md`](API.md)
3. [`ARCHITECTURE.md`](ARCHITECTURE.md)

## Mapa por objetivo

| Objetivo | Documento principal | Documento complementar |
| --- | --- | --- |
| Subir o projeto rapidamente | [`../README.md`](../README.md) | [`OPERATIONS.md`](OPERATIONS.md) |
| Entender endpoints e contratos | [`API.md`](API.md) | [`postman/financial-quotation-api.postman_collection.json`](postman/financial-quotation-api.postman_collection.json) |
| Entender fluxo de execucao por rota API | [`API_FLOW_MAP.md`](API_FLOW_MAP.md) | [`ARCHITECTURE.md`](ARCHITECTURE.md) |
| Alterar arquitetura sem quebrar padrao | [`ARCHITECTURE_GUIDELINES.md`](ARCHITECTURE_GUIDELINES.md) | [`ARCHITECTURE.md`](ARCHITECTURE.md) |
| Executar e evoluir testes | [`TESTING.md`](TESTING.md) | - |
| Validar operacao diaria e scheduler | [`OPERATIONS.md`](OPERATIONS.md) | [`ARCHITECTURE.md`](ARCHITECTURE.md) |
| Atualizar docs durante PR | [`DOCUMENTATION_GUIDE.md`](DOCUMENTATION_GUIDE.md) | [`README.md`](README.md) |

## Checklist para atualizar documentacao

1. Atualize primeiro o contrato de API em [`API.md`](API.md) quando houver mudanca de rota, payload ou status.
2. Ajuste fluxo e responsabilidade em [`ARCHITECTURE.md`](ARCHITECTURE.md) quando houver mudanca de Action/Service/Infrastructure.
3. Revise `README` raiz quando setup, comandos base ou stack mudarem.
4. Revise [`OPERATIONS.md`](OPERATIONS.md) quando variaveis `.env`, scheduler ou runbook operacional mudarem.
5. Revise [`TESTING.md`](TESTING.md) quando comandos/suites/convencoes de testes mudarem.
6. Revise [`DOCUMENTATION_GUIDE.md`](DOCUMENTATION_GUIDE.md) quando o processo de manutencao documental mudar.
7. Valide links relativos e exemplos de comandos antes de finalizar o commit.

## Convencoes de escrita

1. Declare objetivo e publico-alvo nas primeiras linhas.
2. Prefira exemplos executaveis completos (`curl`, `artisan`, `composer`).
3. Use linguagem objetiva e alinhada com os nomes reais de classes, comandos e rotas.
4. Sempre adicione referencia cruzada para este hub (`docs/README.md`) quando criar um novo documento.

## Fluxo rapido de revisao em PR

1. Identifique o tipo de mudanca (API, arquitetura, operacao, testes ou setup).
2. Use a matriz de impacto em [`DOCUMENTATION_GUIDE.md`](DOCUMENTATION_GUIDE.md).
3. Atualize os arquivos necessarios e valide links relativos.
4. Garanta consistencia entre `README` raiz e `docs/*`.
