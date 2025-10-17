@extends('layouts.app')

@section('content')
@php
    // Helpers locais
    function money_br($v) { return 'R$ ' . number_format((float) $v, 2, ',', '.'); }

    // Filtros atuais vindos do controller
    $f = $filters ?? [
        'bank_id' => null, 'account_id' => null, 'card_id' => null,
        'status' => null, 'year' => null, 'month' => null, 'q' => null,
    ];

    $statusBadge = function (string $s) {
        return match ($s) {
            'paid'    => '<span class="badge badge-success gap-1"><i class="fa-solid fa-circle-check text-xs"></i> paga</span>',
            'overdue' => '<span class="badge badge-error gap-1"><i class="fa-solid fa-triangle-exclamation text-xs"></i> vencida</span>',
            default   => '<span class="badge badge-warning gap-1"><i class="fa-regular fa-clock text-xs"></i> em aberto</span>',
        };
    };

    // Opções de ano (últimos 5, atual, próximos 2)
    $yNow  = (int) date('Y');
    $years = range($yNow - 5, $yNow + 2);

    // Meses PT-BR
    $meses = [1=>'Jan',2=>'Fev',3=>'Mar',4=>'Abr',5=>'Mai',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Set',10=>'Out',11=>'Nov',12=>'Dez'];
@endphp

{{-- ====== Dados globais p/ Alpine (antes de iniciar) ====== --}}
<script>
    window.INV_INIT = {
        consultantId: @json($consultantId),
        // listas "cruas" para filtrar no front
        banks   : @js(collect($banks)->map(fn($b)=>['id'=>(int)$b->id,'name'=>$b->name])->values()),
        accounts: @js(collect($accounts)->map(fn($a)=>['id'=>(int)$a->id,'name'=>$a->name,'bank_id'=>(int)($a->bank_id ?? 0)])->values()),
        cards   : @js(collect($cards)->map(fn($c)=>['id'=>(int)$c->id,'name'=>$c->name,'last4'=>$c->last4,'bank_id'=>(int)($c->bank_id ?? 0),'account_id'=>(int)($c->payment_account_id ?? 0)])->values()),
        // filtros atuais vindos do back
        filters : {
            bank_id   : {{ $f['bank_id'] ? (int)$f['bank_id'] : 'null' }},
            account_id: {{ $f['account_id'] ? (int)$f['account_id'] : 'null' }},
            card_id   : {{ $f['card_id'] ? (int)$f['card_id'] : 'null' }},
            status    : @json($f['status'] ?? ''),
            year      : {{ $f['year'] ? (int)$f['year'] : 'null' }},
            month     : {{ $f['month'] ? (int)$f['month'] : 'null' }},
            q         : @json($f['q'] ?? ''),
        },
        // rota base (sem querystring)
        baseUrl: @json(route('client.invoices.index', ['consultant' => $consultantId])),
    };

    window.invoicesPage = function(init){
        return {
            baseUrl: init.baseUrl,
            banks: init.banks || [],
            accountsAll: init.accounts || [],
            cardsAll: init.cards || [],
            f: init.filters || { bank_id:null, account_id:null, card_id:null, status:'', year:null, month:null, q:'' },

            _deb: null,

            get accounts(){
                // se houver banco selecionado, filtra por banco
                if (this.f.bank_id) return this.accountsAll.filter(a => Number(a.bank_id) === Number(this.f.bank_id));
                return this.accountsAll;
            },
            get cards(){
                // filtra por banco e/ou conta
                let list = this.cardsAll;
                if (this.f.bank_id)    list = list.filter(c => Number(c.bank_id)    === Number(this.f.bank_id));
                if (this.f.account_id) list = list.filter(c => Number(c.account_id) === Number(this.f.account_id));
                return list;
            },

            // Dependências
            onBankChange(){
                this.f.account_id = null;
                this.f.card_id = null;
                this.go(); // aplica automático
            },
            onAccountChange(){
                this.f.card_id = null;
                this.go(); // aplica automático
            },

            // Navegação imediata (para selects)
            go(){
                const params = new URLSearchParams();
                if (this.f.bank_id)    params.set('bank_id', this.f.bank_id);
                if (this.f.account_id) params.set('account_id', this.f.account_id);
                if (this.f.card_id)    params.set('card_id', this.f.card_id);
                if (this.f.status)     params.set('status', this.f.status);
                if (this.f.year)       params.set('year', this.f.year);
                if (this.f.month)      params.set('month', this.f.month);
                if ((this.f.q || '').trim()) params.set('q', this.f.q.trim());

                const qs = params.toString();
                window.location = qs ? (this.baseUrl + '?' + qs) : this.baseUrl;
            },

            // Debounce (para campo de busca)
            scheduleGo(){
                clearTimeout(this._deb);
                this._deb = setTimeout(() => this.go(), 500);
            },

            clear(){
                window.location = this.baseUrl;
            },
            keyEnter(e){
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.go();
                }
            },
            cardLabel(c){
                return c.last4 ? `${c.name} •••• ${c.last4}` : c.name;
            }
        }
    }
