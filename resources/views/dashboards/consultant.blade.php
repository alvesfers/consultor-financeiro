@extends('layouts.app')

@section('content')
@php
    // recupera o consultor da rota ou do usuário autenticado
    $routeConsultant = request()->route('consultant');
    $consultantId = is_object($routeConsultant) ? $routeConsultant->id : $routeConsultant;
    $consultantId ??= auth()->user()->consultant?->id;
@endphp

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Dashboard do Consultor</h1>

    <div class="flex gap-2">
        <a href="{{ route('consultant.clients.index', ['consultant' => $consultantId]) }}"
           class="btn btn-sm btn-outline">
            Meus clientes
        </a>
        <a href="{{ route('consultant.tasks.index', ['consultant' => $consultantId]) }}"
           class="btn btn-sm btn-outline">
            Tarefas
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <!-- KPIs -->
    <div class="stats shadow col-span-1 lg:col-span-3">
        <div class="stat">
            <div class="stat-title">Clientes</div>
            <div class="stat-value">{{ $stats['clients'] ?? 0 }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Tarefas próximas</div>
            <div class="stat-value">{{ $stats['tasks_due'] ?? 0 }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Transações recentes (10)</div>
            <div class="stat-value">{{ $stats['tx_last10'] ?? 0 }}</div>
        </div>
    </div>

    <!-- Tarefas vencendo -->
    <div class="card bg-base-200">
        <div class="card-body">
            <h2 class="card-title">Tarefas vencendo (7 dias)</h2>
            <ul class="divide-y divide-base-300">
                @forelse($tasksExpiring as $t)
                    <li class="py-2 flex items-center justify-between">
                        <div>
                            <div class="font-medium">{{ $t->title }}</div>
                            <div class="text-sm opacity-70">Cliente: {{ $t->client->user->name ?? '—' }}</div>
                        </div>
                        <div class="badge badge-warning">
                            {{ \Illuminate\Support\Carbon::parse($t->due_at)->format('d/m') }}
                        </div>
                    </li>
                @empty
                    <li class="py-2">Sem tarefas próximas.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Transações recentes -->
    <div class="card bg-base-200 lg:col-span-2">
        <div class="card-body">
            <h2 class="card-title">Transações recentes</h2>
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $tx)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($tx->date)->format('d/m/Y') }}</td>
                                <td>{{ $tx->client->user->name ?? '—' }}</td>
                                <td class="{{ $tx->amount < 0 ? 'text-error' : 'text-success' }}">
                                    {{ number_format($tx->amount, 2, ',', '.') }}
                                </td>
                                <td>{{ $tx->notes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center opacity-70 py-3">Sem transações recentes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
