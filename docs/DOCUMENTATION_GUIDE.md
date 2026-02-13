# Guia de Manutencao da Documentacao

Este arquivo define padroes para manter a documentacao util, atual e facil de navegar.
Hub principal: [`README.md`](README.md).

## Objetivo

1. Reduzir inconsistencias entre codigo e docs.
2. Diminuir tempo de onboarding.
3. Tornar revisao de PR mais previsivel.

## Padrao minimo por documento

1. Comece com objetivo claro em 1-2 linhas.
2. Declare o escopo coberto e o que fica fora do escopo.
3. Inclua exemplos executaveis (comandos/curl) quando houver fluxo operacional.
4. Referencie documentos relacionados no final.
5. Evite duplicar texto grande; prefira linkar para a fonte principal.

## Estrutura recomendada

Use este esqueleto quando criar um novo `.md`:

```md
# Titulo

Objetivo curto do documento.

## Quando usar
1. Situacao A
2. Situacao B

## Conteudo principal
...

## Checklist de atualizacao
1. Item critico 1
2. Item critico 2

## Relacao com outros documentos
1. Link 1
2. Link 2
```

## Matriz de impacto (mudou no codigo, atualize aqui)

| Mudanca no codigo | Documentos a revisar |
| --- | --- |
| Rotas, payloads, status HTTP, autenticacao | [`API.md`](API.md), [`API_FLOW_MAP.md`](API_FLOW_MAP.md), [`../README.md`](../README.md) |
| Fluxo de camada, responsabilidade de Action/Service/Domain/Infrastructure | [`API_FLOW_MAP.md`](API_FLOW_MAP.md), [`ARCHITECTURE.md`](ARCHITECTURE.md), [`ARCHITECTURE_GUIDELINES.md`](ARCHITECTURE_GUIDELINES.md), [`../README.md`](../README.md) |
| Comandos operacionais, scheduler, variaveis `.env`, logs | [`OPERATIONS.md`](OPERATIONS.md), [`../README.md`](../README.md) |
| Suite de testes, convencoes de escrita de testes, pipeline de qualidade | [`TESTING.md`](TESTING.md) |
| Setup local/Sail, comandos de bootstrap, stack | [`../README.md`](../README.md), [`README.md`](README.md) |

## Checklist de PR (documentacao)

1. Os exemplos de comando foram executados ou validados no contexto atual.
2. Os links relativos foram revisados.
3. O documento alterado aparece no hub de docs quando necessario.
4. Nao existe informacao conflitante entre `README` raiz e `docs/*`.
5. Mudancas de API/arquitetura/operacao/testes atualizaram os arquivos corretos da matriz acima.

## Antipadroes para evitar

1. Copiar o mesmo bloco de texto em varios documentos.
2. Registrar comportamento antigo sem marcar claramente que esta desatualizado.
3. Incluir comando que nao funciona no ambiente padrao do projeto.
4. Descrever regra de negocio sem citar a camada ou classe responsavel.
