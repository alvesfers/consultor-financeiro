@extends('layouts.app')

@section('content')
    {{-- ======= CARDS SUPERIORES ======= --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-4">

        {{-- Saldo atual --}}
        <div class="stat bg-base-200 shadow rounded-box">
            <div class="stat-figure text-primary">
                <i class="fa-solid fa-wallet text-3xl"></i>
            </div>
            <div class="stat-title">Saldo Atual</div>
            <div class="stat-value">
                {{ number_format($balance, 2, ',', '.') }}
            </div>
            <div class="stat-desc">Aberturas + movimentações de contas</div>
        </div>

        {{-- Tasks pendentes --}}
        <div class="stat bg-base-200 shadow rounded-box">
            <div class="stat-figure text-warning">
                <i class="fa-solid fa-list-check text-3xl"></i>
            </div>
            <div class="stat-title">Tasks pendentes</div>
            <div class="stat-value">{{ $tasksPendingCount }}</div>
            <div class="stat-desc">
                <a class="link" href="{{ $rConsultant('consultant.tasks.index') }}">ver tasks</a>
            </div>
        </div>

        {{-- Objetivos pendentes --}}
        <div class="stat bg-base-200 shadow rounded-box">
            <div class="stat-figure text-accent">
                <i class="fa-solid fa-bullseye text-3xl"></i>
            </div>
            <div class="stat-title">Objetivos pendentes</div>
            <div class="stat-value">{{ $goalsPendingCount }}</div>
            <div class="stat-desc">
                <a class="link" href="{{ $rConsultant('client.dashboard') }}">ver objetivos</a>
            </div>
        </div>

        {{-- Ações rápidas --}}
        <div class="stat bg-base-200 shadow rounded-box">
            <div class="stat-figure text-success">
                <i class="fa-solid fa-bolt text-3xl"></i>
            </div>
            <div class="stat-title">Ações</div>
            <div class="stat-value text-base">
                <div class="join join-vertical lg:join-horizontal gap-2">
                    <a class="btn btn-sm btn-primary"
                        href="{{ $rConsultant('client.dashboard'); /* troque para client.transactions.create */ }}">
                        <i class="fa-solid fa-plus mr-2"></i>
                        Nova transação
                    </a>
                    <a class="btn btn-sm" href="{{ $rConsultant('client.dashboard'); /* troque para client.report */ }}">
                        <i class="fa-solid fa-chart-pie mr-2"></i>
                        Relatório geral
                    </a>
                </div>
            </div>
            <div class="stat-desc">Adicionar gasto/ganho e acessar relatórios</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Minhas Contas --}}
        <div class="card bg-base-200">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fa-solid fa-building-columns mr-2"></i> Minhas contas
                </h2>
                <ul class="space-y-2">
                    @forelse($accounts as $a)
                        <li class="flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $a->name }}</div>
                                <div class="text-xs opacity-70">
                                    {{ $a->type }}
                                    @if (!$a->on_budget)
                                        • fora do orçamento
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="badge badge-outline">{{ $a->currency ?? 'BRL' }}</div>
                                <div class="text-xs opacity-70 mt-1">
                                    Abertura: {{ number_format($a->opening_balance, 2, ',', '.') }}
                                </div>
                            </div>
                        </li>
                    @empty
                        <li>Nenhuma conta cadastrada.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Gráfico: despesas por categoria (30d) --}}
        <div class="card bg-base-200 lg:col-span-2">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fa-solid fa-chart-pie mr-2"></i> Despesas por categoria (30 dias)
                </h2>
                <canvas id="chartByCategory" height="140"></canvas>
            </div>
        </div>

        {{-- Filtros + Tabela de transações --}}
        <div class="card bg-base-200 lg:col-span-2">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fa-solid fa-arrow-right-arrow-left mr-2"></i> Transações recentes
                </h2>

                {{-- Filtros simples (GET) --}}
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Tipo</span></label>
                        <select name="type" class="select select-bordered">
                            <option value="">Todos</option>
                            <option value="income" @selected(request('type') === 'income')>Ganhos</option>
                            <option value="expense" @selected(request('type') === 'expense')>Gastos</option>
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text">Conta</span></label>
                        <select name="account_id" class="select select-bordered">
                            <option value="">Todas</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected((string) request('account_id') === (string) $a->id)>{{ $a->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control md:col-span-2">
                        <label class="label"><span class="label-text">Busca (notas)</span></label>
                        <input type="text" name="q" value="{{ request('q') }}" class="input input-bordered"
                            placeholder="ex.: mercado, uber, salário...">
                    </div>

                    <div class="md:col-span-4 flex justify-end">
                        <button class="btn btn-primary">
                            <i class="fa-solid fa-filter mr-2"></i> Filtrar
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto">
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
                                    <td>{{ $tx->category_name ?? '—' }}</td>
                                    <td class="text-right {{ $tx->amount < 0 ? 'text-error' : 'text-success' }}">
                                        {{ number_format($tx->amount, 2, ',', '.') }}
                                    </td>
                                    <td>{{ $tx->notes }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">Sem transações.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        {{-- Gráfico: despesas por subcategoria (30d) --}}
        <div class="card bg-base-200">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fa-solid fa-chart-column mr-2"></i> Despesas por subcategoria (30 dias)
                </h2>
                <canvas id="chartBySubcategory" height="200"></canvas>
            </div>
        </div>

    </div>

    {{-- ======= SCRIPTS (Font Awesome + Chart.js + gráficos) ======= --}}
    @once
        {{-- Font Awesome Free --}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
            crossorigin="anonymous" referrerpolicy="no-referrer" />

        {{-- Chart.js --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

        <script>
            (function() {
                const catData = @json($chartCategories); // { labels: [], values: [] }
                const subData = @json($chartSubcategories); // { labels: [], values: [] }

                // Doughnut por categoria
                const ctxCat = document.getElementById('chartByCategory');
                if (ctxCat && catData && catData.labels?.length) {
                    new Chart(ctxCat, {
                        type: 'doughnut',
                        data: {
                            labels: catData.labels,
                            datasets: [{
                                data: catData.values
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => {
                                            const v = ctx.parsed;
                                            return `${ctx.label}: R$ ${Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
                                        }
                                    }
                                }
                            },
                        }
                    });
                }

                // Barras por subcategoria
                const ctxSub = document.getElementById('chartBySubcategory');
                if (ctxSub && subData && subData.labels?.length) {
                    new Chart(ctxSub, {
                        type: 'bar',
                        data: {
                            labels: subData.labels,
                            datasets: [{
                                label: 'Total (R$)',
                                data: subData.values
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: (value) => `R$ ${Number(value).toLocaleString('pt-BR')}`
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) =>
                                            `R$ ${Number(ctx.parsed.y).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`
                                    }
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
