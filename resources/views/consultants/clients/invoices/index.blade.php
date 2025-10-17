{{-- resources/views/client/invoices/index.blade.php --}}
@extends('layouts.app')

@section('content')
    @php
        // Helpers
        function money_br($v)
        {
            return 'R$ ' . number_format((float) $v, 2, ',', '.');
        }

        $f = $filters ?? [
            'bank_id' => null,
            'account_id' => null,
            'card_id' => null,
            'status' => null,
            'month' => null,
            'q' => null,
        ];
    @endphp

    <div class="p-4 space-y-6" x-data="invoiceList({
        initial: @json($invoices->items()),
        summary: @json($summary),
        filters: @json($f)
    })" x-init="init()">

        {{-- Breadcrumb --}}
        <div class="breadcrumbs text-sm text-base-content/70">
            <ul>
                <li><a href="{{ route('client.dashboard', ['consultant' => $consultantId]) }}" class="link">Início</a></li>
                <li><a href="{{ route('client.invoices.index', ['consultant' => $consultantId]) }}" class="link">Faturas</a>
                </li>
                <li class="text-base-content">Listagem</li>
            </ul>
        </div>

        {{-- Título --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="avatar placeholder">
                    <div class="bg-primary/10 text-primary rounded-xl w-12 h-12 flex items-center justify-center">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Faturas de Cartão</h1>
                    <p class="text-sm text-base-content/70">Filtre por banco, conta, cartão, status e mês.</p>
                </div>
            </div>

            <div class="flex gap-2">
                <a class="btn btn-ghost btn-sm"
                    href="{{ route('client.invoices.index', ['consultant' => $consultantId]) }}">
                    <i class="fa-solid fa-rotate mr-2"></i> Limpar filtros
                </a>
            </div>
        </div>

        {{-- Stats --}}
        <div class="stats shadow-sm bg-base-100 rounded-xl grid md:grid-cols-4">
            <div class="stat">
                <div class="stat-figure text-primary"><i class="fa-solid fa-coins"></i></div>
                <div class="stat-title">Total no filtro</div>
                <div class="stat-value" x-text="formatMoney(summary.total)">{{ money_br($summary['total']) }}</div>
            </div>
            <div class="stat">
                <div class="stat-figure text-warning"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-title">Em aberto</div>
                <div class="stat-value" x-text="formatMoney(summary.open)">{{ money_br($summary['open']) }}</div>
            </div>
            <div class="stat">
                <div class="stat-figure text-success"><i class="fa-solid fa-check-double"></i></div>
                <div class="stat-title">Pagas</div>
                <div class="stat-value" x-text="formatMoney(summary.paid)">{{ money_br($summary['paid']) }}</div>
            </div>
            <div class="stat">
                <div class="stat-figure text-error"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-title">Vencidas</div>
                <div class="stat-value" x-text="formatMoney(summary.overdue)">{{ money_br($summary['overdue']) }}</div>
            </div>
        </div>

        {{-- Card de filtros --}}
        <div class="card shadow-sm bg-base-100">
            <div class="card-body gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="join w-full md:w-auto">
                        <input type="text" name="q" form="filtersForm"
                            class="input input-bordered join-item w-full md:w-96"
                            placeholder="Buscar por banco, cartão, conta..." value="{{ $f['q'] ?? '' }}">
                        <button form="filtersForm" class="btn btn-primary join-item">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>

                    <div class="flex-1"></div>

                    <details class="dropdown">
                        <summary class="btn btn-ghost"><i class="fa-solid fa-sliders mr-2"></i> Filtros</summary>
                        <ul class="dropdown-content z-[1] menu p-4 shadow bg-base-100 rounded-box w-[92vw] md:w-[720px]">
                            <form id="filtersForm" method="GET"
                                action="{{ route('client.invoices.index', ['consultant' => $consultantId]) }}"
                                class="grid md:grid-cols-12 gap-3">
                                <div class="form-control md:col-span-3">
                                    <label class="label"><span class="label-text">Banco</span></label>
                                    <select name="bank_id" class="select select-bordered">
                                        <option value="">Todos</option>
                                        @foreach ($banks as $b)
                                            <option value="{{ $b->id }}" @selected(($f['bank_id'] ?? null) == $b->id)>
                                                {{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-control md:col-span-3">
                                    <label class="label"><span class="label-text">Conta de pagamento</span></label>
                                    <select name="account_id" class="select select-bordered">
                                        <option value="">Todas</option>
                                        @foreach ($accounts as $a)
                                            <option value="{{ $a->id }}" @selected(($f['account_id'] ?? null) == $a->id)>
                                                {{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-control md:col-span-3">
                                    <label class="label"><span class="label-text">Cartão</span></label>
                                    <select name="card_id" class="select select-bordered">
                                        <option value="">Todos</option>
                                        @foreach ($cards as $c)
                                            <option value="{{ $c->id }}" @selected(($f['card_id'] ?? null) == $c->id)>
                                                {{ $c->name }} @if ($c->last4)
                                                    •••• {{ $c->last4 }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-control md:col-span-2">
                                    <label class="label"><span class="label-text">Status</span></label>
                                    <select name="status" class="select select-bordered">
                                        <option value="">Todos</option>
                                        <option value="open" @selected(($f['status'] ?? null) === 'open')>Em aberto</option>
                                        <option value="paid" @selected(($f['status'] ?? null) === 'paid')>Paga</option>
                                        <option value="overdue" @selected(($f['status'] ?? null) === 'overdue')>Vencida</option>
                                    </select>
                                </div>

                                <div class="form-control md:col-span-2">
                                    <label class="label"><span class="label-text">Mês</span></label>
                                    <input type="month" name="month" class="input input-bordered"
                                        value="{{ $f['month'] }}">
                                </div>

                                <div class="md:col-span-2 flex items-end gap-2">
                                    <button class="btn btn-primary w-full" type="submit">
                                        <i class="fa-solid fa-filter mr-2"></i> Aplicar
                                    </button>
                                </div>
                            </form>
                        </ul>
                    </details>
                </div>

                {{-- Chips de filtros ativos --}}
                <div class="flex flex-wrap gap-2">
                    @if ($f['bank_id'])
                        <div class="badge badge-outline gap-2">
                            Banco: {{ optional($banks->firstWhere('id', $f['bank_id']))->name }}
                            <a class="link" href="{{ request()->fullUrlWithQuery(['bank_id' => null]) }}">✕</a>
                        </div>
                    @endif
                    @if ($f['account_id'])
                        <div class="badge badge-outline gap-2">
                            Conta: {{ optional($accounts->firstWhere('id', $f['account_id']))->name }}
                            <a class="link" href="{{ request()->fullUrlWithQuery(['account_id' => null]) }}">✕</a>
                        </div>
                    @endif
                    @if ($f['card_id'])
                        <div class="badge badge-outline gap-2">
                            Cartão: {{ optional($cards->firstWhere('id', $f['card_id']))->name }}
                            <a class="link" href="{{ request()->fullUrlWithQuery(['card_id' => null]) }}">✕</a>
                        </div>
                    @endif
                    @if ($f['status'])
                        <div class="badge badge-outline gap-2">
                            Status: {{ ucfirst($f['status']) }}
                            <a class="link" href="{{ request()->fullUrlWithQuery(['status' => null]) }}">✕</a>
                        </div>
                    @endif
                    @if ($f['month'])
                        <div class="badge badge-outline gap-2">
                            Mês: {{ $f['month'] }}
                            <a class="link" href="{{ request()->fullUrlWithQuery(['month' => null]) }}">✕</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabela --}}
        <div class="card shadow-sm bg-base-100">
            <div class="card-body p-0">
                @if ($invoices->count() === 0)
                    <div class="hero min-h-[260px]">
                        <div class="hero-content text-center">
                            <div>
                                <div class="text-5xl mb-2">🧾</div>
                                <h2 class="text-xl font-semibold">Nenhuma fatura encontrada</h2>
                                <p class="text-base-content/70">Ajuste os filtros acima para ver resultados.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead class="sticky top-0 z-10 bg-base-200">
                                <tr>
                                    <th>Mês</th>
                                    <th>Cartão</th>
                                    <th>Banco</th>
                                    <th>Conta pgto</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-right">Pago</th>
                                    <th class="w-52">Progresso</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoices as $inv)
                                    @php
                                        $pct = max(
                                            0,
                                            min(
                                                100,
                                                (float) $inv->total_amount > 0
                                                    ? round(($inv->paid_amount / $inv->total_amount) * 100)
                                                    : 100,
                                            ),
                                        );
                                        $badge =
                                            [
                                                'open' => 'badge-warning',
                                                'paid' => 'badge-success',
                                                'overdue' => 'badge-error',
                                            ][$inv->status] ?? 'badge-ghost';
                                    @endphp
                                    <tr>
                                        <td class="whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($inv->month_ref)->translatedFormat('MMMM/Y') }}
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <div class="avatar placeholder">
                                                    <div class="bg-neutral text-neutral-content w-8 rounded">
                                                        <i class="fa-regular fa-credit-card text-xs"></i>
                                                    </div>
                                                </div>
                                                <div class="font-medium">
                                                    {{ $inv->card_name }}
                                                    @if ($inv->card_last4)
                                                        <span class="text-base-content/60"> ••••
                                                            {{ $inv->card_last4 }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $inv->bank_name ?? '-' }}</td>
                                        <td>{{ $inv->pay_account_name ?? '-' }}</td>
                                        <td class="text-right">{{ money_br($inv->total_amount) }}</td>
                                        <td class="text-right">{{ money_br($inv->paid_amount) }}</td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <progress class="progress progress-primary w-40"
                                                    value="{{ $pct }}" max="100"></progress>
                                                <span
                                                    class="text-xs text-base-content/70 w-10 text-right">{{ $pct }}%</span>
                                            </div>
                                        </td>
                                        <td class="{{ $inv->status === 'overdue' ? 'text-error font-medium' : '' }}">
                                            {{ \Carbon\Carbon::parse($inv->due_on)->format('d/m/Y') }}
                                        </td>
                                        <td><span class="badge {{ $badge }}">{{ ucfirst($inv->status) }}</span>
                                        </td>
                                        <td class="text-right">
                                            <div class="dropdown dropdown-end">
                                                <div tabindex="0" role="button" class="btn btn-ghost btn-xs">
                                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                                </div>
                                                <ul tabindex="0"
                                                    class="dropdown-content menu bg-base-100 rounded-box z-[1] w-56 p-2 shadow">
                                                    <li>
                                                        <a
                                                            href="{{ route('client.invoices.show', ['consultant' => $consultantId, 'invoice' => $inv->id]) }}">
                                                            <i class="fa-regular fa-eye"></i> Ver detalhes
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button type="button"
                                                            @click="openPayModal('{{ route('client.invoices.markPaid', ['consultant' => $consultantId, 'invoice' => $inv->id]) }}')">
                                                            <i class="fa-solid fa-check"></i> Marcar como paga
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginação --}}
                    <div class="p-4">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-base-content/70">
                                Mostrando {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} de
                                {{ $invoices->total() }}
                            </div>
                            <div class="flex-1 flex justify-end">
                                {{ $invoices->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Modal: marcar como paga --}}
        <dialog id="payModal" class="modal">
            <div class="modal-box">
                <h3 class="font-bold text-lg"><i class="fa-solid fa-check mr-2"></i> Marcar fatura como paga</h3>
                <p class="text-sm text-base-content/70">Confirma marcar esta fatura como paga? Isso atualizará todas as
                    transações do ciclo.</p>
                <div class="modal-action">
                    <form method="dialog"><button class="btn">Cancelar</button></form>
                    <form id="payForm" method="POST" action="#">
                        @csrf
                        <button class="btn btn-primary" type="submit">Confirmar</button>
                    </form>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>
    </div>

    {{-- Alpine.js --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('invoiceList', (params) => ({
                items: params.initial || [],
                summary: params.summary || {
                    total: 0,
                    open: 0,
                    paid: 0,
                    overdue: 0
                },
                filters: Object.assign({
                    bank_id: null,
                    account_id: null,
                    card_id: null,
                    status: null,
                    month: null,
                    q: ''
                }, params.filters || {}),
                copyMessage: '',

                init() {},

                formatMoney(v) {
                    try {
                        return Number(v ?? 0).toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        });
                    } catch (e) {
                        return 'R$ 0,00';
                    }
                },

                openPayModal(actionUrl) {
                    const form = document.getElementById('payForm');
                    form?.setAttribute('action', actionUrl);
                    document.getElementById('payModal')?.showModal();
                }
            }));
        });
    </script>
@endsection
