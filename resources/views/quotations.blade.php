<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Central de Cotações</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --bg-0: #f6fbff;
            --bg-1: #eef5ff;
            --bg-2: #ffffff;
            --ink: #0f1f2f;
            --muted: #4c6278;
            --line: #d7e5f3;
            --line-strong: #bfd7eb;
            --accent: #0ea5a3;
            --accent-strong: #0f766e;
            --accent-soft: rgba(14, 165, 163, 0.12);
            --warning: #b45309;
            --warning-soft: rgba(245, 158, 11, 0.18);
            --error: #be123c;
            --error-soft: rgba(225, 29, 72, 0.15);
            --ok: #166534;
            --ok-soft: rgba(34, 197, 94, 0.16);
            --surface-shadow: 0 12px 34px rgba(15, 23, 42, 0.08);
        }

        body {
            font-family: "Sora", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 500px at -10% -20%, rgba(14, 165, 163, 0.18), transparent),
                radial-gradient(1000px 600px at 110% -20%, rgba(59, 130, 246, 0.12), transparent),
                linear-gradient(180deg, var(--bg-0), var(--bg-1));
        }

        .panel {
            background: color-mix(in srgb, var(--bg-2) 92%, transparent);
            border: 1px solid var(--line);
            box-shadow: var(--surface-shadow);
        }

        .metric {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.96), rgba(246, 250, 255, 0.94));
            border: 1px solid var(--line);
        }

        .chip {
            border: 1px solid var(--line-strong);
            background: #fff;
            color: #20415f;
            transition: all .2s ease;
        }

        .chip:hover {
            border-color: var(--accent);
            transform: translateY(-1px);
        }

        .chip[aria-pressed="true"] {
            background: var(--accent-soft);
            border-color: var(--accent-strong);
            color: var(--accent-strong);
        }

        .input-base {
            border: 1px solid var(--line-strong);
            background: #fff;
            color: var(--ink);
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .input-base:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(14, 165, 163, 0.14);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0ea5a3, #0f766e);
            color: #fff;
            border: 1px solid transparent;
        }

        .btn-primary:hover {
            filter: brightness(1.05);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #111827;
            border: 1px solid transparent;
        }

        .btn-warning:hover {
            filter: brightness(1.04);
        }

        .btn-danger {
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #be123c;
        }

        .btn-danger:hover {
            border-color: #fda4af;
            background: #ffe4e6;
        }

        .btn-ghost {
            border: 1px solid var(--line-strong);
            background: #fff;
            color: #27445f;
        }

        .btn-ghost:hover {
            border-color: #8db3d5;
            background: #f7fbff;
        }

        .btn:disabled {
            opacity: .65;
            cursor: not-allowed;
            transform: none;
        }

        .status-dot {
            width: .55rem;
            height: .55rem;
            border-radius: 9999px;
        }

        .active-chip {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
        }

        .shortcut-key {
            border: 1px solid #cbd5e1;
            background: linear-gradient(180deg, #fff, #f8fafc);
            color: #1e293b;
            box-shadow: 0 1px 0 rgba(15, 23, 42, 0.08);
        }

        .skeleton {
            position: relative;
            overflow: hidden;
            border-radius: 0.55rem;
            background: #e2e8f0;
        }

        .skeleton::after {
            content: '';
            position: absolute;
            inset: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, rgba(226, 232, 240, 0), rgba(255, 255, 255, 0.9), rgba(226, 232, 240, 0));
            animation: shimmer 1.2s infinite;
        }

        .layout-chip {
            border: 1px solid var(--line-strong);
            background: #fff;
            color: #1f3b53;
            transition: all .2s ease;
        }

        .layout-chip:hover {
            border-color: var(--accent);
            transform: translateY(-1px);
        }

        .layout-chip[aria-pressed="true"] {
            border-color: var(--accent-strong);
            background: var(--accent-soft);
            color: var(--accent-strong);
        }

        .layout-toggle {
            border: 1px dashed #b6cadf;
            background: #f8fbff;
            color: #2f4d67;
        }

        .layout-toggle:hover {
            border-color: var(--accent);
            background: #f0fbfb;
        }

        .layout-toggle[data-hidden="true"] {
            background: #fff;
            border-style: solid;
            border-color: #cbd5e1;
            color: #475569;
        }

        .dashboard-workspace.one-column {
            grid-template-columns: minmax(0, 1fr);
        }

        .dashboard-workspace.two-columns {
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 1024px) {
            .dashboard-workspace.two-columns {
                grid-template-columns: minmax(0, 360px) minmax(0, 1fr);
            }
        }

        .is-collapsed {
            display: none !important;
        }

        .reveal {
            opacity: 0;
            transform: translateY(8px);
            animation: rise .45s ease forwards;
        }

        .reveal-delay-1 { animation-delay: .05s; }
        .reveal-delay-2 { animation-delay: .10s; }
        .reveal-delay-3 { animation-delay: .15s; }

        @keyframes rise {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shimmer {
            to {
                transform: translateX(100%);
            }
        }

        @media (max-width: 768px) {
            .scroll-shadow {
                box-shadow: inset -14px 0 12px -14px rgba(3, 7, 18, 0.24);
            }
        }
    </style>
</head>
<body class="min-h-screen">
    <main id="dashboard-root" class="mx-auto max-w-7xl px-4 py-6 md:px-6 md:py-8">
        <header class="panel reveal rounded-3xl p-5 md:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs uppercase tracking-[0.28em] text-slate-500">Finance API Control Center</p>
                    <h1 class="text-2xl font-bold leading-tight text-slate-900 md:text-4xl">Dashboard de Cotações com foco em decisão</h1>
                    <p class="max-w-3xl text-sm text-slate-600 md:text-base">
                        Consulte ativos em tempo real, persista o histórico e acompanhe tendência com filtros, paginação e feedback operacional claros.
                    </p>
                    <nav class="flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="rounded-full border border-teal-300 bg-teal-50 px-3 py-1.5 text-teal-800">Cotações</span>
                        <a href="/dashboard/operations" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-600 hover:border-teal-300 hover:text-teal-700">Operações</a>
                    </nav>
                    <div class="flex flex-wrap gap-2">
                        <button id="btn-refresh" class="btn btn-ghost rounded-xl px-4 py-2 text-sm font-medium">Atualizar histórico</button>
                        <p class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-500">Suporta `awesome_api`, `alpha_vantage`, `yahoo_finance` e `stooq`</p>
                    </div>
                </div>
                <div class="w-full rounded-2xl border border-slate-200 bg-white/90 p-4 lg:max-w-sm">
                    <div class="space-y-2 text-sm text-slate-600">
                        <p id="updated-at">Última atualização: -</p>
                        <p id="history-status" class="flex items-center gap-2">
                            <span id="history-status-dot" class="status-dot bg-slate-400"></span>
                            <span id="history-status-text">Status: aguardando primeira consulta</span>
                        </p>
                        <p id="latency-status">Última latência: -</p>
                    </div>
                </div>
            </div>
        </header>

        <section class="panel reveal reveal-delay-1 mt-6 rounded-3xl p-4 md:p-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-600">Estratégia de layout</h2>
                    <p id="layout-hint" class="mt-1 text-sm text-slate-600">Modo atual: monitor completo.</p>
                </div>
                <div id="layout-presets" class="flex flex-wrap gap-2">
                    <button id="btn-layout-monitor" type="button" data-layout="monitor" aria-pressed="true" class="layout-chip rounded-full px-3 py-1.5 text-xs font-semibold">Monitor</button>
                    <button id="btn-layout-analysis" type="button" data-layout="analysis" aria-pressed="false" class="layout-chip rounded-full px-3 py-1.5 text-xs font-semibold">Análise</button>
                    <button id="btn-layout-query" type="button" data-layout="query" aria-pressed="false" class="layout-chip rounded-full px-3 py-1.5 text-xs font-semibold">Consulta</button>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2" id="layout-toggles">
                <button id="btn-toggle-kpis" type="button" data-panel="kpis" data-label="KPIs" data-hidden="false" class="layout-toggle rounded-lg px-3 py-1.5 text-xs font-semibold">Esconder KPIs</button>
                <button id="btn-toggle-filters" type="button" data-panel="filters" data-label="Filtros" data-hidden="false" class="layout-toggle rounded-lg px-3 py-1.5 text-xs font-semibold">Esconder filtros</button>
                <button id="btn-toggle-snapshot" type="button" data-panel="snapshot" data-label="Snapshot" data-hidden="false" class="layout-toggle rounded-lg px-3 py-1.5 text-xs font-semibold">Esconder snapshot</button>
                <button id="btn-toggle-chart" type="button" data-panel="chart" data-label="Gráfico" data-hidden="false" class="layout-toggle rounded-lg px-3 py-1.5 text-xs font-semibold">Esconder gráfico</button>
                <button id="btn-toggle-history" type="button" data-panel="history" data-label="Histórico" data-hidden="false" class="layout-toggle rounded-lg px-3 py-1.5 text-xs font-semibold">Esconder histórico</button>
            </div>
        </section>

        <section id="section-kpis" class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="metric reveal reveal-delay-1 rounded-2xl p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Registros totais</p>
                <p id="kpi-total" class="mt-2 text-3xl font-semibold text-slate-900">0</p>
                <p id="kpi-total-detail" class="mt-1 text-xs text-slate-500">Base filtrada atual.</p>
            </article>
            <article class="metric reveal reveal-delay-1 rounded-2xl p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Ativos únicos</p>
                <p id="kpi-assets" class="mt-2 text-3xl font-semibold text-slate-900">0</p>
                <p class="mt-1 text-xs text-slate-500">Diversidade do histórico visível.</p>
            </article>
            <article class="metric reveal reveal-delay-2 rounded-2xl p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Preço médio</p>
                <p id="kpi-average" class="mt-2 text-3xl font-semibold text-slate-900">-</p>
                <p class="mt-1 text-xs text-slate-500">Média da página atual.</p>
            </article>
            <article class="metric reveal reveal-delay-2 rounded-2xl p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Último ativo</p>
                <p id="kpi-latest" class="mt-2 text-3xl font-semibold text-slate-900">-</p>
                <p id="kpi-trend" class="mt-1 text-xs text-slate-500">Sem tendência calculável.</p>
            </article>
        </section>

        <section id="section-workspace" class="mt-6 grid gap-6 dashboard-workspace two-columns">
            <article id="panel-filters" class="panel reveal reveal-delay-2 rounded-3xl p-5 md:p-6">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-slate-900">Consulta e filtros</h2>
                    <p class="mt-1 text-sm text-slate-600">Fluxo: buscar prévia, validar e persistir quando fizer sentido.</p>
                </div>

                <div class="mb-4 space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Símbolos rápidos</p>
                    <div id="quick-symbols" class="flex flex-wrap gap-2">
                        <button type="button" data-symbol="BTC" class="chip rounded-full px-3 py-1.5 text-xs font-semibold">BTC</button>
                        <button type="button" data-symbol="ETH" class="chip rounded-full px-3 py-1.5 text-xs font-semibold">ETH</button>
                        <button type="button" data-symbol="MSFT" class="chip rounded-full px-3 py-1.5 text-xs font-semibold">MSFT</button>
                        <button type="button" data-symbol="AAPL" class="chip rounded-full px-3 py-1.5 text-xs font-semibold">AAPL</button>
                        <button type="button" data-symbol="USD-BRL" class="chip rounded-full px-3 py-1.5 text-xs font-semibold">USD-BRL</button>
                        <button type="button" data-symbol="PETR4.SA" class="chip rounded-full px-3 py-1.5 text-xs font-semibold">PETR4.SA</button>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="space-y-1 text-sm sm:col-span-2">
                        <span class="text-slate-600">Símbolo</span>
                        <input id="symbol" value="BTC" autocomplete="off" class="input-base w-full rounded-xl px-3 py-2.5" placeholder="Ex.: BTC, MSFT, USD-BRL">
                    </label>

                    <label class="space-y-1 text-sm">
                        <span class="text-slate-600">Provider</span>
                        <select id="provider" class="input-base w-full rounded-xl px-3 py-2.5">
                            <option value="">default</option>
                            <option value="awesome_api">awesome_api</option>
                            <option value="alpha_vantage">alpha_vantage</option>
                            <option value="yahoo_finance">yahoo_finance</option>
                            <option value="stooq">stooq</option>
                        </select>
                    </label>

                    <label class="space-y-1 text-sm">
                        <span class="text-slate-600">Tipo</span>
                        <select id="type" class="input-base w-full rounded-xl px-3 py-2.5">
                            <option value="">auto</option>
                            <option value="stock">stock</option>
                            <option value="crypto">crypto</option>
                            <option value="currency">currency</option>
                        </select>
                    </label>

                    <label class="space-y-1 text-sm">
                        <span class="text-slate-600">Data inicial</span>
                        <input id="date-from" type="date" class="input-base w-full rounded-xl px-3 py-2.5">
                    </label>

                    <label class="space-y-1 text-sm">
                        <span class="text-slate-600">Data final</span>
                        <input id="date-to" type="date" class="input-base w-full rounded-xl px-3 py-2.5">
                    </label>

                    <label class="space-y-1 text-sm sm:col-span-2">
                        <span class="text-slate-600">Itens por página</span>
                        <input id="per-page" type="number" min="1" max="100" value="20" class="input-base w-full rounded-xl px-3 py-2.5">
                    </label>

                    <label class="space-y-1 text-sm sm:col-span-2">
                        <span class="text-slate-600">API Token (opcional)</span>
                        <div class="relative">
                            <input id="token" type="password" placeholder="Bearer token" class="input-base w-full rounded-xl px-3 py-2.5 pr-24">
                            <button id="btn-toggle-token" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">Mostrar</button>
                        </div>
                        <p id="token-permission-hint" class="text-xs text-slate-500">Sem token: modo leitura (exclusão oculta).</p>
                    </label>
                </div>

                <div class="mt-4 space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Atalhos de período</p>
                    <div id="quick-ranges" class="flex flex-wrap gap-2">
                        <button type="button" data-range="1" class="chip rounded-full px-3 py-1.5 text-xs font-semibold">Hoje</button>
                        <button type="button" data-range="7" class="chip rounded-full px-3 py-1.5 text-xs font-semibold">7 dias</button>
                        <button type="button" data-range="30" class="chip rounded-full px-3 py-1.5 text-xs font-semibold">30 dias</button>
                        <button type="button" data-range="90" class="chip rounded-full px-3 py-1.5 text-xs font-semibold">90 dias</button>
                        <button type="button" data-range="all" class="chip rounded-full px-3 py-1.5 text-xs font-semibold">Tudo</button>
                    </div>
                </div>

                <div class="mt-5 grid gap-2 sm:grid-cols-2">
                    <button id="btn-fetch" class="btn btn-primary rounded-xl px-4 py-2.5 text-sm font-semibold">Buscar prévia</button>
                    <button id="btn-save" class="btn btn-warning rounded-xl px-4 py-2.5 text-sm font-semibold">Buscar e salvar</button>
                    <button id="btn-filter" class="btn btn-ghost rounded-xl px-4 py-2.5 text-sm font-semibold">Aplicar filtros</button>
                    <button id="btn-reset" class="btn btn-ghost rounded-xl px-4 py-2.5 text-sm font-semibold">Limpar filtros</button>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Filtros ativos</p>
                        <button id="btn-clear-active-filters" type="button" class="btn btn-ghost rounded-lg px-3 py-1 text-xs font-semibold">Limpar ativos</button>
                    </div>
                    <div id="active-filters" class="mt-2 flex flex-wrap gap-2 text-xs"></div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Atalhos</p>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1">
                            <span class="shortcut-key rounded px-1.5 py-0.5 font-semibold">/</span>
                            focar símbolo
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1">
                            <span class="shortcut-key rounded px-1.5 py-0.5 font-semibold">Enter</span>
                            buscar prévia
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1">
                            <span class="shortcut-key rounded px-1.5 py-0.5 font-semibold">Ctrl + Enter</span>
                            buscar e salvar
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1">
                            <span class="shortcut-key rounded px-1.5 py-0.5 font-semibold">Ctrl + Shift + R</span>
                            recarregar histórico
                        </span>
                    </div>
                </div>

                <div id="validation-hint" class="mt-4 hidden rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700" role="alert"></div>
                <div id="notice" class="mt-4 hidden rounded-xl border px-3 py-2 text-sm" role="status" aria-live="polite"></div>
            </article>

            <div id="panel-insights" class="space-y-6">
                <article id="panel-snapshot" class="panel reveal reveal-delay-2 rounded-3xl p-5 md:p-6">
                    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Snapshot da cotação</h2>
                            <p class="text-sm text-slate-600">Visão rápida da última consulta com contexto de mercado.</p>
                        </div>
                        <p id="quote-context" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">Sem snapshot</p>
                    </div>

                    <div id="quote-placeholder" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        Faça uma consulta para exibir o snapshot deste ativo.
                    </div>

                    <div id="quote-result" class="hidden rounded-2xl border border-slate-200 bg-white p-5"></div>
                </article>

                <article id="panel-chart" class="panel reveal reveal-delay-3 rounded-3xl p-5 md:p-6">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Evolução de preço</h2>
                            <p class="text-sm text-slate-600">Baseado no histórico persistido e filtros atuais.</p>
                        </div>
                        <p id="chart-hint" class="text-xs text-slate-500"></p>
                    </div>
                    <div class="relative h-72 rounded-2xl border border-slate-200 bg-white p-3">
                        <canvas id="price-chart"></canvas>
                        <div id="chart-overlay" class="hidden absolute inset-0 grid place-items-center rounded-2xl bg-white/75 text-sm font-medium text-slate-600">Carregando histórico...</div>
                    </div>
                </article>
            </div>
        </section>

        <section id="panel-history" class="panel reveal reveal-delay-3 mt-6 rounded-3xl p-5 md:p-6">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Histórico de cotações</h2>
                    <p id="pagination-info" class="text-sm text-slate-600">Sem dados carregados.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button id="btn-delete-filtered" class="btn btn-danger hidden rounded-xl px-3 py-2 text-xs font-semibold">Excluir filtradas</button>
                    <button id="btn-page-first" class="btn btn-ghost rounded-xl px-3 py-2 text-xs font-semibold">Primeira</button>
                    <button id="btn-page-prev" class="btn btn-ghost rounded-xl px-3 py-2 text-xs font-semibold">Anterior</button>
                    <span id="page-indicator" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600">Página 1 de 1</span>
                    <button id="btn-page-next" class="btn btn-ghost rounded-xl px-3 py-2 text-xs font-semibold">Próxima</button>
                    <button id="btn-page-last" class="btn btn-ghost rounded-xl px-3 py-2 text-xs font-semibold">Última</button>
                </div>
            </div>

            <div id="history-table-shell" class="scroll-shadow overflow-x-auto rounded-2xl border border-slate-200 bg-white" aria-busy="false">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 bg-slate-50 text-left text-xs uppercase tracking-[0.13em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Símbolo</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Preço</th>
                            <th class="px-4 py-3">Moeda</th>
                            <th class="px-4 py-3">Fonte</th>
                            <th class="px-4 py-3">Quoted at</th>
                            <th class="px-4 py-3">Persistido em</th>
                            <th id="history-actions-header" class="px-4 py-3 text-right hidden">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="history-rows" class="divide-y divide-slate-100" aria-live="polite"></tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        const defaultPanelVisibility = Object.freeze({
            kpis: true,
            filters: true,
            snapshot: true,
            chart: true,
            history: true,
        });

        const state = {
            chart: null,
            history: [],
            meta: null,
            currentPage: 1,
            loadingHistory: false,
            canDeleteQuotations: false,
            authUser: null,
            tokenChecked: false,
            lastAppliedFilters: '',
            layoutMode: 'monitor',
            panelVisibility: { ...defaultPanelVisibility },
        };

        const storageKey = 'quotation_dashboard_preferences_v2';
        const locale = 'pt-BR';
        let tokenPermissionCheckTimeout = null;

        const elements = {
            layoutHint: document.getElementById('layout-hint'),
            layoutPresets: document.getElementById('layout-presets'),
            layoutToggles: document.getElementById('layout-toggles'),
            btnLayoutMonitor: document.getElementById('btn-layout-monitor'),
            btnLayoutAnalysis: document.getElementById('btn-layout-analysis'),
            btnLayoutQuery: document.getElementById('btn-layout-query'),
            btnToggleKpis: document.getElementById('btn-toggle-kpis'),
            btnToggleFilters: document.getElementById('btn-toggle-filters'),
            btnToggleSnapshot: document.getElementById('btn-toggle-snapshot'),
            btnToggleChart: document.getElementById('btn-toggle-chart'),
            btnToggleHistory: document.getElementById('btn-toggle-history'),
            sectionKpis: document.getElementById('section-kpis'),
            sectionWorkspace: document.getElementById('section-workspace'),
            panelFilters: document.getElementById('panel-filters'),
            panelInsights: document.getElementById('panel-insights'),
            panelSnapshot: document.getElementById('panel-snapshot'),
            panelChart: document.getElementById('panel-chart'),
            panelHistory: document.getElementById('panel-history'),
            symbol: document.getElementById('symbol'),
            provider: document.getElementById('provider'),
            type: document.getElementById('type'),
            dateFrom: document.getElementById('date-from'),
            dateTo: document.getElementById('date-to'),
            perPage: document.getElementById('per-page'),
            token: document.getElementById('token'),
            tokenPermissionHint: document.getElementById('token-permission-hint'),
            notice: document.getElementById('notice'),
            validationHint: document.getElementById('validation-hint'),
            activeFilters: document.getElementById('active-filters'),
            quotePlaceholder: document.getElementById('quote-placeholder'),
            quoteResult: document.getElementById('quote-result'),
            quoteContext: document.getElementById('quote-context'),
            chartHint: document.getElementById('chart-hint'),
            chartOverlay: document.getElementById('chart-overlay'),
            priceChart: document.getElementById('price-chart'),
            historyTableShell: document.getElementById('history-table-shell'),
            historyActionsHeader: document.getElementById('history-actions-header'),
            historyRows: document.getElementById('history-rows'),
            paginationInfo: document.getElementById('pagination-info'),
            pageIndicator: document.getElementById('page-indicator'),
            updatedAt: document.getElementById('updated-at'),
            historyStatus: document.getElementById('history-status'),
            historyStatusDot: document.getElementById('history-status-dot'),
            historyStatusText: document.getElementById('history-status-text'),
            latencyStatus: document.getElementById('latency-status'),
            btnFetch: document.getElementById('btn-fetch'),
            btnSave: document.getElementById('btn-save'),
            btnFilter: document.getElementById('btn-filter'),
            btnReset: document.getElementById('btn-reset'),
            btnClearActiveFilters: document.getElementById('btn-clear-active-filters'),
            btnRefresh: document.getElementById('btn-refresh'),
            btnDeleteFiltered: document.getElementById('btn-delete-filtered'),
            btnPageFirst: document.getElementById('btn-page-first'),
            btnPagePrev: document.getElementById('btn-page-prev'),
            btnPageNext: document.getElementById('btn-page-next'),
            btnPageLast: document.getElementById('btn-page-last'),
            btnToggleToken: document.getElementById('btn-toggle-token'),
            quickSymbols: document.getElementById('quick-symbols'),
            quickRanges: document.getElementById('quick-ranges'),
            kpiTotal: document.getElementById('kpi-total'),
            kpiTotalDetail: document.getElementById('kpi-total-detail'),
            kpiAssets: document.getElementById('kpi-assets'),
            kpiAverage: document.getElementById('kpi-average'),
            kpiLatest: document.getElementById('kpi-latest'),
            kpiTrend: document.getElementById('kpi-trend'),
        };

        const htmlEscapeMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        };

        const quickSymbolsByProvider = Object.freeze({
            default: ['BTC', 'ETH', 'MSFT', 'AAPL', 'USD-BRL', 'PETR4.SA'],
            awesome_api: ['BTC', 'ETH', 'USD-BRL', 'EUR-BRL', 'GBP-BRL', 'BRL-USD'],
            alpha_vantage: ['BTC', 'ETH', 'MSFT', 'AAPL', 'USD-BRL', 'PETR4.SA'],
            yahoo_finance: ['MSFT', 'AAPL', 'GOOGL', 'NVDA', 'PETR4.SA', 'USDBRL'],
            stooq: ['MSFT', 'AAPL', 'NVDA', 'TSLA', 'BTC', 'USDBRL'],
        });

        /**

         * Executa a rotina principal do metodo escapeHtml.

         */
        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => htmlEscapeMap[char]);
        }

        /**

         * Executa a rotina principal do metodo toISODateInput.

         */
        function toISODateInput(dateValue) {
            const date = new Date(dateValue);
            const offsetMs = date.getTimezoneOffset() * 60000;
            return new Date(date.getTime() - offsetMs).toISOString().slice(0, 10);
        }

        /**

         * Normaliza os dados para o formato esperado.

         */
        function normalizeSymbolInput() {
            elements.symbol.value = elements.symbol.value.trim().toUpperCase();
        }

        /**

         * Retorna o estado atual da configuracao.

         */
        function currentProviderForQuickSymbols() {
            const provider = elements.provider.value.trim();

            return provider === '' ? 'default' : provider;
        }

        /**

         * Renderiza a saida para o formato esperado.

         */
        function renderQuickSymbols() {
            const provider = currentProviderForQuickSymbols();
            const symbols = quickSymbolsByProvider[provider] || quickSymbolsByProvider.default;

            elements.quickSymbols.innerHTML = symbols.map((symbol) => `
                <button type="button" data-symbol="${escapeHtml(symbol)}" class="chip rounded-full px-3 py-1.5 text-xs font-semibold">
                    ${escapeHtml(symbol)}
                </button>
            `).join('');

            markActiveQuickSymbol(elements.symbol.value.trim());
        }

        /**

         * Executa a rotina principal do metodo numberFormatter.

         */
        function numberFormatter() {
            return new Intl.NumberFormat(locale, {
                maximumFractionDigits: 2,
            });
        }

        /**

         * Executa a rotina principal do metodo priceFormatter.

         */
        function priceFormatter(currency) {
            try {
                return new Intl.NumberFormat(locale, {
                    style: 'currency',
                    currency,
                    maximumFractionDigits: 6,
                });
            } catch (_) {
                return new Intl.NumberFormat(locale, {
                    maximumFractionDigits: 6,
                });
            }
        }

        /**

         * Formata os dados para exibicao.

         */
        function formatPrice(value, currency = 'USD') {
            if (value === null || value === undefined || Number.isNaN(Number(value))) {
                return '-';
            }

            return priceFormatter(currency).format(Number(value));
        }

        /**

         * Formata os dados para exibicao.

         */
        function formatDateTime(value) {
            if (!value) {
                return '-';
            }

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return '-';
            }

            return date.toLocaleString(locale, {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                timeZoneName: 'short',
            });
        }

        /**

         * Formata os dados para exibicao.

         */
        function formatDateOnly(value) {
            if (!value) {
                return '-';
            }

            const date = new Date(`${value}T00:00:00`);

            if (Number.isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleDateString(locale);
        }

        /**

         * Executa a rotina principal do metodo relativeFromNow.

         */
        function relativeFromNow(value) {
            if (!value) {
                return '-';
            }

            const target = new Date(value);
            if (Number.isNaN(target.getTime())) {
                return '-';
            }

            const diffMs = Date.now() - target.getTime();
            const diffMinutes = Math.round(diffMs / 60000);

            if (Math.abs(diffMinutes) < 1) return 'agora';
            if (Math.abs(diffMinutes) < 60) return `${diffMinutes} min`;

            const diffHours = Math.round(diffMinutes / 60);
            if (Math.abs(diffHours) < 48) return `${diffHours} h`;

            const diffDays = Math.round(diffHours / 24);
            return `${diffDays} d`;
        }

        /**

         * Monta os dados necessarios para a proxima etapa.

         */
        function buildQuery(params) {
            const query = new URLSearchParams();

            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    query.set(key, String(value));
                }
            });

            return query.toString();
        }

        /**

         * Executa a rotina principal do metodo requestHeaders.

         */
        function requestHeaders() {
            const headers = {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            };

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const token = elements.token.value.trim();

            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }

            if (token) {
                headers.Authorization = `Bearer ${token}`;
            }

            return headers;
        }

        /**

         * Executa a rotina principal do metodo sanitizePerPageValue.

         */
        function sanitizePerPageValue(value) {
            const parsed = Number.parseInt(String(value ?? ''), 10);

            if (!Number.isFinite(parsed)) {
                return 20;
            }

            return Math.min(100, Math.max(1, parsed));
        }

        /**

         * Coleta dados conforme a configuracao ativa.

         */
        function collectFilters() {
            const safePerPage = sanitizePerPageValue(elements.perPage.value);

            return {
                symbol: elements.symbol.value.trim(),
                type: elements.type.value.trim(),
                source: elements.provider.value.trim(),
                date_from: elements.dateFrom.value,
                date_to: elements.dateTo.value,
                per_page: safePerPage,
            };
        }

        /**

         * Coleta dados conforme a configuracao ativa.

         */
        function collectDeletionFilters() {
            const filters = collectFilters();

            return {
                symbol: filters.symbol,
                type: filters.type,
                source: filters.source,
                date_from: filters.date_from,
                date_to: filters.date_to,
            };
        }

        /**

         * Verifica a existencia da condicao avaliada.

         */
        function hasRestrictiveHistoryFilters(filters = collectDeletionFilters()) {
            return [
                filters.symbol,
                filters.type,
                filters.source,
                filters.date_from,
                filters.date_to,
            ].some((value) => Boolean(String(value ?? '').trim()));
        }

        /**

         * Normaliza os dados para o formato esperado.

         */
        function normalizePanelVisibility(value) {
            const normalized = { ...defaultPanelVisibility };
            const source = value && typeof value === 'object' ? value : {};

            Object.keys(normalized).forEach((panel) => {
                if (typeof source[panel] === 'boolean') {
                    normalized[panel] = source[panel];
                }
            });

            return normalized;
        }

        /**

         * Executa a rotina principal do metodo layoutPresetMap.

         */
        function layoutPresetMap(mode) {
            const presets = {
                monitor: {
                    kpis: true,
                    filters: true,
                    snapshot: true,
                    chart: true,
                    history: true,
                },
                analysis: {
                    kpis: true,
                    filters: false,
                    snapshot: false,
                    chart: true,
                    history: true,
                },
                query: {
                    kpis: true,
                    filters: true,
                    snapshot: true,
                    chart: false,
                    history: false,
                },
            };

            return presets[mode] || presets.monitor;
        }

        /**

         * Executa a rotina principal do metodo layoutModeText.

         */
        function layoutModeText(mode) {
            const messages = {
                monitor: 'Modo atual: monitor completo.',
                analysis: 'Modo atual: análise de histórico com foco em gráfico e tabela.',
                query: 'Modo atual: consulta operacional com foco em entrada e snapshot.',
                custom: 'Modo atual: distribuição personalizada.',
            };

            return messages[mode] || messages.custom;
        }

        /**

         * Executa a rotina principal do metodo layoutModeLabel.

         */
        function layoutModeLabel(mode) {
            const labels = {
                monitor: 'Monitor',
                analysis: 'Análise',
                query: 'Consulta',
                custom: 'Personalizado',
            };

            return labels[mode] || labels.custom;
        }

        /**

         * Aplica as configuracoes no fluxo atual.

         */
        function applyLayoutState() {
            const visibility = normalizePanelVisibility(state.panelVisibility);
            state.panelVisibility = visibility;

            const snapshotVisible = visibility.snapshot;
            const chartVisible = visibility.chart;
            const insightsVisible = snapshotVisible || chartVisible;
            const filtersVisible = visibility.filters;
            const workspaceVisible = filtersVisible || insightsVisible;
            const twoColumns = filtersVisible && insightsVisible;

            elements.sectionKpis.classList.toggle('is-collapsed', !visibility.kpis);
            elements.panelFilters.classList.toggle('is-collapsed', !filtersVisible);
            elements.panelSnapshot.classList.toggle('is-collapsed', !snapshotVisible);
            elements.panelChart.classList.toggle('is-collapsed', !chartVisible);
            elements.panelInsights.classList.toggle('is-collapsed', !insightsVisible);
            elements.sectionWorkspace.classList.toggle('is-collapsed', !workspaceVisible);
            elements.sectionWorkspace.classList.toggle('two-columns', twoColumns);
            elements.sectionWorkspace.classList.toggle('one-column', !twoColumns);
            elements.panelHistory.classList.toggle('is-collapsed', !visibility.history);

            elements.layoutHint.textContent = layoutModeText(state.layoutMode);

            elements.layoutPresets.querySelectorAll('button[data-layout]').forEach((button) => {
                button.setAttribute('aria-pressed', button.dataset.layout === state.layoutMode ? 'true' : 'false');
            });

            elements.layoutToggles.querySelectorAll('button[data-panel]').forEach((button) => {
                const panel = button.dataset.panel;
                const label = button.dataset.label || panel;
                const isVisible = Boolean(visibility[panel]);
                button.dataset.hidden = isVisible ? 'false' : 'true';
                button.textContent = `${isVisible ? 'Esconder' : 'Mostrar'} ${label}`;
            });

            if (state.chart && visibility.chart) {
                window.requestAnimationFrame(() => state.chart?.resize());
            }
        }

        /**

         * Aplica as configuracoes no fluxo atual.

         */
        function applyLayoutPreset(mode, { notify = true } = {}) {
            state.layoutMode = mode;
            state.panelVisibility = { ...layoutPresetMap(mode) };
            applyLayoutState();
            savePreferences();

            if (notify) {
                showNotice(`Layout "${layoutModeLabel(mode)}" aplicado.`, 'info');
            }
        }

        /**

         * Executa a rotina principal do metodo togglePanelVisibility.

         */
        function togglePanelVisibility(panel) {
            if (!(panel in state.panelVisibility)) {
                return;
            }

            state.layoutMode = 'custom';
            state.panelVisibility[panel] = !state.panelVisibility[panel];
            applyLayoutState();
            savePreferences();
        }

        /**

         * Salva os dados persistiveis da operacao.

         */
        function savePreferences() {
            try {
                const payload = {
                    symbol: elements.symbol.value,
                    provider: elements.provider.value,
                    type: elements.type.value,
                    dateFrom: elements.dateFrom.value,
                    dateTo: elements.dateTo.value,
                    perPage: elements.perPage.value,
                    layoutMode: state.layoutMode,
                    panelVisibility: state.panelVisibility,
                };

                window.localStorage.setItem(storageKey, JSON.stringify(payload));
            } catch (_) {
                // noop
            }
        }

        /**

         * Carrega dados necessarios para a operacao.

         */
        function loadPreferences() {
            try {
                const raw = window.localStorage.getItem(storageKey);
                if (!raw) {
                    return;
                }

                const payload = JSON.parse(raw);

                elements.symbol.value = payload.symbol || elements.symbol.value;
                elements.provider.value = payload.provider || '';
                elements.type.value = payload.type || '';
                elements.dateFrom.value = payload.dateFrom || '';
                elements.dateTo.value = payload.dateTo || '';
                elements.perPage.value = payload.perPage || 20;
                state.layoutMode = ['monitor', 'analysis', 'query', 'custom'].includes(payload.layoutMode)
                    ? payload.layoutMode
                    : 'monitor';
                state.panelVisibility = normalizePanelVisibility(payload.panelVisibility);
            } catch (_) {
                // noop
            }
        }

        /**

         * Exibe os dados solicitados.

         */
        function showValidationHint(message) {
            elements.validationHint.textContent = message;
            elements.validationHint.classList.remove('hidden');
        }

        /**

         * Executa a rotina principal do metodo hideValidationHint.

         */
        function hideValidationHint() {
            elements.validationHint.classList.add('hidden');
        }

        /**

         * Executa a rotina principal do metodo validateHistoryFilters.

         */
        function validateHistoryFilters({ notify = false } = {}) {
            const rawPerPage = elements.perPage.value;
            const safePerPage = sanitizePerPageValue(rawPerPage);
            const perPageChanged = String(safePerPage) !== String(rawPerPage).trim();

            elements.perPage.value = String(safePerPage);

            const fromValue = elements.dateFrom.value;
            const toValue = elements.dateTo.value;

            if (fromValue && toValue) {
                const from = new Date(`${fromValue}T00:00:00`);
                const to = new Date(`${toValue}T00:00:00`);

                if (!Number.isNaN(from.getTime()) && !Number.isNaN(to.getTime()) && from > to) {
                    const message = 'A data inicial não pode ser maior do que a data final.';
                    showValidationHint(message);

                    if (notify) {
                        showNotice(message, 'warning');
                    }

                    return false;
                }
            }

            hideValidationHint();

            if (perPageChanged && notify) {
                showNotice(`Itens por página ajustado automaticamente para ${safePerPage}.`, 'info');
            }

            return true;
        }

        /**

         * Renderiza a saida para o formato esperado.

         */
        function renderActiveFilters() {
            const filters = collectFilters();
            const chips = [];

            if (filters.symbol) {
                chips.push({ label: 'Símbolo', value: filters.symbol.toUpperCase() });
            }

            if (filters.source) {
                chips.push({ label: 'Provider', value: filters.source });
            }

            if (filters.type) {
                chips.push({ label: 'Tipo', value: filters.type });
            }

            if (filters.date_from) {
                chips.push({ label: 'De', value: formatDateOnly(filters.date_from) });
            }

            if (filters.date_to) {
                chips.push({ label: 'Até', value: formatDateOnly(filters.date_to) });
            }

            if (filters.per_page !== 20) {
                chips.push({ label: 'Itens', value: numberFormatter().format(filters.per_page) });
            }

            if (!chips.length) {
                elements.activeFilters.innerHTML = `
                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs text-slate-500">
                        Nenhum filtro ativo.
                    </span>
                `;
                elements.btnClearActiveFilters.disabled = true;
                return;
            }

            elements.activeFilters.innerHTML = chips.map((chip) => `
                <span class="active-chip inline-flex items-center gap-2 rounded-full px-3 py-1">
                    <span class="font-semibold text-slate-600">${escapeHtml(chip.label)}:</span>
                    <span class="font-medium text-slate-800">${escapeHtml(chip.value)}</span>
                </span>
            `).join('');
            elements.btnClearActiveFilters.disabled = false;
        }

        /**

         * Atualiza dados existentes conforme os parametros recebidos.

         */
        function updateHistoryStatus(message, tone = 'neutral') {
            const styleByTone = {
                neutral: 'bg-slate-400',
                loading: 'bg-sky-500',
                success: 'bg-emerald-500',
                error: 'bg-rose-500',
            };

            elements.historyStatusDot.className = `status-dot ${styleByTone[tone] || styleByTone.neutral}`;
            elements.historyStatusText.textContent = `Status: ${message}`;
        }

        /**

         * Exibe os dados solicitados.

         */
        function showNotice(message, kind = 'info') {
            const classByKind = {
                info: 'border-sky-200 bg-sky-50 text-sky-700',
                success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                warning: 'border-amber-200 bg-amber-50 text-amber-700',
                error: 'border-rose-200 bg-rose-50 text-rose-700',
            };

            elements.notice.className = `mt-4 rounded-xl border px-3 py-2 text-sm ${classByKind[kind] || classByKind.info}`;
            elements.notice.textContent = message;
            elements.notice.classList.remove('hidden');
        }

        /**

         * Executa a rotina principal do metodo hideNotice.

         */
        function hideNotice() {
            elements.notice.classList.add('hidden');
        }

        /**

         * Define valores de configuracao para o fluxo atual.

         */
        function setTokenPermissionHint(message, tone = 'neutral') {
            const classByTone = {
                neutral: 'text-slate-500',
                success: 'text-emerald-700',
                warning: 'text-amber-700',
                error: 'text-rose-700',
            };

            elements.tokenPermissionHint.className = `text-xs ${classByTone[tone] || classByTone.neutral}`;
            elements.tokenPermissionHint.textContent = message;
        }

        /**

         * Aplica as configuracoes no fluxo atual.

         */
        function applyDeletePermissionUiState() {
            const canDelete = Boolean(state.canDeleteQuotations);

            elements.btnDeleteFiltered.classList.toggle('hidden', !canDelete);
            elements.historyActionsHeader.classList.toggle('hidden', !canDelete);
            elements.historyRows.querySelectorAll('.history-actions-cell').forEach((cell) => {
                cell.classList.toggle('hidden', !canDelete);
            });
        }

        /**

         * Executa a rotina principal do metodo refreshUserDeletePermission.

         */
        async function refreshUserDeletePermission({ notifyOnFailure = false } = {}) {
            const token = elements.token.value.trim();

            if (!token) {
                state.canDeleteQuotations = false;
                state.authUser = null;
                state.tokenChecked = true;
                setTokenPermissionHint('Sem token: modo leitura (exclusão oculta).');
                applyDeletePermissionUiState();
                renderHistory(state.history);
                syncDeleteFilteredButtonState();
                return;
            }

            setTokenPermissionHint('Validando permissões do token...', 'neutral');

            try {
                const payload = await requestJson('/api/user');
                const userData = payload?.data ?? payload ?? {};
                const canDelete = Boolean(
                    userData?.permissions?.delete_quotations ?? userData?.is_admin
                );

                state.canDeleteQuotations = canDelete;
                state.authUser = userData;
                state.tokenChecked = true;

                if (canDelete) {
                    const principal = userData?.name || userData?.email || 'usuário';
                    setTokenPermissionHint(
                        `Token válido para ${principal}: exclusão liberada.`,
                        'success'
                    );
                } else {
                    setTokenPermissionHint(
                        'Token válido em modo leitura: exclusão bloqueada para este usuário.',
                        'warning'
                    );

                    if (notifyOnFailure) {
                        showNotice('Seu usuário não possui permissão para excluir cotações.', 'warning');
                    }
                }
            } catch (error) {
                state.canDeleteQuotations = false;
                state.authUser = null;
                state.tokenChecked = true;
                setTokenPermissionHint('Token inválido ou expirado: exclusão bloqueada.', 'error');

                if (notifyOnFailure) {
                    showNotice(`Falha ao validar token: ${error.message}`, 'warning');
                }
            } finally {
                applyDeletePermissionUiState();
                renderHistory(state.history);
                syncDeleteFilteredButtonState();
            }
        }

        /**

         * Executa a rotina principal do metodo scheduleTokenPermissionCheck.

         */
        function scheduleTokenPermissionCheck() {
            if (tokenPermissionCheckTimeout !== null) {
                window.clearTimeout(tokenPermissionCheckTimeout);
            }

            tokenPermissionCheckTimeout = window.setTimeout(() => {
                refreshUserDeletePermission({ notifyOnFailure: Boolean(elements.token.value.trim()) });
            }, 350);
        }

        /**

         * Executa a rotina principal do metodo filtersSignature.

         */
        function filtersSignature(filters = collectFilters()) {
            return JSON.stringify(filters);
        }

        /**

         * Atualiza dados existentes conforme os parametros recebidos.

         */
        function updateApplyButtonState() {
            const dirty = filtersSignature() !== state.lastAppliedFilters;

            if (dirty) {
                elements.btnFilter.classList.add('border-teal-300', 'text-teal-700', 'bg-teal-50');
                elements.btnFilter.textContent = 'Aplicar filtros*';
                syncDeleteFilteredButtonState();
                return;
            }

            elements.btnFilter.classList.remove('border-teal-300', 'text-teal-700', 'bg-teal-50');
            elements.btnFilter.textContent = 'Aplicar filtros';
            syncDeleteFilteredButtonState();
        }

        /**

         * Define valores de configuracao para o fluxo atual.

         */
        function setButtonLoading(button, loading, loadingText) {
            if (!button) return;

            if (!button.dataset.defaultText) {
                button.dataset.defaultText = button.textContent;
            }

            button.disabled = loading;
            button.textContent = loading ? loadingText : button.dataset.defaultText;
        }

        /**

         * Define valores de configuracao para o fluxo atual.

         */
        function setPaginationDisabled(disabled) {
            [
                elements.btnPageFirst,
                elements.btnPagePrev,
                elements.btnPageNext,
                elements.btnPageLast,
            ].forEach((button) => {
                button.disabled = disabled;
            });
        }

        /**

         * Define valores de configuracao para o fluxo atual.

         */
        function setHistoryLoading(loading) {
            state.loadingHistory = loading;
            elements.chartOverlay.classList.toggle('hidden', !loading);
            setPaginationDisabled(loading);
            elements.historyTableShell.setAttribute('aria-busy', loading ? 'true' : 'false');
            elements.historyRows.setAttribute('aria-live', loading ? 'off' : 'polite');
            elements.btnFilter.disabled = loading;
            elements.btnReset.disabled = loading;
            elements.btnRefresh.disabled = loading;
            elements.btnClearActiveFilters.disabled = loading;
            elements.btnDeleteFiltered.disabled = loading;
            elements.layoutPresets.querySelectorAll('button[data-layout]').forEach((button) => {
                button.disabled = loading;
            });
            elements.layoutToggles.querySelectorAll('button[data-panel]').forEach((button) => {
                button.disabled = loading;
            });
            syncDeleteFilteredButtonState();
        }

        /**

         * Atualiza dados existentes conforme os parametros recebidos.

         */
        function updatePagination(meta) {
            const currentPage = meta?.current_page || 1;
            const lastPage = meta?.last_page || 1;

            elements.pageIndicator.textContent = `Página ${currentPage} de ${lastPage}`;
            elements.btnPageFirst.disabled = currentPage <= 1 || state.loadingHistory;
            elements.btnPagePrev.disabled = currentPage <= 1 || state.loadingHistory;
            elements.btnPageNext.disabled = currentPage >= lastPage || state.loadingHistory;
            elements.btnPageLast.disabled = currentPage >= lastPage || state.loadingHistory;
        }

        /**

         * Sincroniza o estado entre as fontes envolvidas.

         */
        function syncDeleteFilteredButtonState() {
            if (!state.canDeleteQuotations) {
                elements.btnDeleteFiltered.textContent = 'Excluir filtradas';
                elements.btnDeleteFiltered.disabled = true;
                return;
            }

            const total = Number(state.meta?.total ?? state.history.length ?? 0);
            const dirty = filtersSignature() !== state.lastAppliedFilters;

            if (dirty) {
                elements.btnDeleteFiltered.textContent = 'Aplique filtros para excluir';
                elements.btnDeleteFiltered.disabled = true;
                return;
            }

            elements.btnDeleteFiltered.textContent = total > 0
                ? `Excluir filtradas (${numberFormatter().format(total)})`
                : 'Excluir filtradas';
            elements.btnDeleteFiltered.disabled = state.loadingHistory || total < 1;
        }

        /**

         * Atualiza dados existentes conforme os parametros recebidos.

         */
        function updateKpis(items) {
            const total = state.meta?.total ?? items.length;
            const uniqueAssets = new Set(items.map((item) => item.symbol)).size;
            const averagePrice = items.length
                ? items.reduce((sum, item) => sum + Number(item.price), 0) / items.length
                : null;
            const latest = items[0]?.symbol ?? '-';

            let trend = null;
            if (items.length > 1) {
                const newest = Number(items[0].price);
                const oldest = Number(items[items.length - 1].price);

                if (!Number.isNaN(newest) && !Number.isNaN(oldest) && oldest !== 0) {
                    trend = ((newest - oldest) / oldest) * 100;
                }
            }

            elements.kpiTotal.textContent = numberFormatter().format(total);
            elements.kpiTotalDetail.textContent = state.meta
                ? `Página ${state.meta.current_page} de ${state.meta.last_page}`
                : 'Base filtrada atual.';
            elements.kpiAssets.textContent = numberFormatter().format(uniqueAssets);
            elements.kpiAverage.textContent = averagePrice === null
                ? '-'
                : formatPrice(averagePrice, items[0]?.currency || 'USD');
            elements.kpiLatest.textContent = latest;

            if (trend === null) {
                elements.kpiTrend.textContent = 'Sem tendência calculável.';
                elements.kpiTrend.className = 'mt-1 text-xs text-slate-500';
                return;
            }

            const trendText = `${trend >= 0 ? '+' : ''}${trend.toFixed(2)}% na janela atual`;
            const trendClass = trend >= 0 ? 'text-emerald-700' : 'text-rose-700';
            elements.kpiTrend.textContent = trendText;
            elements.kpiTrend.className = `mt-1 text-xs ${trendClass}`;
        }

        /**

         * Renderiza a saida para o formato esperado.

         */
        function renderQuote(quote, context = 'Prévia') {
            elements.quotePlaceholder.classList.add('hidden');
            elements.quoteResult.classList.remove('hidden');
            elements.quoteContext.textContent = context;

            const quotedAt = formatDateTime(quote.quoted_at);
            const createdAt = formatDateTime(quote.created_at);

            elements.quoteResult.innerHTML = `
                <div class="grid gap-5 lg:grid-cols-[1.2fr_1fr]">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Ativo</p>
                        <p class="mt-1 text-3xl font-bold text-slate-900">${escapeHtml(quote.symbol || '-')}</p>
                        <p class="mt-1 text-sm text-slate-600">${escapeHtml(quote.name || '-')}</p>
                        <p class="mt-4 inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                            ${escapeHtml(quote.type || 'unknown')} • ${escapeHtml(quote.source || '-')}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Preço</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">${escapeHtml(formatPrice(quote.price, quote.currency || 'USD'))}</p>
                        <div class="mt-3 space-y-1 text-xs text-slate-500">
                            <p>Moeda: <span class="font-semibold text-slate-700">${escapeHtml(quote.currency || '-')}</span></p>
                            <p>Quoted at: <span class="font-semibold text-slate-700">${escapeHtml(quotedAt)}</span></p>
                            <p>Persistido em: <span class="font-semibold text-slate-700">${escapeHtml(createdAt)}</span></p>
                        </div>
                    </div>
                </div>
            `;
        }

        /**

         * Renderiza a saida para o formato esperado.

         */
        function renderHistory(items, preview = false) {
            const showActions = state.canDeleteQuotations;
            const emptyColspan = showActions ? 8 : 7;

            if (!items.length) {
                elements.historyRows.innerHTML = `
                    <tr>
                        <td colspan="${emptyColspan}" class="px-4 py-8 text-center text-sm text-slate-500">
                            Nenhum registro encontrado para os filtros atuais.
                        </td>
                    </tr>
                `;
                applyDeletePermissionUiState();
                return;
            }

            elements.historyRows.innerHTML = items.map((item) => `
                <tr class="hover:bg-slate-50/80">
                    <td class="px-4 py-3 font-semibold text-slate-800">${escapeHtml(item.symbol)}</td>
                    <td class="px-4 py-3 text-slate-600">${escapeHtml(item.type)}</td>
                    <td class="px-4 py-3 font-medium text-slate-800">${escapeHtml(formatPrice(item.price, item.currency || 'USD'))}</td>
                    <td class="px-4 py-3 text-slate-600">${escapeHtml(item.currency || '-')}</td>
                    <td class="px-4 py-3 text-slate-600">${escapeHtml(item.source || '-')}</td>
                    <td class="px-4 py-3 text-slate-500">
                        ${escapeHtml(formatDateTime(item.quoted_at))}
                        <span class="block text-xs text-slate-400">${escapeHtml(relativeFromNow(item.quoted_at))}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-500">${escapeHtml(formatDateTime(item.created_at))}</td>
                    <td class="history-actions-cell px-4 py-3 text-right ${showActions ? '' : 'hidden'}">
                        ${showActions && !preview && Number.isFinite(Number(item.id)) ? `
                            <button
                                type="button"
                                data-delete-id="${escapeHtml(item.id)}"
                                data-delete-symbol="${escapeHtml(item.symbol)}"
                                class="btn btn-danger rounded-lg px-2.5 py-1 text-xs font-semibold"
                            >
                                Excluir
                            </button>
                        ` : `
                            <span class="text-xs text-slate-400">—</span>
                        `}
                    </td>
                </tr>
            `).join('');

            if (preview) {
                elements.paginationInfo.textContent = 'Prévia sem persistência: os dados ainda não foram salvos.';
                elements.pageIndicator.textContent = 'Prévia';
            }

            applyDeletePermissionUiState();
        }

        /**

         * Renderiza a saida para o formato esperado.

         */
        function renderHistorySkeleton(lines = 6) {
            const widths = state.canDeleteQuotations
                ? ['6rem', '4.75rem', '6.75rem', '4rem', '5.5rem', '7rem', '7rem', '4.5rem']
                : ['6rem', '4.75rem', '6.75rem', '4rem', '5.5rem', '7rem', '7rem'];

            elements.historyRows.innerHTML = Array.from({ length: lines }, () => `
                <tr>
                    ${widths.map((width) => `
                        <td class="px-4 py-3">
                            <div class="skeleton h-3" style="width:${width};"></div>
                        </td>
                    `).join('')}
                </tr>
            `).join('');
        }

        /**

         * Executa a rotina principal do metodo chartGradient.

         */
        function chartGradient(ctx) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 280);
            gradient.addColorStop(0, 'rgba(14, 165, 163, 0.35)');
            gradient.addColorStop(1, 'rgba(14, 165, 163, 0.02)');
            return gradient;
        }

        /**

         * Atualiza dados existentes conforme os parametros recebidos.

         */
        function updateChartHint(items, preview = false) {
            if (preview) {
                elements.chartHint.textContent = 'Prévia em tempo real, ainda fora do histórico persistido.';
                return;
            }

            if (!items.length) {
                elements.chartHint.textContent = 'Sem pontos para visualização com os filtros atuais.';
                return;
            }

            if (items.length === 1) {
                elements.chartHint.textContent = 'Apenas 1 ponto disponível; persista mais cotações para tendência.';
                return;
            }

            elements.chartHint.textContent = `${items.length} pontos no gráfico (ordem cronológica).`;
        }

        /**

         * Remove dados conforme os filtros informados.

         */
        function destroyChart() {
            if (state.chart) {
                state.chart.destroy();
                state.chart = null;
            }
        }

        /**

         * Renderiza a saida para o formato esperado.

         */
        function renderChart(items, preview = false) {
            destroyChart();
            updateChartHint(items, preview);

            if (!items.length) {
                return;
            }

            const normalized = [...items].reverse();
            const labels = normalized.map((item) => {
                const value = item.quoted_at || item.created_at;
                return formatDateTime(value);
            });
            const values = normalized.map((item) => Number(item.price));
            const singlePoint = values.length === 1;
            const ctx = elements.priceChart.getContext('2d');

            if (!ctx) {
                return;
            }

            state.chart = new Chart(ctx, {
                type: singlePoint ? 'bar' : 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Preço',
                        data: values,
                        borderColor: '#0f766e',
                        backgroundColor: singlePoint ? '#14b8a6' : chartGradient(ctx),
                        fill: !singlePoint,
                        tension: singlePoint ? 0 : 0.28,
                        borderWidth: singlePoint ? 0 : 2,
                        pointRadius: singlePoint ? 3 : 2,
                        pointHoverRadius: 5,
                        borderRadius: singlePoint ? 8 : 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#2a3f54',
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    const raw = context.raw;
                                    return `Preço: ${formatPrice(raw, normalized[context.dataIndex]?.currency || 'USD')}`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#60798f',
                                maxRotation: 30,
                                minRotation: 30,
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.16)',
                            },
                        },
                        y: {
                            ticks: {
                                color: '#60798f',
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.16)',
                            },
                        },
                    },
                },
            });
        }

        /**

         * Executa a rotina principal do metodo requestJson.

         */
        async function requestJson(url, options = {}) {
            const startedAt = performance.now();

            const response = await fetch(url, {
                ...options,
                headers: requestHeaders(),
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(() => ({}));
            const elapsed = Math.round(performance.now() - startedAt);
            elements.latencyStatus.textContent = `Última latência: ${elapsed} ms`;

            if (!response.ok) {
                const error = new Error(payload.message || `Falha na requisição (${response.status}).`);
                error.status = response.status;
                error.errorCode = payload.error_code || null;
                throw error;
            }

            return payload;
        }

        /**

         * Mapeia dados para a estrutura de destino.

         */
        function mapDeleteErrorMessage(error, fallback = 'Falha na exclusão.') {
            const status = Number(error?.status ?? 0);
            const errorCode = String(error?.errorCode || '');

            if (status === 419 || errorCode === 'csrf_mismatch') {
                return 'Sessão/CSRF expirado. Recarregue a página (Ctrl + Shift + R), valide o token novamente e tente excluir.';
            }

            if (status === 405 || errorCode === 'method_not_allowed') {
                return 'Método não disponível para esta rota. Use POST /api/quotations/bulk-delete e rode "php artisan optimize:clear".';
            }

            if (status === 403 || errorCode === 'forbidden') {
                return 'Seu token está autenticado, mas sem permissão para excluir cotações.';
            }

            if (status === 404 || errorCode === 'not_found') {
                return 'A cotação não foi encontrada (pode já ter sido removida). Atualize o histórico.';
            }

            return `${fallback} ${error?.message || ''}`.trim();
        }

        /**

         * Carrega dados necessarios para a operacao.

         */
        async function loadHistory(page = 1) {
            normalizeSymbolInput();
            renderActiveFilters();

            if (!validateHistoryFilters({ notify: true })) {
                updateHistoryStatus('filtros inválidos', 'error');
                updateApplyButtonState();
                return;
            }

            const safePage = Math.max(1, Number(page) || 1);
            state.currentPage = safePage;

            setHistoryLoading(true);
            updateHistoryStatus('carregando histórico...', 'loading');
            elements.paginationInfo.textContent = 'Carregando histórico...';
            renderHistorySkeleton();

            const filters = collectFilters();
            const query = buildQuery({ ...filters, page: safePage });
            const url = `/api/quotations${query ? `?${query}` : ''}`;

            try {
                const payload = await requestJson(url);
                const items = Array.isArray(payload.data) ? payload.data : [];
                const lastPageFromPayload = Math.max(1, Number(payload.meta?.last_page || 1));

                if (safePage > lastPageFromPayload) {
                    await loadHistory(lastPageFromPayload);
                    return;
                }

                state.history = items;
                state.meta = payload.meta || null;

                renderHistory(items);
                renderChart(items);
                updateKpis(items);
                updatePagination(state.meta);

                const from = payload.meta?.from ?? 0;
                const to = payload.meta?.to ?? 0;
                const total = payload.meta?.total ?? items.length;

                elements.paginationInfo.textContent = items.length
                    ? `Exibindo ${from}-${to} de ${numberFormatter().format(total)} registro(s).`
                    : 'Nenhum dado para os filtros informados.';

                elements.updatedAt.textContent = `Última atualização: ${new Date().toLocaleString(locale)}`;
                updateHistoryStatus(items.length ? `${items.length} item(ns) carregado(s)` : 'nenhum dado encontrado', 'success');
                hideNotice();
                savePreferences();
                state.lastAppliedFilters = filtersSignature(filters);
                updateApplyButtonState();
                syncDeleteFilteredButtonState();
            } catch (error) {
                state.history = [];
                state.meta = null;

                renderHistory([]);
                renderChart([]);
                updateKpis([]);
                updatePagination(null);

                elements.paginationInfo.textContent = 'Não foi possível carregar o histórico.';
                updateHistoryStatus('falha ao carregar histórico', 'error');
                showNotice(`Erro ao carregar histórico: ${error.message}`, 'error');
                syncDeleteFilteredButtonState();
            } finally {
                setHistoryLoading(false);
                renderActiveFilters();
                syncDeleteFilteredButtonState();
            }
        }

        /**

         * Remove dados conforme os filtros informados.

         */
        async function deleteSingleQuotation(quotationId, symbol = '') {
            if (state.loadingHistory) {
                return;
            }

            if (!state.canDeleteQuotations) {
                showNotice('Seu usuário não possui permissão para excluir cotações.', 'warning');
                return;
            }

            const description = symbol
                ? `a cotação de ${symbol}`
                : `a cotação #${quotationId}`;
            const confirmed = window.confirm(
                `Deseja excluir ${description} do histórico?\n\nA remoção usa soft delete e exige token válido.`
            );

            if (!confirmed) {
                return;
            }

            setHistoryLoading(true);
            updateHistoryStatus('removendo cotação...', 'loading');

            try {
                const payload = await requestJson(`/api/quotations/${encodeURIComponent(quotationId)}`, {
                    method: 'DELETE',
                });

                showNotice(payload.message || 'Cotação removida com sucesso.', 'success');
                await loadHistory(state.meta?.current_page || 1);
            } catch (error) {
                showNotice(mapDeleteErrorMessage(error, 'Erro ao excluir cotação.'), 'error');
                updateHistoryStatus('falha ao excluir cotação', 'error');
            } finally {
                if (state.loadingHistory) {
                    setHistoryLoading(false);
                }
                syncDeleteFilteredButtonState();
            }
        }

        /**

         * Remove dados conforme os filtros informados.

         */
        async function deleteFilteredHistory() {
            if (state.loadingHistory) {
                return;
            }

            if (!state.canDeleteQuotations) {
                showNotice('Seu usuário não possui permissão para excluir cotações.', 'warning');
                return;
            }

            if (!validateHistoryFilters({ notify: true })) {
                return;
            }

            if (filtersSignature() !== state.lastAppliedFilters) {
                showNotice('Aplique os filtros atuais antes de excluir em lote.', 'warning');
                return;
            }

            const total = Number(state.meta?.total ?? 0);

            if (total < 1) {
                showNotice('Não há registros para excluir com os filtros atuais.', 'info');
                return;
            }

            const filters = collectDeletionFilters();
            const hasFilters = hasRestrictiveHistoryFilters(filters);
            const scopeLabel = hasFilters ? 'dos filtros ativos' : 'de todo o histórico';
            const confirmed = window.confirm(
                `Excluir ${numberFormatter().format(total)} registro(s) ${scopeLabel}?\n\nA remoção usa soft delete e exige token válido.`
            );

            if (!confirmed) {
                return;
            }

            const payload = {
                ...Object.fromEntries(
                    Object.entries(filters).filter(([, value]) => value !== null && value !== undefined && value !== '')
                ),
                confirm: true,
                ...(hasFilters ? {} : { delete_all: true }),
            };

            setHistoryLoading(true);
            updateHistoryStatus('removendo histórico filtrado...', 'loading');

            try {
                const response = await requestJson('/api/quotations/bulk-delete', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                const deletedCount = Number(response?.data?.deleted_count ?? 0);

                showNotice(
                    deletedCount > 0
                        ? `${response.message || 'Registros removidos com sucesso.'} (${numberFormatter().format(deletedCount)} item(ns)).`
                        : response.message || 'Nenhum registro correspondente foi removido.',
                    deletedCount > 0 ? 'success' : 'info'
                );

                await loadHistory(1);
            } catch (error) {
                showNotice(mapDeleteErrorMessage(error, 'Erro ao excluir histórico.'), 'error');
                updateHistoryStatus('falha ao excluir histórico', 'error');
            } finally {
                if (state.loadingHistory) {
                    setHistoryLoading(false);
                }
                syncDeleteFilteredButtonState();
            }
        }

        /**

         * Busca dados na fonte configurada.

         */
        async function fetchQuote(save = false) {
            normalizeSymbolInput();
            const symbol = elements.symbol.value.trim();
            const provider = elements.provider.value.trim();
            const type = elements.type.value.trim();

            if (!symbol) {
                showNotice('Informe um símbolo válido antes de consultar.', 'warning');
                elements.symbol.focus();
                return;
            }

            const query = buildQuery({ provider, type });
            const url = `/api/quotation/${encodeURIComponent(symbol)}${query ? `?${query}` : ''}`;

            setButtonLoading(elements.btnFetch, !save, 'Consultando...');
            setButtonLoading(elements.btnSave, save, 'Persistindo...');

            updateHistoryStatus(save ? 'salvando cotação...' : 'consultando cotação...', 'loading');

            try {
                const payload = await requestJson(url, {
                    method: save ? 'POST' : 'GET',
                    body: save && type ? JSON.stringify({ type }) : undefined,
                });

                const quote = payload.data || {};

                renderQuote(quote, save ? 'Persistida no histórico' : 'Prévia em tempo real');
                showNotice(
                    save
                        ? 'Cotação salva com sucesso e histórico atualizado.'
                        : 'Prévia carregada com sucesso.',
                    'success'
                );

                if (save) {
                    await loadHistory(1);
                } else if (!state.history.length) {
                    const previewItem = {
                        ...quote,
                        created_at: quote.created_at || null,
                    };

                    renderHistory([previewItem], true);
                    renderChart([previewItem], true);
                    updateKpis([previewItem]);
                    updateHistoryStatus('prévia carregada (não persistida)', 'neutral');
                } else {
                    updateHistoryStatus('prévia carregada', 'success');
                }

                savePreferences();
            } catch (error) {
                showNotice(`Erro ao consultar cotação: ${error.message}`, 'error');
                updateHistoryStatus('falha na consulta', 'error');
            } finally {
                setButtonLoading(elements.btnFetch, false, 'Consultando...');
                setButtonLoading(elements.btnSave, false, 'Persistindo...');
            }
        }

        /**

         * Navega para a etapa solicitada.

         */
        function jumpToPage(targetPage) {
            if (state.loadingHistory) {
                return;
            }

            const current = state.meta?.current_page || 1;
            const last = state.meta?.last_page || 1;
            const next = Math.max(1, Math.min(last, targetPage));

            if (next === current) {
                return;
            }

            loadHistory(next);
        }

        /**

         * Aplica as configuracoes no fluxo atual.

         */
        function applyFilterReset({ clearSymbol = false, message = 'Filtros resetados. Histórico recarregado.' } = {}) {
            if (clearSymbol) {
                elements.symbol.value = '';
                markActiveQuickSymbol('');
            }

            elements.provider.value = '';
            elements.type.value = '';
            elements.dateFrom.value = '';
            elements.dateTo.value = '';
            elements.perPage.value = 20;
            syncQuickRangeButtons();
            markActiveQuickSymbol(elements.symbol.value.trim());
            hideValidationHint();
            renderActiveFilters();
            updateApplyButtonState();
            showNotice(message, 'info');
            savePreferences();
            loadHistory(1);
        }

        /**

         * Restaura o estado padrao do fluxo.

         */
        function resetFilters() {
            applyFilterReset();
        }

        /**

         * Limpa dados temporarios do fluxo.

         */
        function clearAllActiveFilters() {
            applyFilterReset({
                clearSymbol: true,
                message: 'Todos os filtros ativos foram removidos. Histórico completo recarregado.',
            });
        }

        /**

         * Aplica as configuracoes no fluxo atual.

         */
        function applyRange(rangeValue) {
            const buttons = elements.quickRanges.querySelectorAll('button');
            buttons.forEach((button) => {
                button.setAttribute('aria-pressed', button.dataset.range === String(rangeValue) ? 'true' : 'false');
            });

            if (rangeValue === 'all') {
                elements.dateFrom.value = '';
                elements.dateTo.value = '';
                return;
            }

            const days = Math.max(1, Number(rangeValue));
            const now = new Date();
            const from = new Date();
            from.setDate(now.getDate() - (days - 1));

            elements.dateFrom.value = toISODateInput(from);
            elements.dateTo.value = toISODateInput(now);
        }

        /**

         * Sincroniza o estado entre as fontes envolvidas.

         */
        function syncQuickRangeButtons() {
            const fromValue = elements.dateFrom.value;
            const toValue = elements.dateTo.value;
            const buttons = elements.quickRanges.querySelectorAll('button');

            if (!fromValue && !toValue) {
                buttons.forEach((button) => {
                    button.setAttribute('aria-pressed', button.dataset.range === 'all' ? 'true' : 'false');
                });
                return;
            }

            buttons.forEach((button) => {
                button.setAttribute('aria-pressed', 'false');
            });
        }

        /**

         * Marca o estado do registro atual.

         */
        function markActiveQuickSymbol(value) {
            const normalized = String(value || '').toUpperCase();
            elements.quickSymbols.querySelectorAll('button').forEach((button) => {
                button.setAttribute('aria-pressed', button.dataset.symbol === normalized ? 'true' : 'false');
            });
        }

        /**

         * Verifica o estado da condicao avaliada.

         */
        function isEditableTarget(target) {
            if (!target) {
                return false;
            }

            if (target.isContentEditable) {
                return true;
            }

            const tagName = target.tagName?.toLowerCase();
            return tagName === 'input' || tagName === 'textarea' || tagName === 'select';
        }

        /**

         * Anexa os relacionamentos necessarios.

         */
        function attachKeyboardShortcuts() {
            document.addEventListener('keydown', (event) => {
                const key = String(event.key || '').toLowerCase();
                const hasPrimaryModifier = event.ctrlKey || event.metaKey;

                if (!hasPrimaryModifier && !event.altKey && key === '/' && !isEditableTarget(event.target)) {
                    event.preventDefault();
                    elements.symbol.focus();
                    elements.symbol.select();
                    return;
                }

                if (hasPrimaryModifier && !event.shiftKey && !event.altKey && key === 'enter') {
                    event.preventDefault();
                    fetchQuote(true);
                    return;
                }

                if (hasPrimaryModifier && event.shiftKey && !event.altKey && key === 'r') {
                    event.preventDefault();
                    loadHistory(state.meta?.current_page || 1);
                    return;
                }

                if (event.key === 'Escape') {
                    hideNotice();
                    hideValidationHint();
                }
            });
        }

        /**

         * Anexa os relacionamentos necessarios.

         */
        function attachEventListeners() {
            elements.layoutPresets.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-layout]');
                if (!button) return;

                const mode = button.dataset.layout || 'monitor';
                applyLayoutPreset(mode);
            });

            elements.layoutToggles.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-panel]');
                if (!button) return;

                togglePanelVisibility(button.dataset.panel || '');
            });

            elements.btnFetch.addEventListener('click', () => fetchQuote(false));
            elements.btnSave.addEventListener('click', () => fetchQuote(true));
            elements.btnFilter.addEventListener('click', () => loadHistory(1));
            elements.btnReset.addEventListener('click', resetFilters);
            elements.btnClearActiveFilters.addEventListener('click', clearAllActiveFilters);
            elements.btnRefresh.addEventListener('click', () => loadHistory(state.meta?.current_page || 1));
            elements.btnDeleteFiltered.addEventListener('click', deleteFilteredHistory);

            elements.btnPageFirst.addEventListener('click', () => jumpToPage(1));
            elements.btnPagePrev.addEventListener('click', () => jumpToPage((state.meta?.current_page || 1) - 1));
            elements.btnPageNext.addEventListener('click', () => jumpToPage((state.meta?.current_page || 1) + 1));
            elements.btnPageLast.addEventListener('click', () => jumpToPage(state.meta?.last_page || 1));

            elements.historyRows.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-delete-id]');
                if (!button) return;

                const quotationId = Number.parseInt(String(button.dataset.deleteId || ''), 10);
                if (!Number.isFinite(quotationId) || quotationId <= 0) {
                    return;
                }

                deleteSingleQuotation(quotationId, button.dataset.deleteSymbol || '');
            });

            elements.quickSymbols.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-symbol]');
                if (!button) return;

                const symbol = button.dataset.symbol || '';
                elements.symbol.value = symbol;
                markActiveQuickSymbol(symbol);
                renderActiveFilters();
                updateApplyButtonState();
                savePreferences();
                loadHistory(1);
            });

            elements.quickRanges.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-range]');
                if (!button) return;

                const range = button.dataset.range || 'all';
                applyRange(range);
                validateHistoryFilters();
                renderActiveFilters();
                updateApplyButtonState();
                savePreferences();
                loadHistory(1);
            });

            elements.btnToggleToken.addEventListener('click', () => {
                const isPassword = elements.token.type === 'password';
                elements.token.type = isPassword ? 'text' : 'password';
                elements.btnToggleToken.textContent = isPassword ? 'Ocultar' : 'Mostrar';
            });

            elements.token.addEventListener('input', () => {
                scheduleTokenPermissionCheck();
            });

            elements.token.addEventListener('change', () => {
                refreshUserDeletePermission({ notifyOnFailure: Boolean(elements.token.value.trim()) });
            });

            elements.token.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    refreshUserDeletePermission({ notifyOnFailure: Boolean(elements.token.value.trim()) });
                }
            });

            [
                elements.symbol,
                elements.provider,
                elements.type,
                elements.dateFrom,
                elements.dateTo,
                elements.perPage,
            ].forEach((input) => {
                input.addEventListener('change', () => {
                    if (input === elements.provider) {
                        renderQuickSymbols();
                    }

                    validateHistoryFilters();
                    renderActiveFilters();
                    updateApplyButtonState();
                    savePreferences();
                });
            });

            [elements.dateFrom, elements.dateTo].forEach((input) => {
                input.addEventListener('change', syncQuickRangeButtons);
            });

            elements.symbol.addEventListener('input', () => {
                const cursor = elements.symbol.selectionStart;
                elements.symbol.value = elements.symbol.value.toUpperCase();
                if (cursor !== null) {
                    elements.symbol.setSelectionRange(cursor, cursor);
                }
                markActiveQuickSymbol(elements.symbol.value.trim());
                renderActiveFilters();
                updateApplyButtonState();
            });

            elements.symbol.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    fetchQuote(false);
                }
            });

            attachKeyboardShortcuts();
        }

        /**

         * Executa a rotina principal do metodo bootstrap.

         */
        function bootstrap() {
            loadPreferences();
            applyLayoutState();
            applyDeletePermissionUiState();
            renderQuickSymbols();
            normalizeSymbolInput();
            markActiveQuickSymbol(elements.symbol.value.trim());
            syncQuickRangeButtons();
            validateHistoryFilters();
            renderActiveFilters();
            syncDeleteFilteredButtonState();
            state.lastAppliedFilters = filtersSignature();
            updateApplyButtonState();
            attachEventListeners();
            refreshUserDeletePermission();
            loadHistory(1);
        }

        bootstrap();
    </script>
</body>
</html>
