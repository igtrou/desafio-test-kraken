<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Operações de Coleta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --bg: #f6fbff;
            --surface: #ffffff;
            --line: #d8e6f4;
            --ink: #0f1f2f;
            --muted: #4b6177;
            --accent: #0f766e;
            --accent-soft: rgba(15, 118, 110, 0.12);
            --danger: #be123c;
            --danger-soft: rgba(190, 18, 60, 0.1);
            --shadow: 0 14px 34px rgba(15, 23, 42, 0.09);
        }

        body {
            font-family: "Sora", sans-serif;
            background:
                radial-gradient(860px 460px at -10% -20%, rgba(20, 184, 166, 0.16), transparent),
                radial-gradient(950px 580px at 110% -15%, rgba(56, 189, 248, 0.14), transparent),
                linear-gradient(180deg, #f8fbff, var(--bg));
            color: var(--ink);
        }

        .panel {
            background: color-mix(in srgb, var(--surface) 93%, transparent);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
        }

        .input-base {
            border: 1px solid #c2d7ea;
            background: #fff;
            color: var(--ink);
        }

        .input-base:focus {
            outline: none;
            border-color: #0ea5a3;
            box-shadow: 0 0 0 4px rgba(14, 165, 163, 0.15);
        }

        .btn-primary {
            border: 1px solid transparent;
            background: linear-gradient(135deg, #0ea5a3, #0f766e);
            color: #fff;
        }

        .btn-primary:hover {
            filter: brightness(1.05);
        }

        .btn-ghost {
            border: 1px solid #c2d7ea;
            background: #fff;
            color: #23415f;
        }

        .btn-ghost:hover {
            background: #f6fbff;
        }

        .pill {
            border: 1px solid #cbd9e8;
            background: #fff;
            color: #2d4a66;
        }
    </style>
</head>
<body class="min-h-screen">
    <main class="mx-auto max-w-6xl px-4 py-6 md:px-6 md:py-8">
        <header class="panel rounded-3xl p-5 md:p-7">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="space-y-2">
                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Operations Console</p>
                    <h1 class="text-2xl font-bold md:text-3xl">Agendamento de coleta e execução manual</h1>
                    <p class="max-w-3xl text-sm text-slate-600">
                        Configure os parâmetros de auto-coleta do projeto sem editar o `.env` manualmente e rode uma coleta imediata para validação.
                    </p>
                </div>
        <nav class="flex flex-wrap gap-2 text-xs font-semibold">
            <a href="/dashboard/quotations" class="pill rounded-full px-3 py-1.5">Dashboard de cotações</a>
            <span class="rounded-full border border-teal-300 bg-teal-50 px-3 py-1.5 text-teal-800">Operações</span>
        </nav>
    </div>
</header>

        <section class="mt-6">
            <article class="panel rounded-3xl p-5 md:p-6">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Auto-coleta</p>
                        <h2 class="text-lg font-semibold">Saúde e histórico rápido</h2>
                        <p class="text-sm text-slate-600">Resumo leve das últimas execuções para saber se o job está saudável sem poluir a tela.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div id="scheduler-pill" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] font-semibold text-slate-700">Scheduler: carregando...</div>
                        <div id="run-indicator" class="hidden rounded-full border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800">Execução em andamento</div>
                        <div id="health-badge" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700">Carregando status...</div>
                        <button id="btn-reset-health" type="button" class="btn-ghost rounded-lg px-3 py-1 text-xs font-semibold">Reiniciar saúde</button>
                        <button id="btn-cancel-run" type="button" class="btn-ghost rounded-lg px-3 py-1 text-xs font-semibold">Cancelar coleta</button>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-white/70 p-3 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Última execução</p>
                        <p id="metric-last-run" class="mt-1 text-lg font-semibold text-slate-900">-</p>
                        <p id="metric-last-run-detail" class="text-xs text-slate-600">Aguardando histórico.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/70 p-3 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Falhas recentes</p>
                        <p id="metric-errors" class="mt-1 text-lg font-semibold text-rose-700">-</p>
                        <p id="metric-errors-detail" class="text-xs text-slate-600">Aguardando histórico.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/70 p-3 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Taxa de sucesso</p>
                        <div class="mt-1 flex items-center gap-2">
                            <p id="metric-success-rate" class="text-lg font-semibold text-emerald-700">-</p>
                            <div class="relative h-2 flex-1 rounded-full bg-slate-200">
                                <div id="metric-success-bar" class="absolute inset-y-0 left-0 rounded-full bg-emerald-500" style="width: 0%;"></div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-600" id="metric-window-label">Baseado nas últimas execuções.</p>
                    </div>
                </div>

                <div id="last-error-box" class="mt-4 hidden rounded-2xl border border-amber-200 bg-amber-50 p-3 text-amber-900">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-wide">Último erro</p>
                        <button id="btn-toggle-last-error" type="button" class="hidden text-xs font-semibold text-amber-900 underline underline-offset-2">Ver completo</button>
                    </div>
                    <p id="last-error-text" class="mt-1 text-sm leading-5">-</p>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-white/80 p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Última execução detalhada</p>
                            <p class="text-sm text-slate-600">Saída resumida estilo console para a execução mais recente.</p>
                        </div>
                        <span id="last-run-summary" class="text-xs font-semibold text-slate-500">Carregando...</span>
                    </div>
                    <pre id="last-run-output" class="mt-2 max-h-56 overflow-auto rounded-xl border border-slate-200 bg-slate-950 p-3 text-xs text-slate-100">Aguardando histórico...</pre>
                </div>

                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-800">Histórico recente de execuções</p>
                        <div class="flex items-center gap-2">
                            <button id="btn-toggle-history" type="button" class="btn-ghost rounded-lg px-3 py-1 text-xs font-semibold">Visualizar tudo</button>
                            <button id="btn-reload-history" type="button" class="btn-ghost rounded-lg px-3 py-1 text-xs font-semibold">Recarregar</button>
                        </div>
                    </div>
                    <div id="history-panel" class="mt-3 hidden rounded-xl border border-slate-100">
                        <div class="grid grid-cols-12 gap-3 border-b border-slate-100 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                            <span class="col-span-4">Data & run</span>
                            <span class="col-span-3">Status</span>
                            <span class="col-span-3">Símbolos</span>
                            <span class="col-span-2 text-right">Resumo</span>
                        </div>
                        <div id="history-rows" class="divide-y divide-slate-100"></div>
                        <p id="history-empty" class="hidden px-3 py-3 text-sm text-slate-600">Nenhuma execução registrada ainda.</p>
                        <div id="history-pagination" class="hidden items-center justify-between border-t border-slate-100 px-3 py-2 text-xs text-slate-600">
                            <button id="btn-history-prev" type="button" class="btn-ghost rounded-lg px-3 py-1 text-xs font-semibold">Anterior</button>
                            <p id="history-page-info">Página 1 de 1</p>
                            <button id="btn-history-next" type="button" class="btn-ghost rounded-lg px-3 py-1 text-xs font-semibold">Próxima</button>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <article class="panel rounded-3xl p-5 md:p-6">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold">Configurar auto-coleta</h2>
                    <p class="mt-1 text-sm text-slate-600">Define `QUOTATIONS_AUTO_COLLECT_*` no `.env` local.</p>
                </div>

                <form id="schedule-form" class="space-y-4">
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <input id="enabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-500">
                        <span class="text-sm text-slate-700">Auto-coleta habilitada</span>
                    </label>

                    <label class="block space-y-1 text-sm">
                        <span class="text-slate-600">Intervalo (minutos)</span>
                        <input id="interval-minutes" type="number" min="1" max="59" class="input-base w-full rounded-xl px-3 py-2.5">
                    </label>

                    <label class="block space-y-1 text-sm">
                        <span class="text-slate-600">Símbolos (separados por vírgula)</span>
                        <textarea id="symbols-csv" rows="3" class="input-base w-full rounded-xl px-3 py-2.5" placeholder="BTC,ETH,MSFT,USD-BRL"></textarea>
                    </label>

                    <label class="block space-y-1 text-sm">
                        <span class="text-slate-600">Provider fixo (opcional)</span>
                        <select id="provider" class="input-base w-full rounded-xl px-3 py-2.5">
                            <option value="">fallback por tipo (default)</option>
                        </select>
                    </label>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        <p><strong>Cron gerado:</strong> <span id="cron-expression">-</span></p>
                        <p class="mt-1"><strong>Observação:</strong> <span id="scheduler-note">-</span></p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button id="btn-save-schedule" type="submit" class="btn-primary rounded-xl px-4 py-2 text-sm font-semibold">Salvar agendamento</button>
                        <button id="btn-reload-schedule" type="button" class="btn-ghost rounded-xl px-4 py-2 text-sm font-semibold">Recarregar do ambiente</button>
                    </div>
                </form>
            </article>

            <article class="panel rounded-3xl p-5 md:p-6">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold">Executar coleta agora</h2>
                    <p class="mt-1 text-sm text-slate-600">Dispara `quotations:collect` e mostra o output no painel.</p>
                </div>

                <form id="run-form" class="space-y-4">
                    <label class="block space-y-1 text-sm">
                        <span class="text-slate-600">Símbolos (opcional)</span>
                        <input id="run-symbols" class="input-base w-full rounded-xl px-3 py-2.5" placeholder="vazio = usa símbolos configurados">
                    </label>

                    <label class="block space-y-1 text-sm">
                        <span class="text-slate-600">Provider (opcional)</span>
                        <select id="run-provider" class="input-base w-full rounded-xl px-3 py-2.5">
                            <option value="">fallback por tipo (default)</option>
                        </select>
                    </label>

                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <input id="dry-run" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-500">
                        <span class="text-sm text-slate-700">Dry-run (não persiste)</span>
                    </label>

                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <input id="force-provider" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-500">
                        <span class="text-sm text-slate-700">Forçar provider fixo (desativa fallback automático)</span>
                    </label>

                    <button id="btn-run-collect" type="submit" class="btn-primary rounded-xl px-4 py-2 text-sm font-semibold">Executar coleta</button>
                </form>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-3">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-700">Saída do comando</h3>
                        <p id="run-meta" class="text-xs text-slate-500">Aguardando execução</p>
                    </div>
                    <pre id="run-output" class="max-h-80 overflow-auto rounded-xl border border-slate-200 bg-slate-950 p-3 text-xs text-slate-100">Sem saída ainda.</pre>
                </div>
            </article>
        </section>

        <section class="mt-6">
            <div id="notice" class="hidden rounded-2xl border px-4 py-3 text-sm"></div>
        </section>
    </main>

    <script>
        const elements = {
            enabled: document.getElementById('enabled'),
            intervalMinutes: document.getElementById('interval-minutes'),
            symbolsCsv: document.getElementById('symbols-csv'),
            provider: document.getElementById('provider'),
            cronExpression: document.getElementById('cron-expression'),
            schedulerNote: document.getElementById('scheduler-note'),
            scheduleForm: document.getElementById('schedule-form'),
            btnReloadSchedule: document.getElementById('btn-reload-schedule'),
            runForm: document.getElementById('run-form'),
            runSymbols: document.getElementById('run-symbols'),
            runProvider: document.getElementById('run-provider'),
            dryRun: document.getElementById('dry-run'),
            forceProvider: document.getElementById('force-provider'),
            runOutput: document.getElementById('run-output'),
            runMeta: document.getElementById('run-meta'),
            notice: document.getElementById('notice'),
            healthBadge: document.getElementById('health-badge'),
            metricLastRun: document.getElementById('metric-last-run'),
            metricLastRunDetail: document.getElementById('metric-last-run-detail'),
            metricErrors: document.getElementById('metric-errors'),
            metricErrorsDetail: document.getElementById('metric-errors-detail'),
            metricSuccessRate: document.getElementById('metric-success-rate'),
            metricSuccessBar: document.getElementById('metric-success-bar'),
            metricWindowLabel: document.getElementById('metric-window-label'),
            historyRows: document.getElementById('history-rows'),
            historyEmpty: document.getElementById('history-empty'),
            btnReloadHistory: document.getElementById('btn-reload-history'),
            btnToggleHistory: document.getElementById('btn-toggle-history'),
            historyPanel: document.getElementById('history-panel'),
            historyPagination: document.getElementById('history-pagination'),
            historyPageInfo: document.getElementById('history-page-info'),
            btnHistoryPrev: document.getElementById('btn-history-prev'),
            btnHistoryNext: document.getElementById('btn-history-next'),
            lastErrorBox: document.getElementById('last-error-box'),
            lastErrorText: document.getElementById('last-error-text'),
            btnToggleLastError: document.getElementById('btn-toggle-last-error'),
            lastRunOutput: document.getElementById('last-run-output'),
            lastRunSummary: document.getElementById('last-run-summary'),
            btnResetHealth: document.getElementById('btn-reset-health'),
            btnCancelRun: document.getElementById('btn-cancel-run'),
            runIndicator: document.getElementById('run-indicator'),
            schedulerPill: document.getElementById('scheduler-pill'),
        };

        const HISTORY_FETCH_LIMIT = 100;
        const HISTORY_PAGE_SIZE = 10;
        const historyState = {
            expanded: false,
            currentPage: 1,
            allRuns: [],
        };
        const lastErrorState = {
            expanded: false,
            fullText: '',
        };
        const POLL_INTERVAL_IDLE_MS = 15000;
        const POLL_INTERVAL_RUNNING_MS = 5000;
        let refreshTimerId = null;

        /**

         * Exibe os dados solicitados.

         */
        function showNotice(message, tone = 'info') {
            const toneClass = {
                success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
                danger: 'border-rose-200 bg-rose-50 text-rose-800',
                info: 'border-slate-200 bg-slate-50 text-slate-700',
            };

            elements.notice.className = `rounded-2xl border px-4 py-3 text-sm ${toneClass[tone] || toneClass.info}`;
            elements.notice.textContent = message;
            elements.notice.classList.remove('hidden');
        }

        /**

         * Interpreta os dados recebidos.

         */
        function parseSymbolsCsv(value) {
            return value
                .split(',')
                .map((item) => item.trim().toUpperCase())
                .filter(Boolean);
        }

        /**

         * Executa a rotina principal do metodo requestJson.

         */
        async function requestJson(url, options = {}) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const headers = {
                Accept: 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                ...(options.headers || {}),
            };

            const response = await fetch(url, {
                ...options,
                headers,
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const errorMessage = payload?.message || `Falha na requisição (${response.status}).`;
                throw new Error(errorMessage);
            }

            return payload;
        }

        async function loadRunningStatus() {
            const result = await requestJson('/dashboard/operations/auto-collect/status');
            const running = Boolean(result?.running);
            const data = result?.data || null;

            if (elements.runIndicator) {
                if (running) {
                    const startedAt = formatDateTime(data?.started_at || data?.startedAt);
                    const symbolInfo = Array.isArray(data?.symbols) && data.symbols.length > 0
                        ? ` | símbolos: ${data.symbols.join(', ')}`
                        : '';
                    elements.runIndicator.textContent = `Execução em andamento • início ${startedAt}${symbolInfo}`;
                    elements.runIndicator.classList.remove('hidden');
                } else {
                    elements.runIndicator.classList.add('hidden');
                }
            }

            return running;
        }

        function formatDateTime(value) {
            if (!value) {
                return '-';
            }

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return '-';
            }

            try {
                return new Intl.DateTimeFormat('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    timeZoneName: 'short',
                }).format(date);
            } catch (error) {
                return date.toLocaleString('pt-BR');
            }
        }

        function normalizeStatus(status) {
            return String(status || 'desconhecido').toLowerCase();
        }

        function statusTone(status) {
            const normalized = normalizeStatus(status);

            if (normalized === 'success') {
                return {
                    label: 'Sucesso',
                    className: 'border-emerald-200 bg-emerald-50 text-emerald-800',
                };
            }

            if (normalized === 'partial') {
                return {
                    label: 'Parcial',
                    className: 'border-amber-200 bg-amber-50 text-amber-800',
                };
            }

            if (normalized === 'failed') {
                return {
                    label: 'Falha',
                    className: 'border-rose-200 bg-rose-50 text-rose-800',
                };
            }

            if (normalized === 'canceled') {
                return {
                    label: 'Cancelada',
                    className: 'border-amber-300 bg-amber-50 text-amber-800',
                };
            }

            return {
                label: 'Desconhecido',
                className: 'border-slate-200 bg-slate-50 text-slate-700',
            };
        }

        function formatSymbolsList(symbols = []) {
            if (!Array.isArray(symbols) || symbols.length === 0) {
                return '—';
            }

            if (symbols.length > 4) {
                return `${symbols.slice(0, 4).join(', ')} +${symbols.length - 4}`;
            }

            return symbols.join(', ');
        }

        function formatSummary(summary) {
            const total = Number(summary?.total ?? 0);
            const success = Number(summary?.success ?? 0);
            const failed = Number(summary?.failed ?? 0);

            if (total === 0 && success === 0 && failed === 0) {
                return '–';
            }

            return `${success} ok • ${failed} falha(s)`;
        }

        function truncateText(text, maxLength = 190) {
            if (typeof text !== 'string' || text.length <= maxLength) {
                return text;
            }

            return `${text.slice(0, maxLength).trimEnd()}...`;
        }

        function renderLastErrorText() {
            if (! elements.lastErrorText || ! elements.btnToggleLastError) {
                return;
            }

            const fullText = lastErrorState.fullText || '';
            const truncatedText = truncateText(fullText);
            const shouldCollapse = truncatedText !== fullText;

            elements.lastErrorText.textContent = lastErrorState.expanded || !shouldCollapse
                ? fullText
                : truncatedText;

            elements.btnToggleLastError.classList.toggle('hidden', !shouldCollapse);
            elements.btnToggleLastError.textContent = lastErrorState.expanded
                ? 'Ocultar'
                : 'Ver completo';
        }

        function computeHistoryStats(runs = []) {
            const stats = {
                runCount: runs.length,
                totalSymbols: 0,
                successSymbols: 0,
                failedSymbols: 0,
                runsWithErrors: 0,
                statusCounts: {
                    success: 0,
                    partial: 0,
                    failed: 0,
                    canceled: 0,
                    other: 0,
                },
                lastRun: runs[0] ?? null,
                lastError: null,
                lastSchedulerRun: null,
            };

            runs.forEach((run) => {
                const normalizedStatus = normalizeStatus(run?.status);

                if (stats.statusCounts[normalizedStatus] !== undefined) {
                    stats.statusCounts[normalizedStatus] += 1;
                } else {
                    stats.statusCounts.other += 1;
                }

                const summary = run?.summary || {};
                const success = Number(summary.success ?? 0);
                const failed = Number(summary.failed ?? 0);
                const total = Number(summary.total ?? success + failed);

                stats.totalSymbols += Number.isFinite(total) ? total : 0;
                stats.successSymbols += Number.isFinite(success) ? success : 0;
                stats.failedSymbols += Number.isFinite(failed) ? failed : 0;

                const runHasError = failed > 0 || normalizedStatus === 'failed' || (run?.exit_code ?? 0) !== 0;

                if (runHasError) {
                    stats.runsWithErrors += 1;

                    if (! stats.lastError) {
                        const errorItem = Array.isArray(run?.items)
                            ? run.items.find((item) => normalizeStatus(item?.status) === 'error')
                            : null;

                        stats.lastError = {
                            message: errorItem?.message || run?.error_message || 'Falha registrada.',
                            symbol: errorItem?.symbol || (Array.isArray(run?.symbols) ? run.symbols[0] : null),
                            runId: run?.run_id || '—',
                            finishedAt: run?.finished_at || run?.started_at || null,
                        };
                    }
                }

                if ((run.trigger || '').toLowerCase() === 'scheduler') {
                    const currentFinished = stats.lastSchedulerRun?.finished_at || stats.lastSchedulerRun?.finishedAt;
                    const candidateFinished = run?.finished_at || run?.finishedAt;

                    if (! currentFinished || new Date(candidateFinished).getTime() > new Date(currentFinished).getTime()) {
                        stats.lastSchedulerRun = run;
                    }
                }
            });

            const successRate = stats.totalSymbols > 0
                ? Math.round((stats.successSymbols / Math.max(stats.totalSymbols, 1)) * 100)
                : null;

            return {
                ...stats,
                successRate,
            };
        }

        function healthToneForStats(stats) {
            if (! stats || stats.runCount === 0) {
                return {
                    label: 'Sem execuções',
                    className: 'border-slate-200 bg-slate-50 text-slate-700',
                };
            }

            if (stats.failedSymbols === 0) {
                return {
                    label: 'Saudável',
                    className: 'border-emerald-200 bg-emerald-50 text-emerald-800',
                };
            }

            if (stats.runsWithErrors <= Math.ceil(stats.runCount / 3)) {
                return {
                    label: 'Instável',
                    className: 'border-amber-200 bg-amber-50 text-amber-800',
                };
            }

            return {
                label: 'Com falhas',
                className: 'border-rose-200 bg-rose-50 text-rose-800',
            };
        }

        function renderHealthSummary(runs = []) {
            const stats = computeHistoryStats(runs);
            const windowLabel = `Baseado nas últimas ${Math.min(HISTORY_FETCH_LIMIT, stats.runCount || HISTORY_FETCH_LIMIT)} execuções.`;

            if (elements.metricWindowLabel) {
                elements.metricWindowLabel.textContent = windowLabel;
            }

            const tone = healthToneForStats(stats);
            elements.healthBadge.textContent = tone.label;
            elements.healthBadge.className = `rounded-full px-3 py-1.5 text-xs font-semibold border ${tone.className}`;

            if (elements.schedulerPill) {
                const schedulerRun = stats.lastSchedulerRun;
                if (! schedulerRun) {
                    elements.schedulerPill.textContent = 'Scheduler: nenhum registro ainda';
                    elements.schedulerPill.title = '';
                    elements.schedulerPill.className = 'rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] font-semibold text-slate-700';
                } else {
                    const schedulerTone = statusTone(schedulerRun.status);
                    const finished = formatDateTime(schedulerRun.finished_at || schedulerRun.started_at);
                    const exit = schedulerRun.exit_code ?? '-';
                    elements.schedulerPill.textContent = `Scheduler: ${schedulerTone.label}`;
                    elements.schedulerPill.title = `Último scheduler: ${finished} | exit ${exit}`;
                    elements.schedulerPill.className = `rounded-full border px-3 py-1.5 text-[11px] font-semibold ${schedulerTone.className}`;
                }
            }

            if (! stats.lastRun) {
                elements.metricLastRun.textContent = 'Sem execuções registradas';
                elements.metricLastRunDetail.textContent = 'Aguardando primeira execução do scheduler ou painel.';
                elements.metricErrors.textContent = '0';
                elements.metricErrorsDetail.textContent = 'Sem dados suficientes.';
                elements.metricSuccessRate.textContent = '–';
                elements.metricSuccessBar.style.width = '0%';
                elements.lastErrorBox.classList.add('hidden');
                lastErrorState.fullText = '';
                lastErrorState.expanded = false;
                renderLastErrorText();
                return;
            }

            const lastRun = stats.lastRun;
            const runTone = statusTone(lastRun.status);
            const summary = lastRun.summary || {};
            const provider = lastRun.effective_provider || lastRun.requested_provider || 'fallback';
            const trigger = lastRun.trigger || 'desconhecido';

            elements.metricLastRun.textContent = `${runTone.label} • ${formatDateTime(lastRun.finished_at || lastRun.started_at)}`;
            elements.metricLastRunDetail.textContent = `${trigger} | prov: ${provider} | ${formatSummary(summary)}`;

            elements.metricErrors.textContent = `${stats.failedSymbols}`;
            elements.metricErrorsDetail.textContent = `${stats.runsWithErrors} de ${Math.max(stats.runCount, 1)} execuções tiveram falhas`;

            const successRate = stats.successRate;
            elements.metricSuccessRate.textContent = successRate !== null ? `${successRate}%` : '–';
            elements.metricSuccessBar.style.width = successRate !== null
                ? `${Math.min(100, Math.max(0, successRate))}%`
                : '0%';

            if (stats.lastError) {
                lastErrorState.expanded = false;
                lastErrorState.fullText = `${stats.lastError.message}${stats.lastError.symbol ? ` | símbolo: ${stats.lastError.symbol}` : ''} (${formatDateTime(stats.lastError.finishedAt)})`;
                renderLastErrorText();
                elements.lastErrorBox.classList.remove('hidden');
            } else {
                elements.lastErrorBox.classList.add('hidden');
                lastErrorState.fullText = '';
                lastErrorState.expanded = false;
                renderLastErrorText();
            }
        }

        function renderHistoryRows(runs = []) {
            if (! elements.historyRows) {
                return;
            }

            elements.historyRows.innerHTML = '';

            if (! Array.isArray(runs) || runs.length === 0) {
                elements.historyEmpty?.classList.remove('hidden');
                return;
            }

            elements.historyEmpty?.classList.add('hidden');

            const fragment = document.createDocumentFragment();

            runs.forEach((run) => {
                const row = document.createElement('div');
                row.className = 'grid grid-cols-12 gap-3 px-3 py-3 text-sm';

                const colDate = document.createElement('div');
                colDate.className = 'col-span-4';
                const dateLine = document.createElement('p');
                dateLine.className = 'font-semibold text-slate-800';
                dateLine.textContent = formatDateTime(run.finished_at || run.started_at);
                const idLine = document.createElement('p');
                idLine.className = 'text-xs text-slate-500';
                const runId = run.run_id ? `#${String(run.run_id).slice(0, 8)}` : 'sem id';
                idLine.textContent = `${runId} • trigger ${run.trigger || '-'}`;
                colDate.append(dateLine, idLine);

                const colStatus = document.createElement('div');
                colStatus.className = 'col-span-3 flex items-center gap-2';
                const badge = document.createElement('span');
                const tone = statusTone(run.status);
                badge.className = `rounded-full border px-2 py-1 text-xs font-semibold ${tone.className}`;
                badge.textContent = tone.label;
                const providerText = document.createElement('span');
                providerText.className = 'text-xs text-slate-500';
                providerText.textContent = run.effective_provider || run.requested_provider || 'fallback';
                colStatus.append(badge, providerText);

                const colSymbols = document.createElement('div');
                colSymbols.className = 'col-span-3';
                const symbolsLine = document.createElement('p');
                symbolsLine.className = 'font-semibold text-slate-800';
                symbolsLine.textContent = formatSymbolsList(run.symbols || []);
                const summaryLine = document.createElement('p');
                summaryLine.className = 'text-xs text-slate-500';
                summaryLine.textContent = formatSummary(run.summary);
                colSymbols.append(symbolsLine, summaryLine);

                const colMeta = document.createElement('div');
                colMeta.className = 'col-span-2 text-right text-xs text-slate-600';
                const exitCode = document.createElement('p');
                exitCode.textContent = `Exit ${run.exit_code ?? '-'}`;
                colMeta.append(exitCode);

                row.append(colDate, colStatus, colSymbols, colMeta);
                fragment.append(row);
            });

            elements.historyRows.append(fragment);
        }

        function getHistoryTotalPages() {
            return Math.max(1, Math.ceil(historyState.allRuns.length / HISTORY_PAGE_SIZE));
        }

        function getHistoryPageRuns() {
            const startIndex = (historyState.currentPage - 1) * HISTORY_PAGE_SIZE;
            const endIndex = startIndex + HISTORY_PAGE_SIZE;

            return historyState.allRuns.slice(startIndex, endIndex);
        }

        function setHistoryPanelExpanded(expanded) {
            historyState.expanded = expanded;

            if (elements.historyPanel) {
                elements.historyPanel.classList.toggle('hidden', !expanded);
            }

            if (elements.btnToggleHistory) {
                const total = historyState.allRuns.length;
                elements.btnToggleHistory.textContent = expanded
                    ? 'Ocultar histórico'
                    : `Visualizar tudo (${total})`;
            }
        }

        function updateHistoryPaginationControls() {
            if (! elements.historyPagination || ! elements.historyPageInfo || ! elements.btnHistoryPrev || ! elements.btnHistoryNext) {
                return;
            }

            if (historyState.allRuns.length <= HISTORY_PAGE_SIZE) {
                elements.historyPagination.classList.add('hidden');
                elements.historyPagination.classList.remove('flex');
                return;
            }

            const totalPages = getHistoryTotalPages();
            elements.historyPagination.classList.remove('hidden');
            elements.historyPagination.classList.add('flex');
            elements.historyPageInfo.textContent = `Página ${historyState.currentPage} de ${totalPages}`;

            const isFirstPage = historyState.currentPage <= 1;
            const isLastPage = historyState.currentPage >= totalPages;

            elements.btnHistoryPrev.disabled = isFirstPage;
            elements.btnHistoryNext.disabled = isLastPage;
            elements.btnHistoryPrev.classList.toggle('opacity-40', isFirstPage);
            elements.btnHistoryPrev.classList.toggle('cursor-not-allowed', isFirstPage);
            elements.btnHistoryNext.classList.toggle('opacity-40', isLastPage);
            elements.btnHistoryNext.classList.toggle('cursor-not-allowed', isLastPage);
        }

        function renderHistoryPanel() {
            const totalPages = getHistoryTotalPages();
            historyState.currentPage = Math.min(Math.max(historyState.currentPage, 1), totalPages);

            if (! historyState.expanded) {
                setHistoryPanelExpanded(false);
                return;
            }

            setHistoryPanelExpanded(true);
            renderHistoryRows(getHistoryPageRuns());
            updateHistoryPaginationControls();
        }

        function renderLastRunDetail(run = null) {
            if (! elements.lastRunOutput || ! elements.lastRunSummary) {
                return;
            }

            if (! run) {
                elements.lastRunSummary.textContent = 'Sem execuções';
                elements.lastRunOutput.textContent = 'Aguardando histórico...';
                return;
            }

            const lines = [];

            const headerTone = statusTone(run.status);
            lines.push(`[${(run.trigger || 'desconhecido')}] ${headerTone.label} | exit=${run.exit_code ?? '-'} | provider=${run.effective_provider || run.requested_provider || 'fallback'}`);

            const items = Array.isArray(run.items) ? run.items : [];
            items.forEach((item) => {
                if (normalizeStatus(item.status) === 'error') {
                    lines.push(`ERROR: ${item.symbol} | ${item.message || 'Erro não informado'}`);
                } else {
                    const price = item.price !== undefined ? ` | price=${item.price}` : '';
                    const src = item.source ? ` | source=${item.source}` : '';
                    const qid = item.quotation_id !== undefined ? ` | quotation_id=${item.quotation_id}` : '';
                    lines.push(`OK: ${item.symbol}${src}${price}${qid}`);
                }
            });

            const summary = run.summary || {};
            lines.push(`Done. total=${summary.total ?? '-'} success=${summary.success ?? '-'} failed=${summary.failed ?? '-'}`);

            elements.lastRunSummary.textContent = `${headerTone.label} • ${formatDateTime(run.finished_at || run.started_at)}`;
            elements.lastRunOutput.textContent = lines.join('\n');
        }

        async function loadHistory(showNotification = false) {
            try {
                const result = await requestJson(`/dashboard/operations/auto-collect/history?limit=${HISTORY_FETCH_LIMIT}`);
                const runs = Array.isArray(result?.data) ? result.data : [];
                historyState.allRuns = runs;
                historyState.currentPage = Math.min(historyState.currentPage, getHistoryTotalPages());

                renderHealthSummary(runs);
                renderHistoryPanel();
                renderLastRunDetail(runs[0] ?? null);

                if (showNotification) {
                    showNotice('Histórico recarregado.', 'info');
                }
            } catch (error) {
                historyState.allRuns = [];
                historyState.currentPage = 1;
                renderHealthSummary([]);
                renderHistoryPanel();
                renderLastRunDetail(null);
                showNotice(error.message || 'Falha ao carregar histórico de execuções.', 'danger');
            }
        }

        /**

         * Sincroniza o estado entre as fontes envolvidas.

         */
        function syncProviderOptions(providers = [], selected = null) {
            const buildOptions = (selectElement) => {
                const previousValue = selected ?? selectElement.value;

                selectElement.innerHTML = '<option value="">fallback por tipo (default)</option>';

                providers.forEach((provider) => {
                    const option = document.createElement('option');
                    option.value = provider;
                    option.textContent = provider;
                    selectElement.appendChild(option);
                });

                if (previousValue && providers.includes(previousValue)) {
                    selectElement.value = previousValue;
                } else {
                    selectElement.value = '';
                }
            };

            buildOptions(elements.provider);
            buildOptions(elements.runProvider);
        }

        /**

         * Renderiza a saida para o formato esperado.

         */
        function renderAutoCollectSettings(payload) {
            elements.enabled.checked = Boolean(payload.enabled);
            elements.intervalMinutes.value = String(payload.interval_minutes || 15);
            elements.symbolsCsv.value = payload.symbols_csv || '';
            elements.cronExpression.textContent = payload.cron_expression || '-';
            elements.schedulerNote.textContent = payload.scheduler_restart_note || '-';

            syncProviderOptions(payload.available_providers || [], payload.provider || '');
        }

        async function cancelAutoCollect() {
            await requestJson('/dashboard/operations/auto-collect/cancel', {
                method: 'POST',
                body: JSON.stringify({}),
            });
        }

        async function resetAutoCollectHealth() {
            return requestJson('/dashboard/operations/auto-collect/health/reset', {
                method: 'POST',
                body: JSON.stringify({}),
            });
        }

        // Atualiza status e histórico com intervalo dinâmico baseado no estado atual.
        async function refreshStatusAndHistory() {
            if (refreshTimerId) {
                clearTimeout(refreshTimerId);
                refreshTimerId = null;
            }

            try {
                const running = await loadRunningStatus();
                await loadHistory();
                const interval = running ? POLL_INTERVAL_RUNNING_MS : POLL_INTERVAL_IDLE_MS;
                refreshTimerId = window.setTimeout(refreshStatusAndHistory, interval);
            } catch (error) {
                refreshTimerId = window.setTimeout(refreshStatusAndHistory, POLL_INTERVAL_IDLE_MS);
            }
        }

        /**

         * Carrega dados necessarios para a operacao.

         */
        async function loadSettings() {
            const result = await requestJson('/dashboard/operations/auto-collect');
            renderAutoCollectSettings(result.data || {});
        }

        /**

         * Salva os dados persistiveis da operacao.

         */
        async function saveSettings(event) {
            event.preventDefault();

            const payload = {
                enabled: elements.enabled.checked,
                interval_minutes: Number.parseInt(elements.intervalMinutes.value || '15', 10),
                symbols: parseSymbolsCsv(elements.symbolsCsv.value),
                provider: elements.provider.value || null,
            };

            const result = await requestJson('/dashboard/operations/auto-collect', {
                method: 'PUT',
                body: JSON.stringify(payload),
            });

            renderAutoCollectSettings(result.data || {});
            showNotice('Agendamento salvo com sucesso. Se estiver usando schedule:work, reinicie o processo.', 'success');
        }

        /**

         * Executa o processo configurado.

         */
        async function runCollection(event) {
            event.preventDefault();

            elements.runOutput.textContent = 'Executando...';
            elements.runMeta.textContent = 'Em execução';

            const payload = {
                symbols: parseSymbolsCsv(elements.runSymbols.value),
                provider: elements.runProvider.value || null,
                dry_run: elements.dryRun.checked,
                force_provider: elements.forceProvider.checked,
            };

            const result = await requestJson('/dashboard/operations/auto-collect/run', {
                method: 'POST',
                body: JSON.stringify(payload),
            });

            const outputLines = result?.data?.output || [];
            const exitCode = result?.data?.exit_code ?? -1;
            const requestedProvider = result?.data?.requested_provider || 'fallback';
            const effectiveProvider = result?.data?.effective_provider || 'fallback';
            const fallbackSuffix = result?.data?.auto_fallback_applied ? ' | fallback automático aplicado' : '';
            elements.runMeta.textContent = `Exit code: ${exitCode} | provider solicitado: ${requestedProvider} | provider efetivo: ${effectiveProvider}${fallbackSuffix}`;

            const warnings = Array.isArray(result?.data?.warnings) ? result.data.warnings : [];
            const renderedWarnings = warnings.map((warning) => `WARN: ${warning}`);
            const consoleLines = renderedWarnings.length > 0
                ? [...renderedWarnings, '', ...outputLines]
                : outputLines;
            elements.runOutput.textContent = consoleLines.length > 0 ? consoleLines.join('\n') : '(sem saída textual)';
            const warningSuffix = warnings.length > 0 ? ` ${warnings.join(' ')}` : '';
            const summary = result?.data?.summary || null;
            const failedCount = Number(summary?.failed || 0);
            const successCount = Number(summary?.success || 0);

            if (exitCode === 0) {
                if (failedCount > 0 && successCount > 0) {
                    showNotice(`Coleta concluída com sucesso parcial (${successCount} sucesso(s), ${failedCount} falha(s)).${warningSuffix}`.trim(), 'info');
                } else {
                    showNotice(`Coleta executada com sucesso.${warningSuffix}`.trim(), 'success');
                }
            } else {
                showNotice(`Coleta finalizada com falhas. Confira o output.${warningSuffix}`.trim(), 'danger');
            }
        }

        elements.scheduleForm.addEventListener('submit', (event) => {
            saveSettings(event).catch((error) => {
                showNotice(error.message || 'Falha ao salvar agendamento.', 'danger');
            });
        });

        elements.btnReloadSchedule.addEventListener('click', () => {
            loadSettings()
                .then(() => showNotice('Configuração recarregada do ambiente.', 'info'))
                .catch((error) => showNotice(error.message || 'Falha ao recarregar configuração.', 'danger'));
        });

        if (elements.btnReloadHistory) {
            elements.btnReloadHistory.addEventListener('click', () => {
                loadHistory(true);
            });
        }

        if (elements.btnToggleHistory) {
            elements.btnToggleHistory.addEventListener('click', () => {
                setHistoryPanelExpanded(! historyState.expanded);
                if (historyState.expanded) {
                    renderHistoryPanel();
                }
            });
        }

        if (elements.btnHistoryPrev) {
            elements.btnHistoryPrev.addEventListener('click', () => {
                historyState.currentPage = Math.max(1, historyState.currentPage - 1);
                renderHistoryPanel();
            });
        }

        if (elements.btnHistoryNext) {
            elements.btnHistoryNext.addEventListener('click', () => {
                historyState.currentPage = Math.min(getHistoryTotalPages(), historyState.currentPage + 1);
                renderHistoryPanel();
            });
        }

        if (elements.btnToggleLastError) {
            elements.btnToggleLastError.addEventListener('click', () => {
                lastErrorState.expanded = ! lastErrorState.expanded;
                renderLastErrorText();
            });
        }

        if (elements.btnResetHealth) {
            elements.btnResetHealth.addEventListener('click', () => {
                const confirmed = window.confirm('Reiniciar saúde agora? O histórico bruto será preservado, mas os indicadores passam a considerar somente as novas execuções.');

                if (! confirmed) {
                    return;
                }

                elements.btnResetHealth.disabled = true;
                elements.btnResetHealth.classList.add('opacity-40', 'cursor-not-allowed');

                resetAutoCollectHealth()
                    .then((result) => {
                        const resetAtLabel = formatDateTime(result?.health_reset_at);
                        const noticeMessage = resetAtLabel === '-'
                            ? 'Saúde reiniciada com sucesso.'
                            : `Saúde reiniciada com sucesso em ${resetAtLabel}.`;

                        historyState.currentPage = 1;
                        showNotice(noticeMessage, 'success');
                        refreshStatusAndHistory();
                    })
                    .catch((error) => showNotice(error.message || 'Falha ao reiniciar saúde.', 'danger'))
                    .finally(() => {
                        elements.btnResetHealth.disabled = false;
                        elements.btnResetHealth.classList.remove('opacity-40', 'cursor-not-allowed');
                    });
            });
        }

        if (elements.btnCancelRun) {
            elements.btnCancelRun.addEventListener('click', () => {
                cancelAutoCollect()
                    .then(() => {
                        showNotice('Cancelamento solicitado. A execução atual será interrompida em breve.', 'info');
                        refreshStatusAndHistory();
                    })
                    .catch((error) => showNotice(error.message || 'Falha ao solicitar cancelamento.', 'danger'));
            });
        }

        elements.runForm.addEventListener('submit', (event) => {
            runCollection(event).catch((error) => {
                elements.runMeta.textContent = 'Falha';
                elements.runOutput.textContent = error.message || 'Falha ao executar coleta.';
                showNotice(error.message || 'Falha ao executar coleta.', 'danger');
            });
        });

        loadSettings().catch((error) => {
            showNotice(error.message || 'Falha ao carregar configuração inicial.', 'danger');
        });

        setHistoryPanelExpanded(false);
        refreshStatusAndHistory();
    </script>
</body>
</html>
