@extends('layouts.app')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="stats shadow col-span-1 lg:col-span-3">
            <div class="stat">
                <div class="stat-title">Saldo Atual</div>
                <div class="stat-value">{{ number_format($balance, 2, ',', '.') }}</div>
            </div>
            <div class="stat">
                <div class="stat-title">Contas</div>
                <div class="stat-value">{{ $accounts->count() }}</div>
            </div>
            <div class="stat">
                <div class="stat-title">Maior gasto (30d)</div>
                <div class="stat-value text-warning">
                    {{ $topCategory?->category?->name ?? '—' }}
                </div>
            </div>
        </div>

        <div class="card bg-base-200">
            <div class="card-body">
                <h2 class="card-title">Minhas Contas</h2>
                <ul class="space-y-2">
                    @forelse($accounts as $a)
                        <li class="flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $a->name }}</div>
                                <div class="text-xs opacity-70">{{ $a->type }} @if (!$a->on_budget)
                                        • fora do orçamento
                                    @endif
                                </div>
                            </div>
                            <div class="badge badge-outline">{{ $a->currency ?? 'BRL' }}</div>
                        </li>
                    @empty
                        <li>Nenhuma conta cadastrada.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card bg-base-200 lg:col-span-2">
            <div class="card-body">
                <h2 class="card-title">Transações recentes</h2>
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Conta</th>
                                <th>Valor</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $tx)
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($tx->date)->format('d/m/Y') }}</td>
                                    <td>{{ $tx->account->name ?? '—' }}</td>
                                    <td class="{{ $tx->amount < 0 ? 'text-error' : 'text-success' }}">
                                        {{ number_format($tx->amount, 2, ',', '.') }}
                                    </td>
                                    <td>{{ $tx->notes }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">Sem transações.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