</script>

<div class="space-y-6" x-data="invoicesPage(window.INV_INIT)">
    {{-- Título + resumo --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                Faturas de Cartão
            </h1>
            <p class="opacity-70 text-sm">Agrupadas por cartão + mês, com status e ações rápidas.</p>
        </div>

        {{-- Resumo --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 w-full md:w-auto">
            <div class="stats shadow bg-base-100">
                <div class="stat">
                    <div class="stat-title">Total</div>
                    <div class="stat-value text-base md:text-xl">{{ money_br($summary['total'] ?? 0) }}</div>
                </div>
            </div>
            <div class="stats shadow bg-success/10">
                <div class="stat">
                    <div class="stat-title">Pago</div>
                    <div class="stat-value text-base md:text-xl">{{ money_br($summary['paid'] ?? 0) }}</div>
                </div>
            </div>
            <div class="stats shadow bg-warning/10">
                <div class="stat">
                    <div class="stat-title">Em aberto</div>
                    <div class="stat-value text-base md:text-xl">{{ money_br($summary['open'] ?? 0) }}</div>
                </div>
            </div>
            <div class="stats shadow bg-error/10">
                <div class="stat">
                    <div class="stat-title">Vencidas</div>
                    <div class="stat-value text-base md:text-xl">{{ money_br($summary['overdue'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTROS (100% reativos, sem botão) --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-3">
                <div>
                    <label class="label"><span class="label-text">Banco</span></label>
                    <select class="select select-bordered w-full" x-model.number="f.bank_id" @change="onBankChange()">
                        <option :value="null">Todos</option>
                        <template x-for="b in banks" :key="b.id">
                            <option :value="b.id" x-text="b.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Conta pagamento</span></label>
                    <select class="select select-bordered w-full" x-model.number="f.account_id" @change="onAccountChange()">
                        <option :value="null">Todas</option>
                        <template x-for="a in accounts" :key="a.id">
                            <option :value="a.id" x-text="a.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Cartão</span></label>
                    <select class="select select-bordered w-full" x-model.number="f.card_id" @change="go()">
                        <option :value="null">Todos</option>
                        <template x-for="c in cards" :key="c.id">
                            <option :value="c.id" x-text="cardLabel(c)"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Status</span></label>
                    <select class="select select-bordered w-full" x-model="f.status" @change="go()">
                        <option value="">Todos</option>
                        <option value="open">Em aberto</option>
                        <option value="paid">Pago</option>
                        <option value="overdue">Vencido</option>
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Ano</span></label>
                    <select class="select select-bordered w-full" x-model.number="f.year" @change="go()">
                        <option :value="null">Todos</option>
                        @foreach ($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Mês</span></label>
                    <select class="select select-bordered w-full" x-model.number="f.month" @change="go()">
                        <option :value="null">Todos</option>
                        @foreach ($meses as $mNum => $mLab)
                            <option value="{{ $mNum }}">{{ $mLab }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Buscar</span></label>
                    <input type="text" class="input input-bordered w-full"
                           placeholder="Cartão, banco, conta..."
                           x-model.trim="f.q"
                           @input="scheduleGo()" @keydown="keyEnter($event)">
                    <div class="mt-2">
                        @if (collect($f)->filter(fn($v) => filled($v))->isNotEmpty())
                            <button class="btn btn-ghost btn-sm" @click.prevent="clear()">
                                <i class="fa-solid fa-rotate-left mr-1"></i> Limpar filtros
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- LISTA --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            @if ($invoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead class="sticky top-0 bg-base-100 z-10">
                            <tr>
                                <th>Cartão / Banco</th>
                                <th class="text-center">Mês ref.</th>
                                <th class="text-center">Vence em</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $inv)
                                @php
                                    $rowBg = match ($inv->status) {
                                        'paid' => 'bg-success/5',
                                        'overdue' => 'bg-error/5',
                                        default => 'bg-warning/5',
                                    };
                                    $monthLabel = \Carbon\Carbon::parse($inv->month_ref)->format('Y-m');
                                    $dueLabel = \Carbon\Carbon::parse($inv->due_on)->format('d/m/Y');
                                @endphp
                                <tr class="{{ $rowBg }}">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="avatar placeholder">
                                                <div class="bg-neutral text-neutral-content rounded-full w-9">
                                                    <span class="text-xs"><i class="fa-solid fa-credit-card"></i></span>
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-medium truncate">
                                                    {{ $inv->card_name }}
                                                    @if ($inv->card_last4)
                                                        <span class="opacity-60"> •••• {{ $inv->card_last4 }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs opacity-70 truncate">
                                                    {{ $inv->bank_name ?? '—' }} · {{ $inv->pay_account_name ?? '—' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge badge-outline">{{ $monthLabel }}</span>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge badge-ghost">
                                            <i class="fa-regular fa-calendar mr-1"></i>{{ $dueLabel }}
                                        </span>
                                    </td>

                                    <td class="text-right font-medium">{{ money_br($inv->total_amount) }}</td>

                                    <td class="text-center">{!! $statusBadge($inv->status) !!}</td>

                                    <td class="text-center">
                                        <div class="join">
                                            {{-- Ver transações --}}
                                            <a class="btn btn-sm btn-ghost join-item"
                                               href="{{ route('client.invoices.show', ['consultant' => $consultantId, 'invoice' => $inv->id]) }}">
                                                <i class="fa-regular fa-eye mr-1"></i>
                                                Ver
                                            </a>

                                            {{-- Marcar paga (não cria lançamento) --}}
                                            @if ($inv->status !== 'paid')
                                                <form method="POST"
                                                      action="{{ route('client.invoices.markPaid', ['consultant' => $consultantId, 'invoice' => $inv->id]) }}"
                                                      onsubmit="return confirm('Marcar a fatura {{ $monthLabel }} de {{ $inv->card_name }} como paga?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline join-item">
                                                        <i class="fa-solid fa-check mr-1"></i>
                                                        Marcar paga
                                                    </button>
                                                </form>

                                                {{-- Pagar fatura (cria saída e marca paga) --}}
                                                <details class="dropdown dropdown-end join-item">
                                                    <summary class="btn btn-sm btn-primary">
                                                        <i class="fa-solid fa-money-bill-wave mr-1"></i>
                                                        Pagar
                                                    </summary>
                                                    <div class="dropdown-content z-[1] card card-compact bg-base-100 shadow p-3 w-64 mt-2">
                                                        <form method="POST"
                                                              action="{{ route('client.invoices.pay', ['consultant' => $consultantId, 'invoice' => $inv->id]) }}"
                                                              onsubmit="return confirm('Confirmar pagamento da fatura {{ $monthLabel }} do cartão {{ $inv->card_name }}?')">
                                                            @csrf
                                                            <label class="label">
                                                                <span class="label-text text-xs">Data do pagamento (opcional)</span>
                                                            </label>
                                                            <input type="date" name="date" class="input input-bordered w-full mb-3">
                                                            <button type="submit" class="btn btn-primary w-full">
                                                                Confirmar pagamento
                                                            </button>
                                                        </form>
                                                        <div class="mt-2 text-xs opacity-70">
                                                            Será lançado um débito na conta de pagamento vinculada ao cartão e todas as compras do ciclo serão marcadas como pagas.
                                                        </div>
                                                    </div>
                                                </details>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- paginação --}}
                <div class="mt-4">
                    {{ $invoices->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <div class="text-5xl mb-3">🧾</div>
                    <h3 class="text-lg font-semibold">Nenhuma fatura encontrada</h3>
                    <p class="opacity-70">Ajuste os filtros acima para visualizar outros períodos, cartões ou contas.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
