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

    <div class="max-w-6xl mx-auto px-4 py-6" x-data="accountsPage()">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Minhas contas</h1>
            <a href="#" class="btn btn-primary btn-sm" @click.prevent="openNewAccount()">Nova conta</a>
        </div>

        {{-- ====== CARROSSEL DE CONTAS (apenas checking) ====== --}}
        <div class="relative">
            <button type="button" class="btn btn-ghost btn-sm absolute -left-2 top-1/2 -translate-y-1/2 z-10"
                @click="scrollAccounts(-1)">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div id="accounts-wrapper" class="overflow-x-auto">
                <div id="accounts-track" class="flex gap-4 scroll-smooth pr-6">
                    @php
                        // função pra saber se a conta é "checking"
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
                            class="shrink-0 w-[220px] md:w-[260px] rounded-2xl p-4 border hover:shadow transition-all duration-200 text-left"
                            :class="selectedAccountIndex === {{ $i }} ?
                                'ring-2 ring-offset-2 ring-base-300 bg-base-100' : 'bg-base-200/40'"
                            style="background-color: {{ $bg }};" @click="selectAccount({{ $i }})">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-white grid place-items-center overflow-hidden">
                                    @if ($logo)
                                        <img src="{{ $logo }}" alt="{{ $bankName }}"
                                            class="w-9 h-9 object-contain">
                                    @else
                                        <i class="fa-solid fa-building-columns text-xl"
                                            style="color: {{ $pri }}"></i>
                                    @endif
                                </div>

                                <div>
                                    <div class="text-sm font-semibold" style="color: {{ $txt }}">
                                        {{ $bankName }}</div>
                                    <div class="text-xs opacity-70">{{ $accName }}</div>
                                </div>
                            </div>

                            <div class="mt-3 text-xs opacity-70">Saldo</div>
                            <div class="text-lg font-bold" style="color: {{ $pri }}">
                                R$ {{ number_format($acc['balance_total'] ?? 0, 2, ',', '.') }}
                            </div>
                        </button>
                    @empty
                        <div class="alert w-full">Você ainda não possui contas (checking).</div>
                    @endforelse
                </div>
            </div>

            <button type="button" class="btn btn-ghost btn-sm absolute -right-2 top-1/2 -translate-y-1/2 z-10"
                @click="scrollAccounts(1)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        {{-- ====== PAINEL DA CONTA SELECIONADA ====== --}}
        <div class="mt-6" x-show="accounts.length">
            <template x-if="currentAccount()">
                <div class="space-y-6">
                    {{-- Cabeçalho da conta --}}
                    <div class="card bg-base-100 shadow">
                        <div class="card-body p-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <template x-if="currentAccount().bank && currentAccount().bank.logo_svg">
                                        <img :src="assetPath(currentAccount().bank.logo_svg)"
                                            class="w-10 h-10 object-contain" />
                                    </template>
                                    <template x-if="!(currentAccount().bank && currentAccount().bank.logo_svg)">
                                        <div class="avatar placeholder">
                                            <div class="rounded-full w-10 bg-neutral text-neutral-content">
                                                <i class="fa-solid fa-building-columns text-base"></i>
                                            </div>
                                        </div>
                                    </template>
                                    <div>
                                        <h2 class="card-title"
                                            x-text="(currentAccount().bank?.name || 'Sem banco') + ' · ' + (currentAccount().name || 'Conta')">
                                        </h2>
                                        <div class="text-xs opacity-70">
                                            <span x-text="'Moeda: ' + (currentAccount().currency || 'BRL')"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div class="text-xs opacity-70">Saldo</div>
                                    <div class="text-xl font-bold">
                                        R$ <span x-text="formatMoney(currentAccount().balance_total)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Cartões da conta --}}
                    <div class="card bg-base-100 shadow">
                        <div class="card-body p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-semibold">Cartões desta conta</h3>
                                <a href="#" class="btn btn-sm btn-primary"
                                    @click.prevent="openNewCard(currentAccount().bank_id)">
                                    <i class="fa-solid fa-plus me-2"></i>Adicionar cartão
                                </a>
                            </div>

                            <template x-if="cardsOfCurrentAccount().length === 0">
                                <div class="alert alert-info">Nenhum cartão vinculado a esta conta.</div>
                            </template>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                                x-show="cardsOfCurrentAccount().length">
                                <template x-for="c in cardsOfCurrentAccount()" :key="c.id">
                                    <div class="border rounded-xl p-4 hover:bg-base-200/50 transition">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <div class="font-semibold" x-text="c.name"></div>
                                                <div class="text-xs opacity-70">
                                                    <span x-text="c.brand || 'Cartão'"></span>
                                                    <template x-if="c.last4"> · final <span
                                                            x-text="c.last4"></span></template>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm opacity-70">Limite</div>
                                                <div class="font-semibold">R$ <span
                                                        x-text="formatMoney(c.limit_amount)"></span></div>
                                            </div>
                                        </div>

                                        <div class="mt-3 text-xs opacity-70">
                                            Fechamento: dia <span x-text="c.close_day || '-'"></span> ·
                                            Vencimento: dia <span x-text="c.due_day || '-'"></span>
                                        </div>

                                        <div class="mt-4 flex items-center justify-between">
                                            <button class="btn btn-sm btn-primary"
                                                @click="toggleCard(c.id, c)">Detalhes</button>
                                            <span class="text-xs opacity-70">•••</span>
                                        </div>

                                        {{-- Detalhes inline --}}
                                        <div class="mt-4 rounded-xl border bg-base-100" x-show="expanded[c.id]"
                                            x-transition>
                                            <template x-if="expanded[c.id]">
                                                <div class="p-4">
                                                    <form class="grid grid-cols-1 md:grid-cols-3 gap-4" method="POST"
                                                        :action="updateCardUrl(c.id)">
                                                        @csrf
                                                        @method('PATCH')

                                                        <label class="form-control">
                                                            <span class="label-text">Final (4 dígitos)</span>
                                                            <input name="last4" maxlength="4"
                                                                x-model="expanded[c.id].card.last4"
                                                                class="input input-bordered" placeholder="1234">
                                                        </label>

                                                        <label class="form-control md:col-span-2">
                                                            <span class="label-text">Limite do cartão (R$)</span>
                                                            <input name="limit_amount" type="number" step="0.01"
                                                                min="0" x-model="expanded[c.id].card.limit_amount"
                                                                class="input input-bordered" placeholder="0,00">
                                                        </label>

                                                        <div class="md:col-span-3 grid grid-cols-2 gap-4 text-sm">
                                                            <div>
                                                                <span class="opacity-70">Fechamento</span>
                                                                <div x-text="expanded[c.id].card.close_day || '-'"></div>
                                                            </div>
                                                            <div>
                                                                <span class="opacity-70">Vencimento</span>
                                                                <div x-text="expanded[c.id].card.due_day || '-'"></div>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="md:col-span-3 flex items-center justify-end gap-2 mt-2">
                                                            <button type="button" class="btn btn-ghost"
                                                                @click="toggleCard(c.id, null)">Fechar</button>
                                                            <button class="btn btn-primary" type="submit">Salvar</button>
                                                        </div>
                                                    </form>

                                                    <div class="divider my-4">Compras recentes</div>

                                                    <template x-if="!(expanded[c.id]?.purchases?.length)">
                                                        <div class="alert alert-info">Sem compras recentes.</div>
                                                    </template>

                                                    <div class="overflow-x-auto"
                                                        x-show="expanded[c.id]?.purchases?.length">
                                                        <table class="table table-sm">
                                                            <thead>
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
                                                                        <td x-text="tx.description ?? '—'"></td>
                                                                        <td class="text-right"
                                                                            :class="Number(tx.amount) < 0 ? 'text-error' :
                                                                                'text-success'">
                                                                            R$ <span
                                                                                x-text="formatMoney(tx.amount)"></span>
                                                                        </td>
                                                                    </tr>
                                                                </template>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        {{-- /Detalhes inline --}}
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Filtros do extrato combinado da conta --}}
                    <div class="card bg-base-100 shadow">
                        <div class="card-body p-5">
                            <div class="flex flex-wrap items-end gap-3">
                                <div>
                                    <label class="label text-xs">Período (início)</label>
                                    <input type="date" class="input input-bordered input-sm"
                                        x-model="filters.start" />
                                </div>
                                <div>
                                    <label class="label text-xs">Período (fim)</label>
                                    <input type="date" class="input input-bordered input-sm" x-model="filters.end" />
                                </div>
                                <div>
                                    <label class="label text-xs">Tipo</label>
                                    <select class="select select-bordered select-sm" x-model="filters.type">
                                        <option value="all">Todos</option>
                                        <option value="in">Entradas</option>
                                        <option value="out">Saídas</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label text-xs">Origem</label>
                                    <select class="select select-bordered select-sm" x-model="filters.source">
                                        <option value="all">Contas e Cartões</option>
                                        <option value="accounts">Apenas Contas</option>
                                        <option value="cards">Apenas Cartões</option>
                                    </select>
                                </div>
                                <button class="btn btn-sm btn-outline" @click="resetFilters()">Limpar</button>
                            </div>

                            {{-- Extrato combinado --}}
                            <div class="overflow-x-auto mt-4">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Descrição</th>
                                            <th>Origem</th>
                                            <th class="text-right">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="tx in filteredTransactions()"
                                            :key="tx.id + '-' + (tx._source || '')">
                                            <tr>
                                                <td class="whitespace-nowrap" x-text="formatDate(tx.created_at)"></td>
                                                <td x-text="tx.description || '—'"></td>
                                                <td class="whitespace-nowrap" x-text="tx._source || '—'"></td>
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
            </template>
        </div>

        {{-- ⚠️ MOVER OS MODAIS PARA DENTRO DO X-DATA (para ter acesso a cardModal, etc.) --}}
        {{-- MODAL: Nova Conta --}}
        <dialog id="newAccountModal" class="modal">
            <div class="modal-box max-w-lg">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>

                <h3 class="font-bold text-lg mb-4">Nova conta</h3>

                <form method="POST" action="{{ route('client.accounts.store', ['consultant' => $consultantId]) }}">
                    @csrf
                    <div class="grid gap-4">
                        <label class="form-control">
                            <span class="label-text">Nome da conta</span>
                            <input name="name" class="input input-bordered" placeholder="Minha conta" required>
                        </label>

                        <div class="grid grid-cols-2 gap-4">
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

                    <div class="mt-6 flex items-center justify-end gap-2">
                        <button class="btn btn-primary" type="submit">Salvar</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>

        {{-- MODAL: Adicionar Cartão --}}
        <dialog id="newCardModal" class="modal">
            <div class="modal-box max-w-2xl">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>

                <h3 class="font-bold text-lg mb-4">Adicionar cartão</h3>

                <form method="POST" action="{{ route('client.cards.store', ['consultant' => $consultantId]) }}">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                    {{-- opções via Alpine (accountsByBank) --}}
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2">
                        <button type="button" class="btn btn-ghost" @click="closeNewCard()">Cancelar</button>
                        <button class="btn btn-primary" type="submit">Salvar</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>
        {{-- FIM dos modais dentro do x-data --}}
    </div>
