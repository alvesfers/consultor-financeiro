@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user();
        $clientId = $user?->client?->id ?? null;
        $consultantId = $user?->consultant?->id ?? ($user?->client?->consultant_id ?? null);

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

        // ===== Totais faturas =====
        $totalInvoices = 0.0;
        $sumAvailable = 0.0;
        foreach ($cardInvoices ?? [] as $ci) {
            $totalInvoices += (float) ($ci['total'] ?? 0);
            $sumAvailable += (float) ($ci['available'] ?? 0);
        }

        // helper logo de cartão
        $cardLogo = function ($ci) {
            $card = $ci['card'] ?? null;
            if (!$card) {
                return null;
            }
            if (!empty($card->logo_url)) {
                return asset($card->logo_url);
            }
            if (!empty($card->brand)) {
                $brand = \Illuminate\Support\Str::of($card->brand)->lower()->slug('-');
                return asset("/storage/banks/{$brand}.svg");
            }
            return null;
        };

        $newTxnUrl = $clientId ? url("/{$clientId}/transactions/new") : '#';

        // ===== FALLBACKS para nomes/variáveis vindas do controller =====
        $userName = $user->name ?? 'Usuário';
        $balance = isset($balance) ? (float) $balance : 0.0;
        $netWorth = isset($netWorth) ? (float) $netWorth : $balance ?? 0.0;
        $invoiceMonthTitle = $invoiceMonthTitle ?? ($invoicesMonthTitle ?? now()->format('m/Y'));
        $invoiceTotal = isset($invoiceTotal)
            ? (float) $invoiceTotal
            : (isset($totalInvoices)
                ? (float) $totalInvoices
                : 0.0);
        $pendingFeesCount = isset($pendingFeesCount)
            ? (int) $pendingFeesCount
            : (isset($tasksPendingCount)
                ? (int) $tasksPendingCount
                : 0);

        // Gastos por categoria (espera: [['label'=>..., 'month'=>..., 'year'=>...], ...])
        $spendByCategory = $spendByCategory ?? [];
        // Últimas transações (limita a 10)
        $recent = array_slice($recent ?? [], 0, 10);

        // Donut (0–100) — se já vier pronto do controller, usa; senão 0
        $donutPercent = isset($donutPercent) ? max(0, min(100, (int) $donutPercent)) : 0;
        $circumference = 2 * M_PI * 60; // r=60
        $dash = ($donutPercent / 100) * $circumference;

        $fmt = fn($v) => number_format((float) $v, 2, ',', '.');

    @endphp

    {{-- =========================
WRAPPER DE ESTADO (olho/patrimônio/pie)
========================= --}}
    <div x-data="dashboardUI({
        balance: {{ (float) ($balance ?? 0) }},
        netWorth: {{ (float) ($netWorth ?? 0) }},
        invoicesTotal: {{ (float) ($totalInvoices ?? 0) }},
        pie: @js($pieCategories ?? ['labels' => [], 'values' => []]),
    })" x-init="init()">

        {{-- =========================
    HERO / HEADER MOBILE
    ========================= --}}
        <div class="lg:hidden">
            <div
                class="rounded-2xl p-4 bg-gradient-to-br from-base-200/60 to-base-100/60 border border-base-300/70 backdrop-blur">
                <div class="flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="text-xs opacity-70">Olá,</p>
                        <h1 class="text-xl font-semibold truncate">{{ $user->name ?? 'Bem-vindo' }}</h1>
                    </div>

                    <div class="flex items-center gap-2">
                        <button class="btn btn-ghost btn-sm" @click="toggleMask()"
                            :aria-label="masked ? 'Mostrar valores' : 'Ocultar valores'">
                            <i :class="masked ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                        </button>
                        <button class="btn btn-ghost btn-sm" @click="toggleMode()"
                            :aria-label="mode === 'balance' ? 'Ver patrimônio' : 'Ver saldo'">
                            <i :class="mode === 'balance' ? 'fa-solid fa-piggy-bank' : 'fa-solid fa-wallet'"></i>
                        </button>
                        <a class="btn btn-primary btn-sm" href="{{ $newTxnUrl }}">
                            <i class="fa-solid fa-plus mr-1"></i> Nova
                        </a>
                    </div>
                </div>

                {{-- Cartão principal --}}
                <div class="mt-4">
                    <div class="card rounded-2xl shadow-sm border border-base-300 overflow-hidden">
                        <div class="card-body p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs opacity-70"
                                        x-text="mode==='balance' ? 'Saldo atual' : 'Patrimônio'"></div>
                                    <div class="text-3xl font-semibold"
                                        x-text="fmtCurrencyMasked(mode==='balance' ? balance : netWorth)"></div>
                                </div>
                                <div class="badge badge-primary badge-lg gap-2"
                                    :class="mode === 'networth' && 'badge-accent'">
                                    <i :class="mode === 'balance' ? 'fa-solid fa-wallet' : 'fa-solid fa-piggy-bank'"></i>
                                    {{ count($accounts ?? []) }} contas
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-4 gap-2">
                                <a class="btn btn-accent btn-sm"><i
                                        class="fa-solid fa-arrow-down-wide-short mr-1"></i>Receber</a>
                                <a class="btn btn-secondary btn-sm"><i
                                        class="fa-solid fa-arrow-up-right-dots mr-1"></i>Pagar</a>
                                <a class="btn btn-primary btn-sm"><i class="fa-solid fa-right-left mr-1"></i>Transferir</a>
                                <a class="btn btn-warning btn-sm" href="javascript:void(0)"
                                    onclick="document.getElementById('investmentModal').showModal()">
                                    <i class="fa-solid fa-arrow-trend-up mr-1"></i>Investir
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Chips --}}
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div class="rounded-xl p-3 bg-base-100 border border-base-300">
                        <div class="text-xs opacity-70">Tasks pendentes</div>
                        <div class="text-2xl font-semibold">{{ (int) ($tasksPendingCount ?? 0) }}</div>
                    </div>
                    <div class="rounded-xl p-3 bg-base-100 border border-base-300">
                        <div class="text-xs opacity-70">Fatura do mês</div>
                        <div class="text-2xl font-semibold" x-text="fmtCurrencyMasked(invoicesTotal)"></div>
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
                <a class="btn btn-primary" href="{{ $newTxnUrl }}">
                    <i class="fa-solid fa-plus mr-2"></i> Nova transação
                </a>
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
                            <div class="text-3xl font-semibold" x-text="fmtCurrencyMasked(balance)"></div>
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
                            <div class="text-3xl font-semibold" x-text="fmtCurrencyMasked(netWorth)"></div>
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
                        <h2 class="card-title"><i class="fa-solid fa-credit-card mr-2"></i> Faturas —
                            {{ $invoicesMonthTitle }}</h2>
                        <span class="hidden sm:inline-flex badge badge-ghost">Fechamento/Vencimento</span>
                    </div>

                    {{-- Resumo do mês --}}
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div class="rounded-xl p-4 border border-base-300 bg-gradient-to-br from-base-200 to-base-100">
                            <div class="text-xs opacity-70">Total das faturas</div>
                            <div class="text-2xl font-semibold" :class="invoicesTotal > 0 ? 'text-error' : ''"
                                x-text="fmtCurrencyMasked(invoicesTotal)"></div>
                        </div>
                        <div class="rounded-xl p-4 border border-base-300 bg-base-100">
                            <div class="text-xs opacity-70">Crédito disponível (soma)</div>
                            <div class="text-2xl font-semibold">{{ number_format($sumAvailable, 2, ',', '.') }}</div>
                        </div>
                    </div>

                    @if (!empty($cardInvoices))
                        {{-- Cards por cartão --}}
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach ($cardInvoices as $ci)
                                @php
                                    $logo = $cardLogo($ci);
                                    $total = (float) ($ci['total'] ?? 0);
                                    $available = $ci['available'];
                                    $paid = !empty($ci['all_paid']);
                                @endphp
                                <div class="rounded-2xl border border-base-300 p-4 bg-base-100">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            @if ($logo)
                                                <img src="{{ $logo }}" alt="logo"
                                                    class="h-8 w-8 object-contain">
                                            @else
                                                <div class="avatar placeholder">
                                                    <div
                                                        class="bg-primary/20 text-primary w-9 h-9 rounded-xl flex items-center justify-center">
                                                        <span
                                                            class="text-xs">{{ \Illuminate\Support\Str::substr($ci['card']->name ?? 'C', 0, 2) }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <div class="font-medium truncate">{{ $ci['card']->name }}</div>
                                                <div class="text-xs opacity-70">
                                                    {{ $ci['close_date']->format('d') }} →
                                                    {{ $ci['due_date']->format('d') }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-right shrink-0">
                                            <div class="text-xs opacity-70">Fatura</div>
                                            <div class="text-lg font-semibold {{ $total > 0 ? 'text-error' : '' }}">
                                                {{ number_format($total, 2, ',', '.') }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between">
                                        <div class="text-xs opacity-70">
                                            Disponível:
                                            <b>{{ !is_null($available) ? number_format((float) $available, 2, ',', '.') : '—' }}</b>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @if ($total > 0)
                                                @if ($paid)
                                                    <span class="badge badge-success">Paga</span>
                                                @else
                                                    <form method="POST"
                                                        action="{{ route('client.cards.invoices.pay', ['consultant' => $consultantId, 'card' => $ci['card']->id, 'invoiceMonth' => $ci['invoice_month']]) }}"
                                                        onsubmit="return confirm('Confirmar pagamento da fatura {{ $ci['invoice_month'] }} do cartão {{ $ci['card']->name }}?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary btn-sm">
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
                    @else
                        <div class="mt-2 text-sm opacity-70">Nenhuma fatura para exibir.</div>
                    @endif
                </div>
            </div>

            {{-- METAS --}}
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h2 class="card-title"><i class="fa-solid fa-bullseye mr-2"></i> Metas do mês — {{ $goalsTitle }}
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
                                                Meta: {{ $money($row['limit']) }} • Gasto:
                                                <b>{{ $money($row['spent']) }}</b>
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
    DONUT + TRANSAÇÕES
    ========================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Donut 30d --}}
            <div class="card bg-base-100 shadow lg:col-span-1">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <h2 class="card-title"><i class="fa-solid fa-chart-pie mr-2"></i> Despesas por categoria</h2>
                        <div class="badge badge-ghost text-xs">30d</div>
                    </div>
                    <div class="mt-3 cursor-pointer" id="chartPieExpenses30d" @click="openPieModal()"></div>
                    <div class="text-sm opacity-70" id="pie-empty" style="display:none;">Sem dados nos últimos 30 dias.
                    </div>
                </div>
            </div>

            {{-- Transações --}}
            <div class="card bg-base-100 shadow lg:col-span-2" x-data="txList()" x-init="init()">
                <div class="card-body">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="card-title"><i class="fa-solid fa-arrow-right-arrow-left mr-2"></i> Transações recentes
                        </h2>

                        {{-- filtros compactos no mobile --}}
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
                                        <select class="select select-bordered select-sm"
                                            x-model.number="filters.account_id">
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
                                        <input type="checkbox" class="toggle toggle-sm"
                                            x-model="filters.groupInstallments">
                                    </label>
                                </div>
                            </details>
                        </div>

                        {{-- filtros md+ --}}
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

                    {{-- MOBILE: cards --}}
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

                    {{-- DESKTOP: tabela --}}
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

        {{-- ===== MODAL DO DONUT (ampliado) ===== --}}
        <dialog id="pieModal" class="modal">
            <div class="modal-box max-w-3xl">
                <h3 class="font-bold text-lg mb-2"><i class="fa-solid fa-chart-pie mr-2"></i>Despesas por categoria (30d)
                </h3>
                <div id="chartPieLarge"></div>

                <div class="mt-4 space-y-1 max-h-56 overflow-y-auto">
                    <template x-for="row in pieBreakdown" :key="row.label">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-sm" :style="`background:${row.color}`"></span>
                                <span x-text="row.label"></span>
                            </div>
                            <div class="font-medium" x-text="`${row.pct.toFixed(1)}% — ${fmtCurrency(row.value)}`"></div>
                        </div>
                    </template>
                </div>

                <div class="modal-action">
                    <form method="dialog"><button class="btn">Fechar</button></form>
                </div>
            </div>
        </dialog>

        {{-- MODAL: Investimentos (mantido) --}}
        @include('consultants.clients.partials.modal-investment')

    </div> {{-- /wrapper x-data --}}

    {{-- ===== SCRIPTS ===== --}}
    @once
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            // ===== Helpers de tema
            function cssVar(v) {
                return getComputedStyle(document.documentElement).getPropertyValue(v).trim();
            }
            const C_BASE100 = `hsl(${cssVar('--b1')})`;
            const C_BASE300 = `hsl(${cssVar('--b3')})`;

            function buildPalette(n) {
                const base = [
                    `hsl(${cssVar('--p')})`, `hsl(${cssVar('--s')})`, `hsl(${cssVar('--a')})`,
                    `hsl(${cssVar('--wa')})`, `#00bcd4`, `#ff9800`, `#8bc34a`, `#e91e63`, `#3f51b5`, `#795548`
                ];
                // garante pelo menos n cores
                while (base.length < n) base.push('#' + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0'));
                return base.slice(0, Math.max(n, 0));
            }

            // ===== Controller UI (Olho, Porquinho, Pie Modal)
            function dashboardUI({
                balance,
                netWorth,
                invoicesTotal,
                pie
            }) {
                return {
                    balance,
                    netWorth,
                    invoicesTotal,
                    masked: false,
                    mode: 'balance',
                    pieData: pie || {
                        labels: [],
                        values: []
                    },
                    pieColors: [],
                    pieBreakdown: [],

                    init() {
                        this.pieColors = buildPalette((this.pieData.labels || []).length);
                        this.calcPieBreakdown();
                        this.mountPie('#chartPieExpenses30d', 260);
                    },

                    toggleMask() {
                        this.masked = !this.masked;
                    },
                    toggleMode() {
                        this.mode = (this.mode === 'balance' ? 'networth' : 'balance');
                    },

                    fmtCurrency(v) {
                        return 'R$ ' + Number(v || 0).toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    },
                    fmtCurrencyMasked(v) {
                        return this.masked ? 'R$ ••••' : this.fmtCurrency(v);
                    },

                    calcPieBreakdown() {
                        const labels = this.pieData.labels || [];
                        const values = this.pieData.values || [];
                        const total = values.reduce((a, b) => a + (+b || 0), 0) || 1;
                        this.pieBreakdown = labels.map((l, i) => ({
                            label: l,
                            value: +(values[i] || 0),
                            pct: ((+(values[i] || 0)) / total) * 100,
                            color: this.pieColors[i % this.pieColors.length] || '#999'
                        }));
                    },

                    mountPie(selector, height) {
                        const el = document.querySelector(selector);
                        const empty = document.getElementById('pie-empty');
                        if (!el || !Array.isArray(this.pieData.labels) || !this.pieData.labels.length) {
                            if (empty) empty.style.display = '';
                            return;
                        }
                        const chart = new ApexCharts(el, {
                            chart: {
                                type: 'donut',
                                height,
                                foreColor: C_BASE300
                            },
                            labels: this.pieData.labels,
                            series: this.pieData.values,
                            colors: this.pieColors,
                            dataLabels: {
                                enabled: true,
                                style: {
                                    colors: [C_BASE100]
                                }
                            },
                            legend: {
                                position: 'bottom',
                                labels: {
                                    colors: C_BASE300
                                }
                            },
                            tooltip: {
                                y: {
                                    formatter: (val, opt) => {
                                        const total = opt.globals.seriesTotals.reduce((a, b) => a + b, 0) || 1;
                                        const pct = (val / total) * 100;
                                        return `R$ ${Number(val).toLocaleString('pt-BR',{minimumFractionDigits:2})} (${pct.toFixed(1)}%)`;
                                    }
                                }
                            },
                            stroke: {
                                width: 0
                            },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '55%'
                                    }
                                }
                            },
                        });
                        chart.render();
                    },

                    openPieModal() {
                        const dlg = document.getElementById('pieModal');
                        if (!dlg) return;
                        dlg.showModal();
                        document.getElementById('chartPieLarge').innerHTML = '';
                        const big = new ApexCharts(document.querySelector('#chartPieLarge'), {
                            chart: {
                                type: 'donut',
                                height: 360,
                                foreColor: C_BASE300
                            },
                            labels: this.pieData.labels,
                            series: this.pieData.values,
                            colors: this.pieColors,
                            legend: {
                                position: 'right'
                            },
                            dataLabels: {
                                enabled: true,
                                style: {
                                    colors: [C_BASE100]
                                }
                            },
                            stroke: {
                                width: 0
                            },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '60%'
                                    }
                                }
                            },
                        });
                        big.render();
                    },
                }
            }

            // ===== Dados globais já existentes
            window.TX_INIT = @js($txForJs);
            window.ACCOUNTS = @js($accountsForJs);
            window.PIE_DATA = @js($pieCategories ?? ['labels' => [], 'values' => []]);

            // ===== Transações (com limite de 10)
            document.addEventListener('alpine:init', () => {
                Alpine.data('txList', () => ({
                    all: window.TX_INIT || [],
                    accounts: window.ACCOUNTS || [],
                    filters: {
                        type: '',
                        account_id: 0,
                        q: '',
                        groupInstallments: true
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
                        else if (this.filters.type === 'transfer') list = list.filter(i => (i
                            .category_full || '').toLowerCase().includes('transfer'));

                        if (+this.filters.account_id > 0) list = list.filter(i => +i.account_id === +this
                            .filters.account_id);

                        const q = (this.filters.q || '').toLowerCase();
                        if (q) list = list.filter(i =>
                            (i.note || '')
                            .toLowerCase().includes(q) ||
                            (i.category_full || '').toLowerCase().includes(q) ||
                            (i.account_name || '').toLowerCase().includes(q) ||
                            (i.card_name || '').toLowerCase().includes(q)
                        );

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
                                grouped.get(key).amount += t.amount;
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

                        // Limita a 10
                        this.rows = this.rows.slice(0, 10);
                    },
                    formatBRL(v) {
                        const n = Number(v || 0);
                        const s = n.toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        const sign = n < 0 ? '-' : '';
                        return `${sign}R$ ${s.replace('-','')}`;
                    }
                }));
            });
        </script>
    @endonce
@endsection
