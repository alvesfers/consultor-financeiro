@extends('layouts.app')

@section('content')
    @php
        $invoicesMonthTitle = !empty($cardInvoices)
            ? \Illuminate\Support\Str::of($cardInvoices[0]['invoice_month'])->replace('-', '/')
            : now()->format('Y/m');

        $goalsTitle = $goalsMonthTitle ?? now()->format('m/Y');

        // ===== Dados para Alpine (transações) =====
        $txForJs = collect($recentTransactions ?? [])->map(function ($t) {
            $date = \Illuminate\Support\Carbon::parse($t->date ?? now());
            $ddmm = $date->format('d/m');
            $cat = $t->category_name ?? null;
            $sub = $t->subcategory_name ?? null;
            $catFull = trim(($cat ?: '—') . ($sub ? " / {$sub}" : ''));
            $note = (string) ($t->notes ?? '');
            $noteBase = preg_replace('/\s*\(\d+\/\d+\)\s*$/', '', $note) ?: null;
            $bankIcon = $t->bank_icon_class ?? 'fa-solid fa-building-columns';

            return [
                'id' => (int) ($t->id ?? 0),
                'account_id' => (int) ($t->account_id ?? 0),
                'account_name' => $t->account_name ?? '—',
                'bank_icon' => $bankIcon,
                'bank_name' => $t->bank_name ?? null,
                'card_id' => (int) ($t->card_id ?? 0),
                'card_name' => $t->card_name ?? null,
                'note' => $note,
                'note_base' => $noteBase,
                'category_full' => $catFull,
                'amount' => (float) ($t->amount ?? 0),
                'installment_total' => (int) ($t->installment_total ?? 0),
                'installment_number' => (int) ($t->installment_number ?? 0),
                'installment_group_id' => (string) ($t->installment_group_id ?? ''),
                'date_iso' => $date->format('Y-m-d'),
                'date_ddmm' => $ddmm,
                'time_hhmm' => $date->format('H:i'),
            ];
        });

        $accountsForJs = ($accounts ?? collect())->map(fn($a) => ['id' => (int) $a->id, 'name' => $a->name])->values();

        // Donut (30d)
        $pieCategories = $pieCategories ?? ($chartCategories ?? ['labels' => [], 'values' => []]);

        // helpers
        $money = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    @endphp

    {{-- =========================
     HERO / HEADER MOBILE
========================= --}}
    <div class="lg:hidden">
        <div
            class="rounded-2xl p-4 bg-gradient-to-br from-base-200/60 to-base-100/60 border border-base-300/70 backdrop-blur">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs opacity-70">Olá,</p>
                    <h1 class="text-xl font-semibold truncate">{{ auth()->user()->name ?? 'Bem-vindo' }}</h1>
                </div>
                <button class="btn btn-ghost btn-sm" onclick="document.getElementById('txModal').showModal()">
                    <i class="fa-solid fa-plus mr-1"></i> Nova
                </button>
            </div>

            {{-- “Cartão saldo” no estilo neon highlight --}}
            <div class="mt-4">
                <div class="card rounded-2xl shadow-sm border border-base-300 overflow-hidden">
                    <div class="card-body p-4 relative">
                        <div class="absolute -inset-x-6 -top-6 h-24 blur-3xl pointer-events-none"
                            style="background: radial-gradient(100px 60px at right top, hsl(var(--p)) 15%, transparent 60%); opacity:.35;">
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-xs opacity-70">Saldo atual</div>
                                <div class="text-3xl font-semibold">
                                    {{ number_format((float) ($balance ?? 0), 2, ',', '.') }}</div>
                            </div>
                            <div class="badge badge-primary badge-lg gap-2">
                                <i class="fa-solid fa-wallet"></i> {{ count($accounts ?? []) }} contas
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-4 gap-2">
                            <button class="btn btn-sm"><i
                                    class="fa-solid fa-arrow-down-wide-short mr-1"></i>Receber</button>
                            <button class="btn btn-sm"><i class="fa-solid fa-arrow-up-right-dots mr-1"></i>Pagar</button>
                            <button class="btn btn-sm"><i class="fa-solid fa-right-left mr-1"></i>Transferir</button>
                            <button class="btn btn-sm" onclick="document.getElementById('investmentModal').showModal()">
                                <i class="fa-solid fa-arrow-trend-up mr-1"></i>Investir
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- “Indicadores rápidos” em chips --}}
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div class="rounded-xl p-3 bg-base-100 border border-base-300">
                    <div class="text-xs opacity-70">Tasks pendentes</div>
                    <div class="text-2xl font-semibold">{{ (int) ($tasksPendingCount ?? 0) }}</div>
                </div>
                <div class="rounded-xl p-3 bg-base-100 border border-base-300">
                    <div class="text-xs opacity-70">Patrimônio</div>
                    <div class="text-2xl font-semibold">{{ number_format((float) ($netWorth ?? 0), 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================
     HEADER DESKTOP/TABLET
========================= --}}
    <div class="hidden lg:flex items-end justify-between mb-5">
        <div>
            <h1 class="text-2xl font-semibold">Meu painel</h1>
            <p class="opacity-70 text-sm">Resumo financeiro e atividades recentes</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="btn" onclick="document.getElementById('investmentModal').showModal()">
                <i class="fa-solid fa-arrow-trend-up mr-2"></i> Investimentos
            </button>
            <button class="btn btn-primary" onclick="document.getElementById('txModal').showModal()">
                <i class="fa-solid fa-plus mr-2"></i> Nova transação
            </button>
        </div>
    </div>

    {{-- =========================
     CARDS / KPIs (desktop/tablet)
========================= --}}
    <div class="hidden md:grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card shadow bg-base-100">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-60">Saldo atual</div>
                        <div class="text-3xl font-semibold">{{ number_format((float) ($balance ?? 0), 2, ',', '.') }}</div>
                    </div>
                    <i class="fa-solid fa-wallet text-3xl text-primary"></i>
                </div>
                <div class="mt-2 text-xs opacity-60">Somatório das contas consideradas no orçamento.</div>
            </div>
        </div>

        <div class="card shadow bg-base-100">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-60">Tasks pendentes</div>
                        <div class="text-3xl font-semibold">{{ (int) ($tasksPendingCount ?? 0) }}</div>
                    </div>
                    <i class="fa-solid fa-list-check text-3xl text-warning"></i>
                </div>
                <a class="link mt-3" href="{{ route('consultant.tasks.index', ['consultant' => $consultantId]) }}">Ver
                    todas</a>
            </div>
        </div>

        <div class="card shadow bg-base-100">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-60">Objetivos pendentes</div>
                        <div class="text-3xl font-semibold">{{ (int) ($goalsPendingCount ?? 0) }}</div>
                    </div>
                    <i class="fa-solid fa-bullseye text-3xl text-accent"></i>
                </div>
                <a class="link mt-3" href="{{ route('client.dashboard', ['consultant' => $consultantId]) }}">Ir para
                    objetivos</a>
            </div>
        </div>

        <div class="card shadow bg-gradient-to-br from-base-200 to-base-100">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-60">Patrimônio</div>
                        <div class="text-3xl font-semibold">{{ number_format((float) ($netWorth ?? 0), 2, ',', '.') }}
                        </div>
                        <div class="mt-1 text-xs opacity-70">
                            Saldo: {{ $money($balance ?? 0) }} • Investido: {{ $money($investedTotal ?? 0) }}
                        </div>
                    </div>
                    <i class="fa-solid fa-chart-line text-3xl text-success"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================
     FATURAS + METAS (responsivo)
========================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- FATURAS --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">
                        <i class="fa-solid fa-credit-card mr-2"></i> Faturas — {{ $invoicesMonthTitle }}
                    </h2>
                    <div class="hidden sm:flex gap-2">
                        <span class="badge badge-ghost">Fechamento/Vencimento</span>
                    </div>
                </div>

                @if (!empty($cardInvoices))
                    {{-- MOBILE: lista em cards --}}
                    <div class="sm:hidden space-y-3 mt-3">
                        @foreach ($cardInvoices as $ci)
                            <div class="rounded-xl border border-base-300 p-3">
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0">
                                        <div class="font-medium truncate">{{ $ci['card']->name }}</div>
                                        <div class="text-xs opacity-70">
                                            {{ $ci['close_date']->format('d') }} → {{ $ci['due_date']->format('d') }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm opacity-70">Fatura</div>
                                        <div class="text-lg font-semibold {{ $ci['total'] > 0 ? 'text-error' : '' }}">
                                            {{ number_format((float) $ci['total'], 2, ',', '.') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 flex items-center justify-between">
                                    <div class="text-xs opacity-70">
                                        Disponível:
                                        <strong>
                                            {{ !is_null($ci['available']) ? number_format((float) $ci['available'], 2, ',', '.') : '—' }}
                                        </strong>
                                    </div>
                                    <div>
                                        @if ($ci['total'] > 0)
                                            @if (!empty($ci['all_paid']))
                                                <span class="badge badge-success">Paga</span>
                                            @else
                                                <form method="POST"
                                                    action="{{ route('client.cards.invoices.pay', ['consultant' => $consultantId, 'card' => $ci['card']->id, 'invoiceMonth' => $ci['invoice_month']]) }}"
                                                    onsubmit="return confirm('Confirmar pagamento da fatura {{ $ci['invoice_month'] }} do cartão {{ $ci['card']->name }}?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="fa-solid fa-money-bill-wave mr-1"></i> Pagar
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="text-xs opacity-60">—</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- DESKTOP/TABLET: tabela --}}
                    <div class="hidden sm:block overflow-x-auto mt-2">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cartão</th>
                                    <th class="text-right">Fatura (R$)</th>
                                    <th class="text-right">Disponível</th>
                                    <th class="text-center">Ciclo</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cardInvoices as $ci)
                                    <tr>
                                        <td>{{ $ci['card']->name }}</td>
                                        <td class="text-right {{ $ci['total'] > 0 ? 'text-error' : '' }} font-medium">
                                            {{ number_format((float) $ci['total'], 2, ',', '.') }}
                                        </td>
                                        <td class="text-right">
                                            {{ !is_null($ci['available']) ? number_format((float) $ci['available'], 2, ',', '.') : '—' }}
                                        </td>
                                        <td class="text-center">
                                            {{ $ci['close_date']->format('d') }} → {{ $ci['due_date']->format('d') }}
                                        </td>
                                        <td class="text-center">
                                            @if ($ci['total'] > 0)
                                                @if (!empty($ci['all_paid']))
                                                    <span class="badge badge-success">Paga</span>
                                                @else
                                                    <form method="POST"
                                                        action="{{ route('client.cards.invoices.pay', ['consultant' => $consultantId, 'card' => $ci['card']->id, 'invoiceMonth' => $ci['invoice_month']]) }}"
                                                        onsubmit="return confirm('Confirmar pagamento da fatura {{ $ci['invoice_month'] }} do cartão {{ $ci['card']->name }}?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="fa-solid fa-money-bill-wave mr-1"></i> Pagar fatura
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="text-xs opacity-60">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="mt-2 text-sm opacity-70">Nenhuma fatura para exibir.</div>
                @endif
            </div>
        </div>

        {{-- METAS (sem barra, com “tinta” leve na linha) --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fa-solid fa-bullseye mr-2"></i> Metas do mês — {{ $goalsTitle }}
                </h2>

                @if (!empty($goalsComparative) && count($goalsComparative))
                    <div class="mt-2 space-y-2">
                        @foreach ($goalsComparative as $row)
                            @php
                                $ratio = (float) ($row['ratio'] ?? 0);
                                $bg = $row['exceeded']
                                    ? 'bg-error/10 border-error/30'
                                    : 'bg-success/10 border-success/30';
                            @endphp
                            <div class="rounded-xl border {{ $bg }} p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-medium truncate">{{ $row['category_name'] }}</div>
                                        <div class="text-xs opacity-70 mt-0.5">
                                            Meta: {{ $money($row['limit']) }} • Gasto: <b>{{ $money($row['spent']) }}</b>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="badge {{ $row['exceeded'] ? 'badge-error' : 'badge-success' }}">
                                            {{ number_format(min(100, $ratio * 100), 0) }}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-2 text-sm opacity-70">Nenhuma meta definida para este mês.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- =========================
     DONUT + TRANSAÇÕES (mobile: cards)
========================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Donut 30d --}}
        <div class="card bg-base-100 shadow lg:col-span-1">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">
                        <i class="fa-solid fa-chart-pie mr-2"></i> Despesas por categoria
                    </h2>
                    <div class="badge badge-ghost text-xs">30d</div>
                </div>
                <div class="mt-3"><canvas id="chartPieExpenses30d" height="240"></canvas></div>
                <div class="text-sm opacity-70" id="pie-empty" style="display:none;">Sem dados nos últimos 30 dias.</div>
            </div>
        </div>

        {{-- Transações --}}
        <div class="card bg-base-100 shadow lg:col-span-2" x-data="txList()" x-init="init()">
            <div class="card-body">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="card-title">
                        <i class="fa-solid fa-arrow-right-arrow-left mr-2"></i> Transações recentes
                    </h2>

                    {{-- filtros compactos no mobile (sheet) --}}
                    <div class="md:hidden">
                        <details class="dropdown dropdown-end">
                            <summary class="btn btn-ghost btn-sm"><i class="fa-solid fa-sliders"></i></summary>
                            <div
                                class="menu dropdown-content bg-base-200 rounded-box w-64 p-3 mt-2 border border-base-300 z-[5]">
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Tipo</span></label>
                                    <select class="select select-bordered select-sm" x-model="filters.type">
                                        <option value="">Todos</option>
                                        <option value="income">Ganhos</option>
                                        <option value="expense">Gastos</option>
                                        <option value="transfer">Transferências</option>
                                    </select>
                                </div>

                                <div class="form-control mt-2">
                                    <label class="label"><span class="label-text">Conta</span></label>
                                    <select class="select select-bordered select-sm" x-model.number="filters.account_id">
                                        <option value="0">Todas</option>
                                        <template x-for="a in accounts" :key="a.id">
                                            <option :value="a.id" x-text="a.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="form-control mt-2">
                                    <label class="label"><span class="label-text">Buscar</span></label>
                                    <input type="text" class="input input-bordered input-sm" placeholder="texto…"
                                        x-model.trim="filters.q" @keydown.enter.prevent>
                                </div>

                                <label class="label cursor-pointer gap-2 mt-2">
                                    <span class="label-text">Agrupar parcelas</span>
                                    <input type="checkbox" class="toggle toggle-sm" x-model="filters.groupInstallments">
                                </label>
                            </div>
                        </details>
                    </div>

                    {{-- filtros completos (md+) --}}
                    <div class="hidden md:flex gap-2 items-center">
                        <select class="select select-bordered select-sm" x-model="filters.type">
                            <option value="">Todos</option>
                            <option value="income">Ganhos</option>
                            <option value="expense">Gastos</option>
                            <option value="transfer">Transferências</option>
                        </select>

                        <select class="select select-bordered select-sm" x-model.number="filters.account_id">
                            <option value="0">Todas as contas</option>
                            <template x-for="a in accounts" :key="a.id">
                                <option :value="a.id" x-text="a.name"></option>
                            </template>
                        </select>

                        <input type="text" class="input input-bordered input-sm" placeholder="buscar…"
                            x-model.trim="filters.q" @keydown.enter.prevent />

                        <label class="label cursor-pointer gap-2">
                            <span class="label-text text-sm">Agrupar parcelas</span>
                            <input type="checkbox" class="toggle toggle-sm" x-model="filters.groupInstallments">
                        </label>
                    </div>
                </div>

                {{-- MOBILE: lista em cards --}}
                <div class="md:hidden mt-3 space-y-3">
                    <template x-if="rows.length === 0">
                        <div class="text-sm opacity-70">Sem transações.</div>
                    </template>

                    <template x-for="r in rows" :key="r.key">
                        <div class="rounded-xl border border-base-300 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 text-sm">
                                        <i :class="r.bank_icon"></i>
                                        <span class="truncate" x-text="r.account_name || r.bank_name || '—'"></span>
                                        <span class="opacity-60">•</span>
                                        <span class="truncate" x-text="r.card_name || 'Conta'"></span>
                                    </div>

                                    <div class="mt-1 font-medium truncate" x-text="r.note_display || '—'"></div>
                                    <div class="text-xs opacity-70 truncate" x-text="r.category_full || '—'"></div>
                                </div>

                                <div class="text-right shrink-0">
                                    <div :class="r.amount < 0 ? 'text-error' : 'text-success'" class="font-semibold">
                                        <span x-text="formatBRL(r.amount)"></span>
                                    </div>
                                    <div class="text-xs opacity-70 mt-1" x-text="r.date_ddmm"></div>
                                    <template x-if="r.installment_info">
                                        <div class="badge badge-ghost mt-1" x-text="r.installment_info"></div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- DESKTOP/TABLET: tabela --}}
                <div class="hidden md:block overflow-x-auto mt-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Banco</th>
                                <th>Conta</th>
                                <th>Cartão</th>
                                <th>Nota</th>
                                <th>Categoria</th>
                                <th class="text-right">Valor</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="rows.length === 0">
                                <tr>
                                    <td colspan="7" class="opacity-70">Sem transações.</td>
                                </tr>
                            </template>

                            <template x-for="r in rows" :key="r.key">
                                <tr>
                                    <td><i :class="r.bank_icon"></i> <span class="ml-2"
                                            x-text="r.bank_name || '—'"></span></td>
                                    <td x-text="r.account_name || '—'"></td>
                                    <td x-text="r.card_name || '—'"></td>
                                    <td x-text="r.note_display || '—'"></td>
                                    <td x-text="r.category_full || '—'"></td>
                                    <td class="text-right" :class="r.amount < 0 ? 'text-error' : 'text-success'">
                                        <span x-text="formatBRL(r.amount)"></span>
                                        <template x-if="r.installment_info">
                                            <span class="opacity-70 text-xs ml-1"
                                                x-text="`(${r.installment_info})`"></span>
                                        </template>
                                    </td>
                                    <td x-text="r.date_ddmm"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== MODAIS ===== --}}
    @include('consultants.clients.partials.modal-transaction')
    @include('consultants.clients.partials.modal-investment')

    {{-- ===== SCRIPTS ===== --}}
    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

        <script>
            // datasets globais
            window.TX_INIT = @js($txForJs);
            window.ACCOUNTS = @js($accountsForJs);
            window.PIE_DATA = @js($pieCategories ?? ['labels' => [], 'values' => []]);

            // Donut
            (function() {
                const data = window.PIE_DATA || {
                    labels: [],
                    values: []
                };
                const el = document.getElementById('chartPieExpenses30d');
                const empty = document.getElementById('pie-empty');
                if (!el || !Array.isArray(data.labels) || !data.labels.length) {
                    if (empty) empty.style.display = '';
                    return;
                }
                new Chart(el, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => {
                                        const val = ctx.raw ?? 0;
                                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0) || 1;
                                        const pct = (val / total) * 100;
                                        return ` ${ctx.label}: R$ ${Number(val).toLocaleString('pt-BR',{minimumFractionDigits:2})} (${pct.toFixed(1)}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '55%'
                    }
                });
            })
            ();

            // Alpine: lista de transações (cards no mobile, tabela no desktop)
            document.addEventListener('alpine:init', () => {
                Alpine.data('txList', () => ({
                    all: window.TX_INIT || [],
                    accounts: window.ACCOUNTS || [],
                    filters: {
                        type: '',
                        account_id: 0,
                        q: '',
                        groupInstallments: true,
                    },
                    rows: [],

                    init() {
                        this.apply();
                        this.$watch('filters', () => this.apply(), {
                            deep: true
                        });
                    },

                    apply() {
                        let list = [...this.all];

                        if (this.filters.type === 'income') list = list.filter(i => i.amount > 0);
                        else if (this.filters.type === 'expense') list = list.filter(i => i.amount < 0);
                        else if (this.filters.type === 'transfer')
                            list = list.filter(i => (i.category_full || '').toLowerCase().includes(
                                'transfer'));

                        if (Number(this.filters.account_id) > 0) {
                            list = list.filter(i => Number(i.account_id) === Number(this.filters
                                .account_id));
                        }

                        const q = (this.filters.q || '').toLowerCase();
                        if (q) {
                            list = list.filter(i =>
                                (i.note || '').toLowerCase().includes(q) ||
                                (i.category_full || '').toLowerCase().includes(q) ||
                                (i.account_name || '').toLowerCase().includes(q) ||
                                (i.card_name || '').toLowerCase().includes(q)
                            );
                        }

                        if (this.filters.groupInstallments) {
                            const grouped = new Map();
                            for (const t of list) {
                                const canGroup = t.installment_total && t.installment_total > 1 && t
                                    .installment_group_id;
                                const key = canGroup ? `G:${t.installment_group_id}` : `S:${t.id}`;

                                if (!grouped.has(key)) {
                                    grouped.set(key, {
                                        key,
                                        bank_icon: t.bank_icon,
                                        bank_name: t.bank_name,
                                        account_name: t.account_name,
                                        card_name: t.card_name,
                                        category_full: t.category_full,
                                        date_ddmm: t.date_ddmm,
                                        amount: 0,
                                        note_display: t.note_base || t.note || null,
                                        installment_total: canGroup ? t.installment_total : 0,
                                        per_installment: Math.abs(t.amount),
                                    });
                                }
                                const agg = grouped.get(key);
                                agg.amount += t.amount;
                            }

                            this.rows = Array.from(grouped.values()).map(r => {
                                if (r.installment_total > 1 && r.per_installment) {
                                    r.installment_info =
                                        `${r.installment_total}x de ${this.formatBRL(-Math.abs(r.per_installment))}`;
                                }
                                return r;
                            });
                        } else {
                            this.rows = list.map(t => ({
                                key: t.id,
                                bank_icon: t.bank_icon,
                                bank_name: t.bank_name,
                                account_name: t.account_name,
                                card_name: t.card_name,
                                note_display: t.note || null,
                                category_full: t.category_full,
                                amount: t.amount,
                                date_ddmm: t.date_ddmm,
                                installment_info: (t.installment_total > 1) ?
                                    `${t.installment_total}x` : null,
                            }));
                        }
                    },

                    formatBRL(v) {
                        const n = Number(v || 0);
                        const s = n.toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        const sign = n < 0 ? '-' : '';
                        return `${sign}R$ ${s.replace('-', '')}`;
                    }
                }));
            });
        </script>
    @endonce
@endsection
