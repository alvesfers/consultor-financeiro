{{-- resources/views/client/goals/index.blade.php --}}
@extends('layouts.app')

@section('content')
    @php
        function money_br($v)
        {
            return 'R$ ' . number_format((float) $v, 2, ',', '.');
        }

        // Define cor de fundo conforme o % gasto
        function bg_color_by_percent($p)
        {
            if ($p >= 90) {
                return 'bg-red-50';
            }
            if ($p >= 60) {
                return 'bg-amber-50';
            }
            return 'bg-green-50';
        }

        // Badge de status
        function badge_status($balance, $percent)
        {
            if ($balance < 0) {
                return ['badge-error', 'Ultrapassou'];
            }
            if ($percent >= 90) {
                return ['badge-warning', 'Atenção'];
            }
            return ['badge-success', 'Ok'];
        }

        function saldo_color($balance)
        {
            return $balance < 0 ? 'text-red-600' : 'text-green-600';
        }
    @endphp

    <div class="p-4 space-y-6" x-data="{
        months: {{ (int) $monthsCount }},
        endYm: '{{ $endYm }}',
        categoryId: {{ $filterCategoryId ? (int) $filterCategoryId : 'null' }},
        go() {
            const params = new URLSearchParams();
            params.set('months', this.months || 6);
            params.set('end', this.endYm || '');
            if (this.categoryId) params.set('category_id', this.categoryId);
            window.location = '{{ route('client.goals.index', ['consultant' => $consultantId]) }}' + '?' + params.toString();
        }
    }">

        {{-- Breadcrumb + título --}}
        <div class="breadcrumbs text-sm text-base-content/70">
            <ul>
                <li><a class="link" href="{{ route('client.dashboard', ['consultant' => $consultantId]) }}">Início</a></li>
                <li>Metas</li>
            </ul>
        </div>

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Metas • Comparativo mensal</h1>
                <p class="opacity-70 text-sm">Acompanhe seus gastos em relação às metas de cada mês.</p>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body grid gap-4 md:grid-cols-5">
                <div>
                    <label class="label"><span class="label-text">Meses</span></label>
                    <select class="select select-bordered w-full" x-model.number="months">
                        <option value="3">Últimos 3</option>
                        <option value="6" selected>Últimos 6</option>
                        <option value="12">Últimos 12</option>
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Mês final</span></label>
                    <input type="month" class="input input-bordered w-full" x-model="endYm">
                </div>

                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Categoria</span></label>
                    <select class="select select-bordered w-full" x-model.number="categoryId">
                        <option value="">Todas</option>
                        @foreach ($categoryOptions as $c)
                            <option value="{{ $c->id }}" @selected($filterCategoryId === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-1 flex items-end">
                    <button class="btn btn-primary w-full" @click="go()">
                        <i class="fa-solid fa-magnifying-glass mr-2"></i> Aplicar
                    </button>
                </div>
            </div>
        </div>

        {{-- Grid de cards mensais --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @forelse ($cards as $card)
                <div class="card bg-base-100 shadow-md border border-base-200">
                    <div class="card-body">
                        {{-- Cabeçalho --}}
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="card-title">{{ $card['label'] }}</h2>
                            <span class="badge badge-outline">{{ $card['ym'] }}</span>
                        </div>

                        @if (empty($card['items']))
                            <div class="text-sm opacity-70">Não há metas cadastradas para este mês.</div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="table table-compact w-full">
                                    <thead>
                                        <tr>
                                            <th>Categoria</th>
                                            <th class="text-right">Gasto</th>
                                            <th class="text-right">Meta</th>
                                            <th class="text-right">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($card['items'] as $it)
                                            @php
                                                $bg = bg_color_by_percent($it['percent']);
                                                [$badgeClass, $badgeText] = badge_status(
                                                    $it['balance'],
                                                    $it['percent'],
                                                );
                                            @endphp
                                            <tr class="{{ $bg }} hover:bg-opacity-75 transition">
                                                <td>
                                                    <div class="flex flex-col">
                                                        <span class="font-medium">{{ $it['category_name'] }}</span>
                                                        <span class="text-xs opacity-70">
                                                            <span
                                                                class="badge {{ $badgeClass }} badge-sm">{{ $badgeText }}</span>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="text-right font-medium">{{ money_br($it['spent']) }}</td>
                                                <td class="text-right">{{ money_br($it['limit']) }}</td>
                                                <td class="text-right font-semibold {{ saldo_color($it['balance']) }}">
                                                    {{ $it['balance'] >= 0 ? '+' : '-' }}{{ money_br(abs($it['balance'])) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="md:col-span-2 xl:col-span-3">
                    <div class="alert">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Nenhum dado encontrado para o intervalo selecionado.</span>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
