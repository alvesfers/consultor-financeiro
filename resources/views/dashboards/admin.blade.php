@extends('layouts.app')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="stats shadow col-span-1 lg:col-span-3">
            <div class="stat">
                <div class="stat-title">Consultores</div>
                <div class="stat-value">{{ $totalConsultants }}</div>
            </div>
            <div class="stat">
                <div class="stat-title">Clientes</div>
                <div class="stat-value">{{ $totalClients }}</div>
            </div>
        </div>

        <div class="card bg-base-200">
            <div class="card-body">
                <h2 class="card-title">Top consultores</h2>
                <ul class="space-y-2">
                    @forelse($topConsultants as $c)
                        <li class="flex items-center justify-between">
                            <span>{{ $c->user->name ?? '—' }}</span>
                            <span class="badge badge-outline">{{ $c->clients_count }} clientes</span>
                        </li>
                    @empty
                        <li>Nenhum consultor</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card bg-base-200 lg:col-span-2">
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
                                {{ \Illuminate\Support\Carbon::parse($t->due_at)->format('d/m') }}</div>
                        </li>
                    @empty
                        <li class="py-2">Sem tarefas próximas.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
