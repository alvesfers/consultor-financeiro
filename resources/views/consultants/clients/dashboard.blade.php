@extends('layouts.app')
@section('content')
    @php
        // Mês para o título das faturas (todas do mesmo ciclo, pelo 1º item)
        $invoicesMonthTitle = !empty($cardInvoices)
            ? \Illuminate\Support\Str::of($cardInvoices[0]['invoice_month'])->replace('-', '/')
            : now()->format('Y/m');

        // Título das metas (enviado pelo controller)
        $goalsTitle = $goalsMonthTitle ?? now()->format('m/Y');
    @endphp
    {{-- ALERTAS --}}
    @if (session('success'))
        <div class="alert alert-success mb-4">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error mb-4">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <div class="font-semibold">Não foi possível salvar a transação.</div>
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

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

    {{-- ======= CARDS ======= --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
        {{-- 1) Saldo atual --}}
        <div class="card shadow bg-base-100">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-60">Saldo atual</div>
                        <div class="text-3xl font-semibold">{{ number_format($balance ?? 0, 2, ',', '.') }}</div>
                    </div>
                    <i class="fa-solid fa-wallet text-3xl text-primary"></i>
                </div>
                <div class="mt-2 text-xs opacity-60">Somatório das contas consideradas no orçamento.</div>
                <div class="mt-4 h-14 w-full bg-base-200 rounded-box"></div>
            </div>
        </div>

        {{-- 2) Tasks pendentes --}}
        <div class="card shadow bg-base-100">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-60">Tasks pendentes</div>
                        <div class="text-3xl font-semibold">{{ $tasksPendingCount ?? 0 }}</div>
                    </div>
                    <i class="fa-solid fa-list-check text-3xl text-warning"></i>
                </div>
                <a class="link mt-3" href="{{ route('consultant.tasks.index', ['consultant' => $consultantId]) }}">Ver
                    todas</a>
                <div class="mt-4 h-14 w-full bg-base-200 rounded-box"></div>
            </div>
        </div>

        {{-- 3) Objetivos pendentes --}}
        <div class="card shadow bg-base-100">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-60">Objetivos pendentes</div>
                        <div class="text-3xl font-semibold">{{ $goalsPendingCount ?? 0 }}</div>
                    </div>
                    <i class="fa-solid fa-bullseye text-3xl text-accent"></i>
                </div>
                <a class="link mt-3" href="{{ route('client.dashboard', ['consultant' => $consultantId]) }}">Ir para
                    objetivos</a>
                <div class="mt-4 h-14 w-full bg-base-200 rounded-box"></div>
            </div>
        </div>

        {{-- 4) Patrimônio --}}
        <div class="card shadow bg-gradient-to-br from-base-200 to-base-100">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-60">Patrimônio</div>
                        <div class="text-3xl font-semibold">
                            {{ number_format($netWorth ?? 0, 2, ',', '.') }}
                        </div>
                        <div class="mt-1 text-xs opacity-70">
                            Saldo: R$ {{ number_format($balance ?? 0, 2, ',', '.') }}
                            • Investido: R$ {{ number_format($investedTotal ?? 0, 2, ',', '.') }}
                        </div>
                    </div>
                    <i class="fa-solid fa-chart-line text-3xl text-success"></i>
                </div>
                <div class="mt-4 h-14 w-full bg-base-200 rounded-box"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- ======= FATURAS DOS CARTÕES ======= --}}
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
                                    <th class="text-right">Valor da fatura (R$)</th>
                                    <th class="text-right">Limite</th>
                                    <th class="text-right">Disponível</th>
                                    <th>Fecha</th>
                                    <th>Vence</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cardInvoices as $ci)
                                    <tr>
                                        <td>
                                            {{ $ci['card']->name }}
                                            @if ($ci['card']->brand)
                                                ({{ $ci['card']->brand }})
                                            @endif
                                        </td>

                                        <td class="text-right {{ $ci['total'] > 0 ? 'text-error' : '' }} font-medium">
                                            {{ number_format($ci['total'], 2, ',', '.') }}
                                        </td>

                                        <td class="text-right">
                                            {{ ($ci['limit'] ?? 0) > 0 ? number_format($ci['limit'], 2, ',', '.') : '—' }}
                                        </td>

                                        <td class="text-right">
                                            {{ !is_null($ci['available']) ? number_format($ci['available'], 2, ',', '.') : '—' }}
                                        </td>

                                        <td>{{ $ci['close_date']->format('d/m/Y') }}</td>
                                        <td>{{ $ci['due_date']->format('d/m/Y') }}</td>

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

        {{-- ======= METAS MENSAIS POR CATEGORIA ======= --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fa-solid fa-bullseye mr-2"></i>
                    Metas do mês — {{ $goalsTitle }}
                </h2>

                @if (!empty($goalsComparative) && count($goalsComparative))
                    <div class="space-y-4 mt-2">
                        @foreach ($goalsComparative as $row)
                            <div class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <div class="font-medium">
                                        {{ $row['category_name'] }}
                                    </div>
                                    <div class="text-xs opacity-70">
                                        Meta: R$ {{ number_format($row['limit'], 2, ',', '.') }}
                                    </div>
                                </div>

                                {{-- Barra de progresso (spent / limit) --}}
                                <div class="w-full bg-base-200 rounded-full h-2.5">
                                    <div class="h-2.5 rounded-full {{ $row['exceeded'] ? 'bg-red-500' : 'bg-green-500' }}"
                                        style="width: {{ number_format(min(100, $row['ratio'] * 100), 2) }}%">
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <div>
                                        Gasto:
                                        <span class="font-medium">
                                            R$ {{ number_format($row['spent'], 2, ',', '.') }}
                                        </span>
                                    </div>

                                    <div
                                        class="{{ $row['exceeded'] ? 'text-red-600 font-semibold' : 'text-green-700 font-medium' }}">
                                        {{ $row['exceeded'] ? 'Ultrapassou' : 'Saldo:' }}
                                        @unless ($row['exceeded'])
                                            R$ {{ number_format($row['remaining'], 2, ',', '.') }}
                                        @endunless
                                    </div>
                                </div>
                            </div>

                            @if (!$loop->last)
                                <div class="divider my-2"></div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="mt-2 text-sm opacity-70">Nenhuma meta definida para este mês.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ======= LISTAS E GRÁFICOS ======= --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Minhas Contas --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title"><i class="fa-solid fa-building-columns mr-2"></i> Minhas contas</h2>
                <ul class="mt-2 space-y-3">
                    @forelse($accounts as $a)
                        <li class="flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $a->name }}</div>
                                <div class="text-xs opacity-70">
                                    {{ $a->type }} @unless ($a->on_budget)
                                        • fora do orçamento
                                    @endunless
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs opacity-70 mt-1">Abertura:
                                    {{ number_format($a->opening_balance, 2, ',', '.') }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="opacity-70">Nenhuma conta cadastrada.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Transações recentes (sem grupo) --}}
        <div class="card bg-base-100 shadow lg:col-span-2">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title"><i class="fa-solid fa-arrow-right-arrow-left mr-2"></i> Transações recentes
                    </h2>
                    <form method="GET" class="hidden md:flex gap-2">
                        <select name="type" class="select select-bordered select-sm">
                            <option value="">Todos</option>
                            <option value="income" @selected(request('type') === 'income')>Ganhos</option>
                            <option value="expense" @selected(request('type') === 'expense')>Gastos</option>
                            <option value="transfer"@selected(request('type') === 'transfer')>Transferências</option>
                        </select>
                        <select name="account_id" class="select select-bordered select-sm">
                            <option value="">Todas as contas</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected((string) request('account_id') === (string) $a->id)>{{ $a->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="text" name="q" value="{{ request('q') }}"
                            class="input input-bordered input-sm" placeholder="buscar em notas...">
                        <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter mr-2"></i> Filtrar</button>
                    </form>
                </div>

                <div class="overflow-x-auto mt-3">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Conta</th>
                                <th>Categoria</th>
                                <th class="text-right">Valor</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $tx)
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($tx->date)->format('d/m/Y') }}</td>
                                    <td>{{ $tx->account_name ?? '—' }}</td>
                                    <td>
                                        {{ $tx->category_name ?? '—' }}
                                        @if (!empty($tx->subcategory_name))
                                            <span class="opacity-60">/ {{ $tx->subcategory_name }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right {{ $tx->amount < 0 ? 'text-error' : 'text-success' }}">
                                        {{ number_format($tx->amount, 2, ',', '.') }}
                                    </td>
                                    <td>{{ $tx->notes }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="opacity-70">Sem transações.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Gráfico por CATEGORIA (30d) --}}
        <div class="card bg-base-100 shadow lg:col-span-3">
            <div class="card-body">
                <h2 class="card-title"><i class="fa-solid fa-chart-column mr-2"></i> Despesas por categoria (30 dias)</h2>
                <canvas id="chartByCategory" height="180"></canvas>
            </div>
        </div>
    </div>
    {{-- ===== MODAIS ===== --}}
    @include('consultants.clients.partials.modal-transaction')
    @include('consultants.clients.partials.modal-investment')

    {{-- ===== SCRIPTS (Chart.js) ===== --}}
    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
            (() => {
                const cat = @json($chartCategories ?? ['labels' => [], 'values' => []]);
                const c = document.getElementById('chartByCategory');
                if (c && cat.labels?.length) {
                    new Chart(c, {
                        type: 'bar',
                        data: {
                            labels: cat.labels,
                            datasets: [{
                                label: 'Total (R$)',
                                data: cat.values
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: v => `R$ ${Number(v).toLocaleString('pt-BR')}`
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                }
            })
            ();
        </script>
    @endonce

@endsection
