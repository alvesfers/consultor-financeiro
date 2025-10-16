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
            ];
        });

        $accountsForJs = ($accounts ?? collect())->map(fn ($a) => ['id' => (int) $a->id, 'name' => $a->name])->values();

        // Donut (30d)
        $pieCategories = $pieCategories ?? ($chartCategories ?? ['labels' => [], 'values' => []]);
    @endphp

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-4">
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

    {{-- CARDS --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
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
                <div class="mt-4 h-14 w-full bg-base-200 rounded-box"></div>
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
                <a class="link mt-3" href="{{ route('consultant.tasks.index', ['consultant' => $consultantId]) }}">Ver todas</a>
                <div class="mt-4 h-14 w-full bg-base-200 rounded-box"></div>
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
                <a class="link mt-3" href="{{ route('client.dashboard', ['consultant' => $consultantId]) }}">Ir para objetivos</a>
                <div class="mt-4 h-14 w-full bg-base-200 rounded-box"></div>
            </div>
        </div>

        <div class="card shadow bg-gradient-to-br from-base-200 to-base-100">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-60">Patrimônio</div>
                        <div class="text-3xl font-semibold">{{ number_format((float) ($netWorth ?? 0), 2, ',', '.') }}</div>
                        <div class="mt-1 text-xs opacity-70">
                            Saldo: R$ {{ number_format((float) ($balance ?? 0), 2, ',', '.') }}
                            • Investido: R$ {{ number_format((float) ($investedTotal ?? 0), 2, ',', '.') }}
                        </div>
                    </div>
                    <i class="fa-solid fa-chart-line text-3xl text-success"></i>
                </div>
                <div class="mt-4 h-14 w-full bg-base-200 rounded-box"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- FATURAS (simplificado) --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fa-solid fa-credit-card mr-2"></i>
                    Faturas — {{ $invoicesMonthTitle }}
                </h2>

                @if (!empty($cardInvoices))
                    <div class="overflow-x-auto mt-2">
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
                                                            <i class="fa-solid fa-money-bill-wave mr-1"></i>
                                                            Pagar fatura
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

        {{-- METAS --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title"><i class="fa-solid fa-bullseye mr-2"></i> Metas do mês — {{ $goalsTitle }}</h2>

                @if (!empty($goalsComparative) && count($goalsComparative))
                    <div class="space-y-4 mt-2">
                        @foreach ($goalsComparative as $row)
                            <div class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <div class="font-medium">{{ $row['category_name'] }}</div>
                                    <div class="text-xs opacity-70">Meta: R$ {{ number_format((float) $row['limit'], 2, ',', '.') }}</div>
                                </div>

                                <div class="w-full bg-base-200 rounded-full h-2.5">
                                    <div class="h-2.5 rounded-full {{ $row['exceeded'] ? 'bg-red-500' : 'bg-green-500' }}"
                                         style="width: {{ number_format(min(100, ($row['ratio'] ?? 0) * 100), 2) }}%"></div>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <div>Gasto: <span class="font-medium">R$ {{ number_format((float) $row['spent'], 2, ',', '.') }}</span></div>
                                    <div class="{{ $row['exceeded'] ? 'text-red-600 font-semibold' : 'text-green-700 font-medium' }}">
                                        {{ $row['exceeded'] ? 'Ultrapassou' : 'Saldo:' }}
                                        @unless ($row['exceeded'])
                                            R$ {{ number_format((float) $row['remaining'], 2, ',', '.') }}
                                        @endunless
                                    </div>
                                </div>
                            </div>
                            @if (!$loop->last) <div class="divider my-2"></div> @endif
                        @endforeach
                    </div>
                @else
                    <div class="mt-2 text-sm opacity-70">Nenhuma meta definida para este mês.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- LISTAS & GRÁFICOS --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Donut 30d --}}
        <div class="card bg-base-100 shadow lg:col-span-1">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fa-solid fa-chart-pie mr-2"></i>
                    Despesas por categoria <span class="badge badge-ghost text-xs ml-2">30d</span>
                </h2>
                <div class="mt-3"><canvas id="chartPieExpenses30d" height="240"></canvas></div>
                <div class="text-sm opacity-70" id="pie-empty" style="display:none;">Sem dados nos últimos 30 dias.</div>
            </div>
        </div>

        {{-- Transações (Alpine) --}}
        <div class="card bg-base-100 shadow lg:col-span-2" x-data="txList()" x-init="init()">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title"><i class="fa-solid fa-arrow-right-arrow-left mr-2"></i> Transações recentes</h2>

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

                        <input type="text" class="input input-bordered input-sm" placeholder="buscar..."
                               x-model.trim="filters.q" @keydown.enter.prevent />

                        <label class="label cursor-pointer gap-2">
                            <span class="label-text text-sm">Agrupar parcelas</span>
                            <input type="checkbox" class="toggle toggle-sm" x-model="filters.groupInstallments">
                        </label>
                    </div>
                </div>

                <div class="overflow-x-auto mt-3">
                    <table class="table table-zebra">
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
                            <tr><td colspan="7" class="opacity-70">Sem transações.</td></tr>
                        </template>

                        <template x-for="r in rows" :key="r.key">
                            <tr>
                                <td><i :class="r.bank_icon"></i> <span class="ml-2" x-text="r.bank_name || '—'"></span></td>
                                <td x-text="r.account_name || '—'"></td>
                                <td x-text="r.card_name || '—'"></td>
                                <td x-text="r.note_display || '—'"></td>
                                <td x-text="r.category_full || '—'"></td>
                                <td class="text-right" :class="r.amount < 0 ? 'text-error' : 'text-success'">
                                    <span x-text="formatBRL(r.amount)"></span>
                                    <template x-if="r.installment_info">
                                        <span class="opacity-70 text-xs ml-1" x-text="`(${r.installment_info})`"></span>
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

    {{-- MODAIS --}}
    @include('consultants.clients.partials.modal-transaction')
    @include('consultants.clients.partials.modal-investment')

    {{-- SCRIPTS --}}
    @once
        {{-- Chart.js --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

        {{-- Passo 1: expor os datasets em variáveis globais (evita JSON no x-data) --}}
        <script>
            window.TX_INIT   = @js($txForJs);
            window.ACCOUNTS  = @js($accountsForJs);
            window.PIE_DATA  = @js($pieCategories ?? ['labels' => [], 'values' => []]);
        </script>

        {{-- Passo 2: donut --}}
        <script>
            (function () {
                const data = window.PIE_DATA || {labels: [], values: []};
                const el = document.getElementById('chartPieExpenses30d');
                const empty = document.getElementById('pie-empty');
                if (!el || !Array.isArray(data.labels) || !data.labels.length) {
                    if (empty) empty.style.display = '';
                    return;
                }
                new Chart(el, {
                    type: 'doughnut',
                    data: { labels: data.labels, datasets: [{ data: data.values }] },
                    options: {
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: { callbacks: {
                                label: (ctx) => {
                                    const val = ctx.raw ?? 0;
                                    const total = ctx.dataset.data.reduce((a,b)=>a+b,0) || 1;
                                    const pct = (val/total)*100;
                                    return ` ${ctx.label}: R$ ${Number(val).toLocaleString('pt-BR',{minimumFractionDigits:2})} (${pct.toFixed(1)}%)`;
                                }
                            }}
                        },
                        cutout: '55%'
                    }
                });
            })();
        </script>

        {{-- Passo 3: Alpine component sem JSON no atributo --}}
        <script>
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
                        this.$watch('filters', () => this.apply(), { deep: true });
                    },

                    apply() {
                        let list = [...this.all];

                        if (this.filters.type === 'income') list = list.filter(i => i.amount > 0);
                        else if (this.filters.type === 'expense') list = list.filter(i => i.amount < 0);
                        else if (this.filters.type === 'transfer')
                            list = list.filter(i => (i.category_full || '').toLowerCase().includes('transfer'));

                        if (Number(this.filters.account_id) > 0) {
                            list = list.filter(i => Number(i.account_id) === Number(this.filters.account_id));
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
                                const canGroup = t.installment_total && t.installment_total > 1 && t.installment_group_id;
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
                                    r.installment_info = `${r.installment_total}x de ${this.formatBRL(-Math.abs(r.per_installment))}`;
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
                                installment_info: (t.installment_total > 1) ? `${t.installment_total}x` : null,
                            }));
                        }
                    },

                    formatBRL(v) {
                        const n = Number(v || 0);
                        const s = n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        const sign = n < 0 ? '-' : '';
                        return `${sign}R$ ${s.replace('-', '')}`;
                    }
                }));
            });
        </script>
    @endonce
@endsection
