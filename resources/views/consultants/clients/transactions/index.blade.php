@extends('layouts.app')

@section('content')
    @php
        function money_br($v)
        {
            return 'R$ ' . number_format((float) $v, 2, ',', '.');
        }
        $hasAny = collect($filters)->some(fn($v) => filled($v));
        $toggleDir = fn($col) => request('sort', 'grp_created_at') === $col && request('dir', 'desc') === 'desc'
            ? 'asc'
            : 'desc';
        $orderLink = function ($col, $label) use ($toggleDir, $consultantId) {
            $params = array_merge(request()->query(), ['sort' => $col, 'dir' => $toggleDir($col)]);
            return route('client.transactions.index', ['consultant' => $consultantId] + $params);
        };
    @endphp

    <div class="p-4 space-y-6">

        {{-- HEADER + AÇÕES --}}
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl font-semibold">Transações</h1>
                <p class="text-sm text-base-content/60">Agrupadas por compra parcelada. Clique para ver parcelas.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('client.transactions.export', ['consultant' => $consultantId] + request()->query()) }}"
                    class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-file-arrow-down mr-2"></i> Exportar CSV
                </a>
                <a href="{{ route('client.transactions.index', ['consultant' => $consultantId]) }}"
                    class="btn btn-ghost btn-sm">
                    Limpar filtros
                </a>
            </div>
        </div>

        {{-- SUMÁRIO --}}
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-title">Total de grupos</div>
                <div class="stat-value text-primary">{{ number_format($summary['count']) }}</div>
                <div class="stat-desc">com os filtros atuais</div>
            </div>
            <div class="stat">
                <div class="stat-title">Entradas</div>
                <div class="stat-value text-success">{{ money_br($summary['in']) }}</div>
            </div>
            <div class="stat">
                <div class="stat-title">Saídas</div>
                <div class="stat-value text-error">{{ money_br($summary['out']) }}</div>
            </div>
        </div>

        {{-- BARRA DE FILTROS (colapsável) --}}
        <details class="collapse bg-base-200 rounded-box">
            <summary class="collapse-title text-md font-medium flex items-center gap-2">
                <i class="fa-solid fa-filter"></i> Filtros
                @if ($hasAny)
                    <span class="badge badge-primary badge-outline ml-2">ativos</span>
                @endif
            </summary>
            <div class="collapse-content">
                <form method="GET" class="grid md:grid-cols-6 grid-cols-2 gap-3">
                    <label class="form-control">
                        <span class="label-text">Grupo</span>
                        <select name="group_id" class="select select-bordered">
                            <option value="">Todos</option>
                            @foreach ($categoryGroups as $g)
                                <option value="{{ $g->id }}" @selected($filters['group_id'] == $g->id)>{{ $g->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-control">
                        <span class="label-text">Categoria</span>
                        <select name="category_id" class="select select-bordered">
                            <option value="">Todas</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" @selected($filters['category_id'] == $c->id)>{{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-control">
                        <span class="label-text">Subcategoria</span>
                        <select name="subcategory_id" class="select select-bordered">
                            <option value="">Todas</option>
                            @foreach ($subcategories as $s)
                                <option value="{{ $s->id }}" @selected($filters['subcategory_id'] === $s->id)>{{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-control">
                        <span class="label-text">Conta</span>
                        <input class="input input-bordered" name="account_id" type="number"
                            value="{{ $filters['account_id'] }}">
                    </label>

                    <label class="form-control">
                        <span class="label-text">Cartão</span>
                        <input class="input input-bordered" name="card_id" type="number"
                            value="{{ $filters['card_id'] }}">
                    </label>

                    <label class="form-control md:col-span-2">
                        <span class="label-text">Busca</span>
                        <input type="text" name="q" value="{{ $filters['q'] }}" class="input input-bordered"
                            placeholder="nota, método, status..." />
                    </label>

                    <label class="form-control">
                        <span class="label-text">De</span>
                        <input type="date" name="date_start" value="{{ $filters['date_start'] }}"
                            class="input input-bordered" />
                    </label>

                    <label class="form-control">
                        <span class="label-text">Até</span>
                        <input type="date" name="date_end" value="{{ $filters['date_end'] }}"
                            class="input input-bordered" />
                    </label>

                    <label class="form-control">
                        <span class="label-text">Itens/página</span>
                        <select name="per_page" class="select select-bordered">
                            @foreach ([10, 20, 50, 100] as $n)
                                <option value="{{ $n }}" @selected((int) request('per_page', 20) === $n)>{{ $n }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div class="md:col-span-2 flex gap-2 items-end">
                        <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass mr-2"></i>Aplicar</button>
                        <a href="{{ route('client.transactions.index', ['consultant' => $consultantId]) }}"
                            class="btn btn-ghost">Limpar</a>
                    </div>
                </form>

                @if ($hasAny)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach (['group_id' => 'Grupo', 'category_id' => 'Categoria', 'subcategory_id' => 'Subcategoria', 'account_id' => 'Conta', 'card_id' => 'Cartão'] as $key => $label)
                            @if (filled($filters[$key]))
                                <span class="badge badge-outline">{{ $label }} #{{ $filters[$key] }}</span>
                            @endif
                        @endforeach
                        @if (filled($filters['q']))
                            <span class="badge badge-outline">Busca: “{{ $filters['q'] }}”</span>
                        @endif
                        @if (filled($filters['date_start']) || filled($filters['date_end']))
                            <span class="badge badge-outline">Período: {{ $filters['date_start'] ?: '∞' }} →
                                {{ $filters['date_end'] ?: '∞' }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </details>

        {{-- LISTA MOBILE (cards) --}}
        <div class="md:hidden space-y-3">
            @forelse ($groups as $g)
                @php
                    $isNeg = ((float) $g->sum_amount) < 0;
                    $parcelas = $g->installment_count ?: $g->items_count;
                    $amountClass = $isNeg ? 'text-error' : 'text-success';
                    $date = \Carbon\Carbon::parse($g->repr_date)->format('d/m');
                @endphp
                <details class="collapse bg-base-100 shadow-sm">
                    <summary class="collapse-title p-4">
                        <div class="flex items-center gap-3">
                            <div class="avatar">
                                <div class="w-8 rounded">
                                    @if ($g->bank_logo)
                                        <img src="{{ $g->bank_logo }}" alt="bank" />
                                    @else
                                        <i class="fa-solid fa-landmark mt-1"></i>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium line-clamp-1">{{ $g->notes ?: '—' }}</div>
                                <div class="text-xs text-base-content/60">Ref.: {{ $date }} •
                                    {{ $g->method ?: '—' }} • {{ $g->status ?: '—' }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold {{ $amountClass }}">{{ money_br($g->sum_amount) }}</div>
                                @if ($parcelas > 1)
                                    <div class="text-[11px] text-base-content/60">{{ $parcelas }}x</div>
                                @endif
                            </div>
                        </div>
                    </summary>
                    <div class="collapse-content pt-0">
                        <div class="overflow-x-auto">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Conta</th>
                                        <th>Cartão</th>
                                        <th>Categoria</th>
                                        <th class="text-right">Valor</th>
                                        <th class="text-center">Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($g->items as $it)
                                        @php
                                            $cat = trim(
                                                ($it->category_name ?: '—') .
                                                    ($it->subcategory_name ? ' / ' . $it->subcategory_name : ''),
                                            );
                                            $dt = \Carbon\Carbon::parse($it->date)->format('d/m');
                                        @endphp
                                        <tr>
                                            <td>{{ $it->installment_index ?: '—' }}</td>
                                            <td>{{ $it->account_name ?: '—' }}</td>
                                            <td>{{ $it->card_name ?: '—' }}</td>
                                            <td>{{ $cat }}</td>
                                            <td class="text-right">{{ money_br($it->amount) }}</td>
                                            <td class="text-center">{{ $dt }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            @empty
                <div class="text-center text-base-content/60 py-8">Sem transações com estes filtros.</div>
            @endforelse
        </div>

        {{-- TABELA DESKTOP --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th class="w-12">Banco</th>
                        <th><a class="link" href="{{ $orderLink('notes', 'Notas') }}">Notas</a></th>
                        <th class="text-center">Parcelas</th>
                        <th class="text-right"><a class="link" href="{{ $orderLink('sum_amount', 'Total') }}">Total</a>
                        </th>
                        <th class="text-center"><a class="link" href="{{ $orderLink('repr_date', 'Data ref.') }}">Data
                                ref.</a></th>
                        <th class="text-center"><a class="link"
                                href="{{ $orderLink('grp_created_at', 'Criado em') }}">Criado em</a></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $g)
                        @php
                            $isNeg = ((float) $g->sum_amount) < 0;
                            $amountClass = $isNeg ? 'text-error' : 'text-success';
                            $parcelas = $g->installment_count ?: $g->items_count;
                            $date = \Carbon\Carbon::parse($g->repr_date)->format('d/m');
                            $created = \Carbon\Carbon::parse($g->grp_created_at)->format('d/m H:i');
                        @endphp

                        <tr class="hover">
                            <td>
                                @if ($g->bank_logo)
                                    <img src="{{ $g->bank_logo }}" class="w-6 h-6 object-contain" alt="bank" />
                                @else
                                    <i class="fa-solid fa-landmark text-base-content/60"></i>
                                @endif
                            </td>
                            <td class="align-top">
                                <details>
                                    <summary class="font-medium cursor-pointer">
                                        {{ $g->notes ?: '—' }}
                                        <span class="ml-2 badge badge-ghost">{{ $g->method ?: '—' }}</span>
                                        <span class="ml-2 badge badge-ghost">{{ $g->status ?: '—' }}</span>
                                    </summary>
                                    <div class="mt-2">
                                        <div class="overflow-x-auto">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Conta</th>
                                                        <th>Cartão</th>
                                                        <th>Categoria</th>
                                                        <th class="text-right">Valor</th>
                                                        <th class="text-center">Data</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($g->items as $it)
                                                        @php
                                                            $cat = trim(
                                                                ($it->category_name ?: '—') .
                                                                    ($it->subcategory_name
                                                                        ? ' / ' . $it->subcategory_name
                                                                        : ''),
                                                            );
                                                            $dt = \Carbon\Carbon::parse($it->date)->format('d/m');
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $it->installment_index ?: '—' }}</td>
                                                            <td>{{ $it->account_name ?: '—' }}</td>
                                                            <td>{{ $it->card_name ?: '—' }}</td>
                                                            <td>{{ $cat }}</td>
                                                            <td class="text-right">{{ money_br($it->amount) }}</td>
                                                            <td class="text-center">{{ $dt }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </details>
                            </td>
                            <td class="text-center">
                                @if ($parcelas > 1)
                                    <span class="badge badge-outline">{{ $parcelas }}x</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-right font-semibold {{ $amountClass }}">{{ money_br($g->sum_amount) }}</td>
                            <td class="text-center">{{ $date }}</td>
                            <td class="text-center text-base-content/70">{{ $created }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-base-content/60">Sem transações para os filtros
                                aplicados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINAÇÃO --}}
        <div class="flex justify-between items-center gap-3 flex-wrap">
            <div class="text-sm text-base-content/60">
                Mostrando <span class="font-medium">{{ $groups->firstItem() }}</span>–<span
                    class="font-medium">{{ $groups->lastItem() }}</span>
                de <span class="font-medium">{{ $groups->total() }}</span> grupos
            </div>
            <div>{{ $groups->onEachSide(1)->links() }}</div>
        </div>
    </div>
@endsection