@endsection

@push('head')
    {{-- Dados precisam existir antes do Alpine iniciar --}}
    <script>
        window.__pageData = {
            consultantId: @json($consultantId),
            // Passa todas as contas, mas o Alpine vai filtrar para mostrar só checking no carrossel:
            accounts: @json($accountsData ?? []),
            accountsByBank: @json($accountsByBank ?? []),
            storageBase: @json(asset('storage')),
        };
    </script>

    {{-- Registro do componente (antes do Alpine.start via alpine:init) --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('accountsPage', () => ({
                // ===== STATE =====
                consultantId: window.__pageData?.consultantId ?? null,
                // Filtra para exibir no carrossel apenas tipos "checking"/"corrente"/"pagamento"
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
                    return n.toFixed(2).replace('.', ',');
                },
                formatDate(s) {
                    if (!s) return '—';
                    const d = new Date(s);
                    return isNaN(d) ? s : d.toLocaleString('pt-BR');
                },
                updateCardUrl(id) {
                    return `/${encodeURIComponent(this.consultantId)}/client/cards/${id}`;
                },

                // ===== CARROSSEL =====
                scrollAccounts(dir) {
                    const wrapper = document.getElementById('accounts-wrapper');
                    if (!wrapper) return;
                    const delta = Math.round(wrapper.clientWidth * 0.85) * dir;
                    wrapper.scrollBy({
                        left: delta,
                        behavior: 'smooth'
                    });
                },
                selectAccount(i) {
                    this.selectedAccountIndex = i;
                    this.resetFilters();
                    this.expanded = {};
                },

                // ===== CARTÕES DA CONTA =====
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

                // ===== DETALHES INLINE =====
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
                        txs = txs.filter(tx => this.filters.type === 'in' ? Number(tx.amount || 0) >=
                            0 : Number(tx.amount || 0) < 0);
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
