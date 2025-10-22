@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

        {{-- Flash --}}
        @if (session('success'))
            <div class="alert alert-success">
                <i class="fa-regular fa-circle-check me-2"></i>{{ session('success') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="avatar placeholder">
                    <div class="bg-primary/15 text-primary-content rounded-full w-12">
                        <span class="text-lg">R</span>
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-bold leading-tight">Regras Recorrentes</h1>
                    <p class="text-xs opacity-70">Cliente #{{ $clientId }} • Consultor #{{ $consultantId }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('client.recurrents.create', ['consultant' => $consultantId]) }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-2"></i> Nova Regra
                </a>
            </div>
        </div>

        {{-- Stats pequeninos --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="stat bg-base-200 rounded-box shadow-sm">
                <div class="stat-title">Total de regras</div>
                <div class="stat-value text-base">{{ $rules->total() }}</div>
            </div>
            <div class="stat bg-base-200 rounded-box shadow-sm">
                <div class="stat-title">Ativas</div>
                <div class="stat-value text-base">
                    {{ $rules->getCollection()->where('is_active', true)->count() }}
                </div>
            </div>
            <div class="stat bg-base-200 rounded-box shadow-sm">
                <div class="stat-title">Com valor padrão</div>
                <div class="stat-value text-base">
                    {{ $rules->getCollection()->whereNotNull('amount')->count() }}
                </div>
            </div>
        </div>

        {{-- Tabela --}}
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-0">
                @if ($rules->count() === 0)
                    <div class="p-8 text-center">
                        <div class="mb-3">
                            <i class="fa-regular fa-calendar-check text-3xl opacity-60"></i>
                        </div>
                        <h2 class="text-lg font-semibold mb-1">Nenhuma regra cadastrada</h2>
                        <p class="opacity-70 mb-4">Crie sua primeira regra para salários, assinaturas e contas fixas.</p>
                        <a href="{{ route('client.recurrents.create', ['consultant' => $consultantId]) }}"
                            class="btn btn-primary">
                            <i class="fa-solid fa-plus me-2"></i> Nova Regra
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th class="w-[28%]">Nome</th>
                                    <th>Tipo</th>
                                    <th>Método</th>
                                    <th>Lançar em</th>
                                    <th class="text-right">Valor</th>
                                    <th>Freq.</th>
                                    <th>Início</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rules as $r)
                                    <tr>
                                        <td class="font-medium">
                                            <div class="flex items-center gap-2">
                                                <i class="fa-regular fa-rotate fa-xs opacity-60"></i>
                                                {{ $r->name }}
                                            </div>
                                            @if ($r->merchant)
                                                <div class="text-xs opacity-70">{{ $r->merchant }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $typeMap = [
                                                    'income' => 'Receita',
                                                    'expense' => 'Despesa',
                                                    'transfer' => 'Transf.',
                                                    'adjustment' => 'Ajuste',
                                                ];
                                                $typeBadge = match ($r->type) {
                                                    'income' => 'badge-success',
                                                    'expense' => 'badge-error',
                                                    'transfer' => 'badge-info',
                                                    default => 'badge-ghost',
                                                };
                                            @endphp
                                            <span
                                                class="badge {{ $typeBadge }} badge-sm">{{ $typeMap[$r->type] ?? $r->type }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-ghost badge-sm">
                                                {{ $r->method ? ucfirst(str_replace('_', ' ', $r->method)) : '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($r->card)
                                                <div class="badge badge-outline badge-sm">Cartão</div>
                                                <div class="text-xs opacity-70">{{ $r->card->name }}</div>
                                            @elseif($r->account)
                                                <div class="badge badge-outline badge-sm">Conta</div>
                                                <div class="text-xs opacity-70">{{ $r->account->name }}</div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            {{ is_null($r->amount) ? '—' : number_format($r->amount, 2, ',', '.') }}
                                        </td>
                                        <td>{{ $r->freq }}/{{ $r->interval }}</td>
                                        <td>{{ optional($r->start_date)->format('d/m/Y') }}</td>
                                        <td class="text-center">
                                            @if ($r->is_active)
                                                <span class="badge badge-success badge-sm">
                                                    <i class="fa-solid fa-circle-dot me-1 text-[10px]"></i> ativa
                                                </span>
                                            @else
                                                <span class="badge badge-ghost badge-sm">inativa</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginação --}}
                    <div class="p-4">
                        {{ $rules->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
