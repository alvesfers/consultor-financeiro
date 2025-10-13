@extends('layouts.app')

@section('content')
    {{-- ALERTAS --}}
    @if (session('status'))
        <div class="alert alert-success mb-4">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-semibold">
                Cliente: {{ $client->user?->name ?? '—' }}
            </h1>
            <p class="opacity-70 text-sm">Resumo financeiro e atividades recentes</p>
        </div>

        <div class="flex items-center gap-2">
            @can('update', $client)
                <a class="btn btn-primary" href="{{ route('consultant.tasks.create', ['consultant' => $consultantId]) }}">
                    <i class="fa-solid fa-list-check mr-2"></i> Criar tarefa
                </a>

                <a class="btn"
                    href="{{ route('consultant.clients.edit', ['consultant' => $consultantId, 'client' => $client->id]) }}">
                    <i class="fa-solid fa-user-pen mr-2"></i> Editar cliente
                </a>
            @endcan
        </div>
    </div>

    {{-- ======= CARDS SUPERIORES ======= --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
        {{-- Saldo atual --}}
        <div class="card shadow bg-base-100">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-60">Saldo atual</div>
                        <div class="text-3xl font-semibold">{{ number_format($balance ?? 0, 2, ',', '.') }}</div>
                    </div>
                    <i class="fa-solid fa-wallet text-3xl text-primary"></i>
                </div>
                <div class="mt-2 text-xs opacity-60">Aberturas + movimentações em conta</div>
                <div class="mt-4 h-14 w-full bg-base-200 rounded-box"></div>
            </div>
        </div>

        {{-- Tasks pendentes --}}
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

        {{-- Objetivos pendentes --}}
        <div class="card shadow bg-base-100">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-60">Objetivos pendentes</div>
                        <div class="text-3xl font-semibold">{{ $goalsPendingCount ?? 0 }}</div>
                    </div>
                    <i class="fa-solid fa-bullseye text-3xl text-accent"></i>
                </div>
                <div class="mt-4 h-14 w-full bg-base-200 rounded-box"></div>
            </div>
        </div>

        {{-- Ações rápidas (consultor) --}}
        <div class="card shadow bg-gradient-to-br from-base-200 to-base-100">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-60">Ações rápidas</div>
                        <div class="mt-2 flex gap-2">
                            @can('update', $client)
                                <a class="btn btn-sm btn-primary"
                                    href="{{ route('consultant.tasks.create', ['consultant' => $consultantId]) }}">
                                    <i class="fa-solid fa-plus mr-2"></i> Nova tarefa
                                </a>
                                <a class="btn btn-sm"
                                    href="{{ route('consultant.clients.edit', ['consultant' => $consultantId, 'client' => $client->id]) }}">
                                    <i class="fa-solid fa-user-pen mr-2"></i> Editar cliente
                                </a>
                            @endcan
                        </div>
                    </div>
                    <i class="fa-solid fa-bolt text-3xl text-success"></i>
                </div>
                <div class="mt-4 h-14 w-full bg-base-200 rounded-box"></div>
            </div>
        </div>
    </div>

    {{-- ======= CONTEÚDO PRINCIPAL ======= --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Contas --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="fa-solid fa-building-columns mr-2"></i> Contas do cliente
                </h2>
                <ul class="mt-2 space-y-3">
                    @forelse($accounts ?? [] as $a)
                        <li class="flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $a->name }}</div>
                                <div class="text-xs opacity-70">
                                    {{ $a->type }} @if (!$a->on_budget)
                                        • fora do orçamento
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs opacity-70 mt-1">
                                    Abertura: {{ number_format($a->opening_balance, 2, ',', '.') }}
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="opacity-70">Nenhuma conta cadastrada.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Transações recentes --}}
        <div class="card bg-base-100 shadow lg:col-span-2">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">
                        <i class="fa-solid fa-arrow-right-arrow-left mr-2"></i> Transações recentes
                    </h2>
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
                            @forelse($recentTransactions ?? [] as $tx)
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($tx->date)->format('d/m/Y') }}</td>
                                    <td>{{ $tx->account?->name ?? '—' }}</td>
                                    <td>{{ $tx->category?->name ?? '—' }}</td>
                                    <td class="text-right {{ ($tx->amount ?? 0) < 0 ? 'text-error' : 'text-success' }}">
                                        {{ number_format($tx->amount ?? 0, 2, ',', '.') }}
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
    </div>
@endsection
