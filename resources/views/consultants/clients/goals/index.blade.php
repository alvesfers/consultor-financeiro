@extends('layouts.app')

@section('content')
    @php
        // ========= Helpers usadas no template =========
        function money_br($v)
        {
            return 'R$ ' . number_format((float) $v, 2, ',', '.');
        }

        // Define cor de fundo conforme o % gasto (null => neutro)
        function bg_color_by_percent($p)
        {
            if (is_null($p)) {
                return 'bg-base-100';
            } // sem meta
            if ($p >= 90) {
                return 'bg-red-50';
            }
            if ($p >= 60) {
                return 'bg-amber-50';
            }
            return 'bg-green-50';
        }

        // Badge de status (sem meta => neutro)
        function badge_status($balance, $percent, $hasGoal)
        {
            if (!$hasGoal) {
                return ['badge-ghost', 'Sem meta'];
            }
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
            if (is_null($balance)) {
                return '';
            }
            return $balance < 0 ? 'text-red-600' : 'text-green-600';
        }

        // ========= Dados para o componente =========
        $breakdownByMonth = collect($cards)->mapWithKeys(fn($c) => [$c['ym'] => $c['subs_breakdown']]);
        $namesMap = collect($cards)->flatMap(
            fn($c) => collect($c['items'])->mapWithKeys(fn($i) => [$i['category_id'] => $i['category_name']]),
        );
    @endphp

    <script>
        // Deixa os dados prontos num objeto global simples
        window._goalsInit = {
            months: {{ (int) $monthsCount }},
            endYm: @json($endYm),
            categoryId: {!! $filterCategoryId ? (int) $filterCategoryId : 'null' !!},
            breakdownByMonth: @json($breakdownByMonth),
            names: @json($namesMap),
        };

        // Define o componente ANTES do x-data usar
        window.goalsPage = function(initial) {
            return {
                months: initial.months,
                endYm: initial.endYm,
                categoryId: initial.categoryId,
                breakdownByMonth: initial.breakdownByMonth || {},
                names: initial.names || {},
                modal: {
                    title: '',
                    items: [],
                    total_br: 'R$ 0,00'
                },

                go() {
                    const params = new URLSearchParams();
                    params.set('months', this.months || 6);
                    params.set('end', this.endYm || '');
                    if (this.categoryId) params.set('category_id', this.categoryId);
                    window.location = @json(route('client.goals.index', ['consultant' => $consultantId])) + '?' + params.toString();
                },

                openBreakdown(ym, categoryId) {
                    const map = this.breakdownByMonth?.[ym] ?? {};
                    const rows = map?.[categoryId] ?? [];
                    let total = 0;

                    const fmt = (v) => {
                        const n = Number(v || 0);
                        return 'R$ ' + n.toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    };

                    const items = rows.map(r => {
                        total += Number(r.spent || 0);
                        return {
                            ...r,
                            spent_br: fmt(r.spent)
                        };
                    });

                    this.modal.title = (this.names?.[categoryId] || 'Categoria') + ' • ' + ym;
                    this.modal.items = items;
                    this.modal.total_br = fmt(total);

                    this.$refs.subsModal.showModal();
                }
            }
        }
    </script>

    <div class="p-4 space-y-6" x-data="goalsPage(window._goalsInit)">

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
                <p class="opacity-70 text-sm">
                    Inclui categorias de despesas mesmo sem meta. Clique em uma categoria para ver o detalhamento por
                    subcategoria.
                </p>
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
                    <label class="label"><span class="label-text">Categoria (Despesas)</span></label>
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
                            <div class="text-sm opacity-70">Não há dados para este mês.</div>
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
                                                $hasGoal = !is_null($it['limit']);
                                                $bg = bg_color_by_percent($it['percent']);
                                                [$badgeClass, $badgeText] = badge_status(
                                                    $it['balance'],
                                                    $it['percent'],
                                                    $hasGoal,
                                                );
                                            @endphp
                                            <tr class="{{ $bg }} hover:bg-base-200 cursor-pointer"
                                                @click="openBreakdown('{{ $card['ym'] }}', {{ $it['category_id'] }})">
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        <i class="fa-solid fa-chevron-right text-xs opacity-60"></i>
                                                        <div class="flex flex-col">
                                                            <span class="font-medium">{{ $it['category_name'] }}</span>
                                                            <span class="text-xs opacity-70">
                                                                <span
                                                                    class="badge {{ $badgeClass }} badge-sm">{{ $badgeText }}</span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-right font-medium">{{ money_br($it['spent']) }}</td>
                                                <td class="text-right">
                                                    {{ $hasGoal ? money_br($it['limit']) : '—' }}
                                                </td>
                                                <td class="text-right font-semibold {{ saldo_color($it['balance']) }}">
                                                    @if (is_null($it['balance']))
                                                        —
                                                    @else
                                                        {{ $it['balance'] >= 0 ? '+' : '-' }}{{ money_br(abs($it['balance'])) }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>

                                    {{-- Rodapé: total do mês --}}
                                    <tfoot>
                                        <tr>
                                            <th class="text-right">Total do mês</th>
                                            <th class="text-right font-bold">{{ money_br($card['total_spent']) }}</th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
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

        {{-- Modal de detalhamento por subcategoria --}}
        <dialog id="subsModal" class="modal" x-ref="subsModal">
            <div class="modal-box max-w-lg">
                <h3 class="font-bold text-lg mb-2">
                    <i class="fa-solid fa-list mr-2"></i>
                    <span x-text="modal.title"></span>
                </h3>

                <div x-show="modal.items.length === 0" class="opacity-70">Sem gastos neste mês.</div>

                <div x-show="modal.items.length > 0" class="overflow-x-auto">
                    <table class="table table-compact w-full">
                        <thead>
                            <tr>
                                <th>Subcategoria</th>
                                <th class="text-right">Gasto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in modal.items" :key="row.id">
                                <tr>
                                    <td x-text="row.name"></td>
                                    <td class="text-right font-medium" x-text="row.spent_br"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="text-right">Total</th>
                                <th class="text-right" x-text="modal.total_br"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="modal-action">
                    <form method="dialog">
                        <button class="btn">Fechar</button>
                    </form>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

    </div>
@endsection
