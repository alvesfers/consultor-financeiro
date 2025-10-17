@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="alert alert-success my-3">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error my-3">
            <ul class="list-disc ms-5">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== DADOS GLOBAIS (antes do Alpine iniciar) ===== --}}
    <script>
        window.__pageData = {
            consultantId: @json($consultantId),
            accounts: @json($accountsData ?? []), // Alpine filtra os tipos "corrente/checking"
            accountsByBank: @json($accountsByBank ?? []),
            storageBase: @json(asset('storage')),
        };
    </script>

    <div class="max-w-[112rem] mx-auto px-4 py-4" x-data="accountsPage()">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-xl md:text-2xl font-semibold">Minhas contas</h1>
                <p class="opacity-70 text-xs md:text-sm">Gerencie contas, cartões e visualize o extrato combinado.</p>
            </div>
            <div class="flex items-center gap-2">
                <button class="btn btn-sm" @click.prevent="openNewAccount()">
                    <i class="fa-solid fa-university mr-2"></i> Nova conta
                </button>
                <button class="btn btn-primary btn-sm" @click.prevent="openNewCard()">
                    <i class="fa-solid fa-credit-card mr-2"></i> Adicionar cartão
                </button>
            </div>
        </div>

        {{-- ===== CARROSSEL DE CONTAS (apenas checking) ===== --}}
        <div class="relative mb-4">
            <button type="button"
                class="btn btn-circle btn-ghost btn-xs md:btn-sm absolute left-1 top-1/2 -translate-y-1/2 z-10 shadow"
                @click="scrollAccounts(-1)">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div id="accounts-wrapper" class="overflow-x-auto scroll-smooth">
                <div id="accounts-track" class="flex gap-3 md:gap-4 pr-6 snap-x snap-mandatory">
                    @php
                        $isChecking = function ($type) {
                            $t = strtolower($type ?? '');
                            return in_array($t, ['checking', 'corrente', 'pagamento', 'payment', 'current']);
                        };
                        $filtered = collect($accountsData ?? [])
                            ->filter(fn($a) => $isChecking($a['type'] ?? null))
                            ->values();
                    @endphp

                    @forelse($filtered as $i => $acc)
                        @php
                            $bank = $acc['bank'] ?? null;
                            $pri = $bank['color_primary'] ?? '#111827';
                            $bg = $bank['color_bg'] ?? '#F3F4F6';
                            $txt = $bank['color_text'] ?? '#111827';
                            $logo = !empty($bank['logo_svg'])
                                ? asset(
                                    str_starts_with($bank['logo_svg'], 'storage/')
                                        ? $bank['logo_svg']
                                        : 'storage/' . ltrim($bank['logo_svg'], '/'),
                                )
                                : null;
                            $bankName = $bank['name'] ?? 'Sem banco';
                            $accName = $acc['name'] ?? 'Conta';
                        @endphp

                        <button type="button"
                            class="shrink-0 w-[200px] md:w-[240px] rounded-xl p-3 border hover:shadow-sm transition-all text-left snap-start"
                            :class="selectedAccountIndex === {{ $i }} ?
                                'ring-2 ring-offset-1 ring-base-300 bg-base-100' : 'bg-base-200/50'"
                            style="background-color: {{ $bg }};" @click="selectAccount({{ $i }})">
                            <div class="flex items-center gap-2">
                                <div class="w-9 h-9 rounded-lg bg-white grid place-items-center overflow-hidden">
                                    @if ($logo)
                                        <img src="{{ $logo }}" alt="{{ $bankName }}"
                                            class="w-8 h-8 object-contain">
                                    @else
                                        <i class="fa-solid fa-building-columns text-lg"
                                            style="color: {{ $pri }}"></i>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-semibold truncate" style="color: {{ $txt }}">
                                        {{ $bankName }}</div>
                                    <div class="text-[11px] opacity-70 truncate">{{ $accName }}</div>
                                </div>
                            </div>

                            <div class="mt-2 text-[11px] opacity-70">Saldo</div>
                            <div class="text-base font-bold leading-5" style="color: {{ $pri }}">
                                R$ {{ number_format($acc['balance_total'] ?? 0, 2, ',', '.') }}
                            </div>
                        </button>
                    @empty
                        <div class="alert w-full">Você ainda não possui contas (checking).</div>
                    @endforelse
                </div>
            </div>

            <button type="button"
                class="btn btn-circle btn-ghost btn-xs md:btn-sm absolute right-1 top-1/2 -translate-y-1/2 z-10 shadow"
                @click="scrollAccounts(1)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        {{-- ===== LAYOUT PRINCIPAL: Detalhes da conta + Extrato ===== --}}
        <div x-show="accounts.length" class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="xl:col-span-1 space-y-4">
                {{-- Cabeçalho da conta --}}
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <template x-if="currentAccount().bank && currentAccount().bank.logo_svg">
                                    <img :src="assetPath(currentAccount().bank.logo_svg)" class="w-9 h-9 object-contain" />
                                </template>
                                <template x-if="!(currentAccount().bank && currentAccount().bank.logo_svg)">
                                    <div class="avatar placeholder">
                                        <div
                                            class="rounded w-9 h-9 bg-neutral text-neutral-content grid place-items-center">
                                            <i class="fa-solid fa-building-columns text-sm"></i>
                                        </div>
                                    </div>
                                </template>
                                <div class="min-w-0">
                                    <div class="font-semibold text-sm truncate"
                                        x-text="(currentAccount().bank?.name || 'Sem banco')"></div>
                                    <div class="text-xs opacity-70 truncate" x-text="currentAccount().name || 'Conta'">
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[11px] opacity-70">Saldo</div>
                                <div class="text-lg font-bold">R$ <span
                                        x-text="formatMoney(currentAccount().balance_total)"></span></div>
                            </div>
                        </div>
                        <div class="mt-2 text-[11px] opacity-70">
                            Moeda: <span x-text="currentAccount().currency || 'BRL'"></span>
                        </div>
                    </div>
                </div>

                {{-- Cartões da conta --}}
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-semibold text-sm">Cartões vinculados</h3>
                            <a href="#" class="btn btn-xs btn-primary"
                                @click.prevent="openNewCard(currentAccount().bank_id)">
                                <i class="fa-solid fa-plus mr-1"></i> Cartão
                            </a>
                        </div>

                        <template x-if="cardsOfCurrentAccount().length === 0">
                            <div class="alert alert-info text-sm py-2">Nenhum cartão para esta conta.</div>
                        </template>

                        <div class="grid grid-cols-1 sm:grid-cols-2 2xl:grid-cols-2 gap-3"
                            x-show="cardsOfCurrentAccount().length">
                            <template x-for="c in cardsOfCurrentAccount()" :key="c.id">
                                <div class="border rounded-lg p-3 hover:bg-base-200/50 transition">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="font-semibold text-sm truncate" x-text="c.name"></div>
                                            <div class="text-[11px] opacity-70">
                                                <span x-text="c.brand || 'Cartão'"></span>
                                                <template x-if="c.last4"> · final <span x-text="c.last4"></span></template>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-[11px] opacity-70">Limite</div>
                                            <div class="font-semibold text-sm">R$ <span
                                                    x-text="formatMoney(c.limit_amount)"></span></div>
                                        </div>
                                    </div>

                                    <div class="mt-2 text-[11px] opacity-70">
                                        Fechamento: <span x-text="c.close_day || '-'"></span> ·
                                        Venc: <span x-text="c.due_day || '-'"></span>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between">
                                        <button class="btn btn-xs btn-primary"
                                            @click="toggleCard(c.id, c)">Detalhes</button>
                                        <span class="text-xs opacity-60">•••</span>
                                    </div>

                                    {{-- Detalhes inline --}}
                                    <div class="mt-3 rounded-lg border bg-base-100" x-show="expanded[c.id]" x-transition>
                                        <template x-if="expanded[c.id]">
                                            <div class="p-3">
                                                <form class="grid grid-cols-2 gap-3" method="POST"
                                                    :action="updateCardUrl(c.id)">
                                                    @csrf
                                                    @method('PATCH')

                                                    <label class="form-control">
                                                        <span class="label-text text-xs">Final (4)</span>
                                                        <input name="last4" maxlength="4"
                                                            x-model="expanded[c.id].card.last4"
                                                            class="input input-bordered input-sm" placeholder="1234">
                                                    </label>

                                                    <label class="form-control">
                                                        <span class="label-text text-xs">Limite (R$)</span>
                                                        <input name="limit_amount" type="number" step="0.01"
                                                            min="0" x-model="expanded[c.id].card.limit_amount"
                                                            class="input input-bordered input-sm" placeholder="0,00">
                                                    </label>

                                                    <div class="col-span-2 grid grid-cols-2 gap-3 text-[11px]">
                                                        <div>
                                                            <span class="opacity-70">Fechamento</span>
                                                            <div x-text="expanded[c.id].card.close_day || '-'"></div>
                                                        </div>
                                                        <div>
                                                            <span class="opacity-70">Vencimento</span>
                                                            <div x-text="expanded[c.id].card.due_day || '-'"></div>
                                                        </div>
                                                    </div>

                                                    <div class="col-span-2 flex items-center justify-end gap-2">
                                                        <button type="button" class="btn btn-ghost btn-xs"
                                                            @click="toggleCard(c.id, null)">Fechar</button>
                                                        <button class="btn btn-primary btn-xs"
                                                            type="submit">Salvar</button>
                                                    </div>
                                                </form>

                                                <div class="divider my-3">Compras recentes</div>

                                                <template x-if="!(expanded[c.id]?.purchases?.length)">
                                                    <div class="alert alert-info text-sm py-2">Sem compras recentes.</div>
                                                </template>

                                                <div class="overflow-x-auto max-h-56"
                                                    x-show="expanded[c.id]?.purchases?.length">
                                                    <table class="table table-xs">
                                                        <thead class="sticky top-0 bg-base-100 z-10">
                                                            <tr>
                                                                <th>Data</th>
                                                                <th>Descrição</th>
                                                                <th class="text-right">Valor</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <template x-for="tx in expanded[c.id].purchases"
                                                                :key="tx.id">
                                                                <tr>
                                                                    <td class="whitespace-nowrap"
                                                                        x-text="formatDate(tx.created_at)"></td>
                                                                    <td class="truncate max-w-[14rem]"
                                                                        x-text="tx.description ?? '—'"></td>
                                                                    <td class="text-right"
                                                                        :class="Number(tx.amount) < 0 ? 'text-error' :
                                                                            'text-success'">
                                                                        R$ <span x-text="formatMoney(tx.amount)"></span>
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Extrato combinado (ocupa 2/3) --}}
            <div class="xl:col-span-2 space-y-4">
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body p-4">
                        {{-- Filtros slim --}}
                        <div class="flex flex-wrap items-end gap-2">
                            <div>
                                <label class="label text-[11px] pt-0 pb-1">Início</label>
                                <input type="date" class="input input-bordered input-sm" x-model="filters.start" />
                            </div>
                            <div>
                                <label class="label text-[11px] pt-0 pb-1">Fim</label>
                                <input type="date" class="input input-bordered input-sm" x-model="filters.end" />
                            </div>
                            <div>
                                <label class="label text-[11px] pt-0 pb-1">Tipo</label>
                                <select class="select select-bordered select-sm" x-model="filters.type">
                                    <option value="all">Todos</option>
                                    <option value="in">Entradas</option>
                                    <option value="out">Saídas</option>
                                </select>
                            </div>
                            <div>
                                <label class="label text-[11px] pt-0 pb-1">Origem</label>
                                <select class="select select-bordered select-sm" x-model="filters.source">
                                    <option value="all">Contas e Cartões</option>
                                    <option value="accounts">Só Contas</option>
                                    <option value="cards">Só Cartões</option>
                                </select>
                            </div>
                            <button class="btn btn-xs btn-outline ml-auto" @click="resetFilters()">Limpar</button>
                        </div>

                        {{-- Tabela compacta com header sticky --}}
                        <div class="overflow-auto mt-3 max-h-[520px]">
                            <table class="table table-sm">
                                <thead class="sticky top-0 bg-base-100 z-10">
                                    <tr>
                                        <th class="w-28">Data</th>
                                        <th>Descrição</th>
                                        <th class="w-28">Origem</th>
                                        <th class="text-right w-36">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="filteredTransactions().length === 0">
                                        <tr>
                                            <td colspan="4" class="opacity-70">Sem transações.</td>
                                        </tr>
                                    </template>

                                    <template x-for="tx in filteredTransactions()" :key="tx.id + '-' + (tx._source || '')">
                                        <tr>
                                            <td class="whitespace-nowrap" x-text="formatDate(tx.created_at)"></td>
                                            <td class="truncate max-w-[34rem]" x-text="tx.description || '—'"></td>
                                            <td class="whitespace-nowrap text-xs" x-text="tx._source || '—'"></td>
                                            <td class="text-right"
                                                :class="Number(tx.amount) < 0 ? 'text-error' : 'text-success'">
                                                R$ <span x-text="formatMoney(tx.amount)"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2 text-right">
                            <a href="{{ route('client.transactions.index', ['consultant' => $consultantId]) }}"
                                class="btn btn-ghost btn-xs">Ver tudo</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== MODAIS ===== --}}
        <dialog id="newAccountModal" class="modal">
            <div class="modal-box max-w-lg">
                <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-3">Nova conta</h3>

                <form method="POST" action="{{ route('client.accounts.store', ['consultant' => $consultantId]) }}">
                    @csrf
                    <div class="grid gap-3">
                        <label class="form-control">
                            <span class="label-text">Nome da conta</span>
                            <input name="name" class="input input-bordered" placeholder="Minha conta" required>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="form-control">
                                <span class="label-text">Tipo</span>
                                <input name="type" class="input input-bordered" placeholder="Corrente / Pagamento">
                            </label>
                            <label class="form-control">
                                <span class="label-text">Moeda</span>
                                <input name="currency" class="input input-bordered" value="BRL" maxlength="5">
                            </label>
                        </div>
                        <label class="form-control">
                            <span class="label-text">Saldo inicial (R$)</span>
                            <input name="opening_balance" type="number" step="0.01" class="input input-bordered"
                                placeholder="0,00">
                        </label>
                        <label class="form-control">
                            <span class="label-text">Banco</span>
                            <select name="bank_id" class="select select-bordered">
                                <option value="">— Sem banco —</option>
                                @foreach ($banks ?? [] as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button class="btn btn-primary" type="submit">Salvar</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>

        <dialog id="newCardModal" class="modal">
            <div class="modal-box max-w-2xl">
                <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-3">Adicionar cartão</h3>

                <form method="POST" action="{{ route('client.cards.store', ['consultant' => $consultantId]) }}">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label class="form-control">
                            <span class="label-text">Nome do cartão</span>
                            <input name="name" class="input input-bordered" placeholder="Meu Visa" required>
                        </label>
                        <label class="form-control">
                            <span class="label-text">Bandeira</span>
                            <input name="brand" class="input input-bordered" placeholder="Visa / Master / Elo">
                        </label>
                        <label class="form-control">
                            <span class="label-text">Final (4 dígitos)</span>
                            <input name="last4" maxlength="4" class="input input-bordered" placeholder="1234">
                        </label>
                        <label class="form-control">
                            <span class="label-text">Limite (R$)</span>
                            <input name="limit_amount" type="number" step="0.01" min="0"
                                class="input input-bordered" placeholder="0,00">
                        </label>
                        <label class="form-control">
                            <span class="label-text">Fechamento (dia)</span>
                            <input name="close_day" type="number" min="1" max="31"
                                class="input input-bordered">
                        </label>
                        <label class="form-control">
                            <span class="label-text">Vencimento (dia)</span>
                            <input name="due_day" type="number" min="1" max="31"
                                class="input input-bordered">
                        </label>

                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label class="form-control">
                                <span class="label-text">Banco</span>
                                <select name="bank_id" class="select select-bordered" x-model="cardModal.bank_id"
                                    @change="filterAccountsForBank()">
                                    <option value="">— Selecionar —</option>
                                    @foreach ($banks ?? [] as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="form-control">
                                <span class="label-text">Conta de pagamento</span>
                                <select name="payment_account_id" class="select select-bordered" x-ref="accountSelect">
                                    <option value="">— Selecione o banco —</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button type="button" class="btn btn-ghost" @click="closeNewCard()">Cancelar</button>
                        <button class="btn btn-primary" type="submit">Salvar</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>
    </div>
@endsection

@push('head')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('accountsPage', () => ({
                // ===== STATE =====
                consultantId: window.__pageData?.consultantId ?? null,
                accounts: (window.__pageData?.accounts ?? []).filter(a => {
                    const t = String(a?.type ?? '').toLowerCase();
                    return ['checking', 'corrente', 'pagamento', 'payment', 'current'].includes(
                        t);
                }),
                accountsByBank: window.__pageData?.accountsByBank ?? {},
                selectedAccountIndex: 0,
                expanded: {},
                filters: {
                    start: '',
                    end: '',
                    type: 'all',
                    source: 'all'
                },
                cardModal: {
                    bank_id: ''
                },

                // ===== HELPERS =====
                assetPath(p) {
                    if (!p) return '';
                    p = p.replace(/^storage\//, '').replace(/^\/+/, '');
                    const base = window.__pageData?.storageBase ?? '';
                    return base ? `${base}/${p}` : p;
                },
                currentAccount() {
                    if (!this.accounts?.length) return null;
                    return this.accounts[this.selectedAccountIndex] ?? this.accounts[0];
                },
                formatMoney(v) {
                    const n = Number(v ?? 0);
                    return n.toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },
                formatDate(s) {
                    if (!s) return '—';
                    const d = new Date(s);
                    return isNaN(d) ? s : d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString(
                        'pt-BR', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                },
                updateCardUrl(id) {
                    return `/${encodeURIComponent(this.consultantId)}/client/cards/${id}`;
                },

                // ===== CARROSSEL =====
                scrollAccounts(dir) {
                    const wrapper = document.getElementById('accounts-wrapper');
                    if (!wrapper) return;
                    wrapper.scrollBy({
                        left: Math.round(wrapper.clientWidth * 0.85) * dir,
                        behavior: 'smooth'
                    });
                },
                selectAccount(i) {
                    this.selectedAccountIndex = i;
                    this.resetFilters();
                    this.expanded = {};
                },

                // ===== CARTÕES =====
                cardsOfCurrentAccount() {
                    const acc = this.currentAccount();
                    if (!acc) return [];
                    const own = (acc.cards || []).filter(c => String(c.payment_account_id ?? '') ===
                        String(acc.id));
                    if (own.length) return own;
                    if (acc.bank_id) return (acc.cards || []).filter(c => String(c.bank_id ?? '') ===
                        String(acc.bank_id));
                    return acc.cards || [];
                },
                toggleCard(id, cardObj) {
                    if (this.expanded[id]) {
                        delete this.expanded[id];
                        this.expanded = {
                            ...this.expanded
                        };
                        return;
                    }
                    const card = cardObj || (this.cardsOfCurrentAccount().find(x => x.id === id) ?? {});
                    this.expanded[id] = {
                        card: {
                            id: card.id,
                            name: card.name,
                            brand: card.brand,
                            last4: card.last4 || '',
                            limit_amount: card.limit_amount ?? 0,
                            close_day: card.close_day,
                            due_day: card.due_day,
                            payment_account_id: card.payment_account_id,
                        },
                        purchases: (card.transactions || []).slice()
                    };
                    this.expanded = {
                        ...this.expanded
                    };
                },

                // ===== EXTRATO =====
                resetFilters() {
                    this.filters = {
                        start: '',
                        end: '',
                        type: 'all',
                        source: 'all'
                    };
                },
                filteredTransactions() {
                    const acc = this.currentAccount();
                    if (!acc) return [];
                    let txs = (acc.recent_transactions || []).slice();

                    if (this.filters.source !== 'all') {
                        txs = txs.filter(tx => {
                            const src = (tx._source || '').toLowerCase();
                            if (this.filters.source === 'accounts') return src.startsWith(
                                'conta ');
                            if (this.filters.source === 'cards') return src.startsWith(
                                'cartão ');
                            return true;
                        });
                    }
                    if (this.filters.type !== 'all') {
                        txs = txs.filter(tx => this.filters.type === 'in' ?
                            Number(tx.amount || 0) >= 0 :
                            Number(tx.amount || 0) < 0);
                    }
                    if (this.filters.start) {
                        const d = new Date(this.filters.start + 'T00:00:00');
                        txs = txs.filter(tx => new Date(tx.created_at) >= d);
                    }
                    if (this.filters.end) {
                        const d = new Date(this.filters.end + 'T23:59:59');
                        txs = txs.filter(tx => new Date(tx.created_at) <= d);
                    }
                    return txs;
                },

                // ===== MODAIS =====
                openNewAccount() {
                    document.getElementById('newAccountModal')?.showModal();
                },
                openNewCard(bankId = '') {
                    this.cardModal.bank_id = bankId || (this.currentAccount()?.bank_id ?? '');
                    document.getElementById('newCardModal')?.showModal();
                    this.$nextTick(() => this.filterAccountsForBank());
                },
                closeNewCard() {
                    document.getElementById('newCardModal')?.close();
                    this.cardModal.bank_id = '';
                    const sel = this.$refs.accountSelect;
                    if (sel) sel.innerHTML = `<option value="">— Selecione o banco —</option>`;
                },
                filterAccountsForBank() {
                    const bankId = this.cardModal.bank_id ? Number(this.cardModal.bank_id) : null;
                    const options = (bankId != null && this.accountsByBank[bankId]) ? this
                        .accountsByBank[bankId] : [];
                    const sel = this.$refs.accountSelect;
                    if (!sel) return;
                    sel.innerHTML = '';
                    if (!options.length) {
                        sel.innerHTML = `<option value="">— Sem contas para este banco —</option>`;
                        return;
                    }
                    options.forEach(acc => {
                        const opt = document.createElement('option');
                        opt.value = acc.id;
                        opt.textContent = acc.name;
                        sel.appendChild(opt);
                    });
                },
            }));
        });
    </script>
@endpush
