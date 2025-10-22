@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto px-3 sm:px-4 py-5 space-y-5">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Nova recorrente</h1>
                <p class="text-xs sm:text-sm opacity-70">Cliente #{{ $clientId }} • Consultor #{{ $consultantId }}</p>
            </div>
            <a href="{{ route('client.recurrents.index', ['consultant' => $consultantId]) }}" class="btn btn-ghost">
                <i class="fa-solid fa-arrow-left me-2"></i> Voltar
            </a>
        </div>

        {{-- Erros --}}
        @if ($errors->any())
            <div class="alert alert-error">
                <i class="fa-regular fa-circle-xmark me-2"></i> Verifique os campos destacados.
            </div>
        @endif

        <form method="POST" action="{{ route('client.recurrents.store', ['consultant' => $consultantId]) }}"
            class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            @csrf

            {{-- Coluna principal --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Card: Identificação + Natureza --}}
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body gap-4">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="form-control">
                                <div class="label"><span class="label-text">Nome*</span></div>
                                <input type="text" name="name" value="{{ old('name') }}"
                                    class="input input-bordered w-full" required>
                                @error('name')
                                    <div class="label-text-alt text-error">{{ $message }}</div>
                                @enderror
                            </label>

                            <label class="form-control">
                                <div class="label"><span class="label-text">Descrição / Estabelecimento</span></div>
                                <input type="text" name="merchant" value="{{ old('merchant') }}"
                                    class="input input-bordered w-full">
                                @error('merchant')
                                    <div class="label-text-alt text-error">{{ $message }}</div>
                                @enderror
                            </label>
                        </div>

                        <div>
                            <div class="label"><span class="label-text">Natureza*</span></div>
                            <div class="join join-vertical sm:join-horizontal w-full">
                                <input class="join-item btn btn-outline" type="radio" name="nature" id="n_income"
                                    value="income">
                                <label class="join-item btn" for="n_income"><i
                                        class="fa-regular fa-circle-up me-2"></i>Receita</label>

                                <input class="join-item btn btn-outline" type="radio" name="nature" id="n_expense"
                                    value="expense">
                                <label class="join-item btn" for="n_expense"><i
                                        class="fa-regular fa-circle-down me-2"></i>Despesa</label>

                                <input class="join-item btn btn-outline" type="radio" name="nature" id="n_transfer"
                                    value="transfer">
                                <label class="join-item btn" for="n_transfer"><i
                                        class="fa-solid fa-right-left me-2"></i>Transferência</label>

                                <div class="dropdown join-item">
                                    <label tabindex="0" class="btn btn-outline w-full"><i
                                            class="fa-solid fa-chart-line me-2"></i> Investimentos</label>
                                    <ul tabindex="0"
                                        class="dropdown-content menu p-2 shadow bg-base-200 rounded-box w-56">
                                        <li><a data-inv="inv_aporte">Aporte</a></li>
                                        <li><a data-inv="inv_resgate">Resgate</a></li>
                                        <li><a data-inv="inv_rendimento">Rendimento</a></li>
                                    </ul>
                                </div>
                            </div>
                            <input type="hidden" name="type" id="type_hidden" value="{{ old('type') }}">
                            @error('type')
                                <div class="text-error text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Card: Método + Valor + Destino --}}
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body gap-4">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="form-control">
                                <div class="label"><span class="label-text">Método</span></div>
                                <select name="method" id="method" class="select select-bordered w-full">
                                    <option value="">—</option>
                                    @foreach (['pix' => 'PIX', 'debit' => 'Débito', 'credit_card' => 'Cartão de crédito', 'cash' => 'Dinheiro', 'transfer' => 'Transferência', 'boleto' => 'Boleto', 'adjustment' => 'Ajuste'] as $v => $l)
                                        <option value="{{ $v }}" @selected(old('method') === $v)>
                                            {{ $l }}</option>
                                    @endforeach
                                </select>
                                @error('method')
                                    <div class="label-text-alt text-error">{{ $message }}</div>
                                @enderror
                            </label>

                            <label class="form-control">
                                <div class="label"><span class="label-text">Valor padrão (opcional)</span></div>
                                <input type="number" step="0.01" inputmode="decimal" name="amount"
                                    value="{{ old('amount') }}" class="input input-bordered w-full"
                                    placeholder="ex.: 199.90">
                                <div class="label"><span class="label-text-alt opacity-70">Deixe vazio se variar.</span>
                                </div>
                                @error('amount')
                                    <div class="label-text-alt text-error">{{ $message }}</div>
                                @enderror
                            </label>
                        </div>

                        {{-- Destino normal --}}
                        <div id="section_target_normal">
                            <div class="label"><span class="label-text">Destino do lançamento</span></div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="card bg-base-200 hover:bg-base-300 transition cursor-pointer"
                                    id="cardPickAccount">
                                    <div class="card-body p-4">
                                        <div class="flex items-center gap-3">
                                            <i class="fa-regular fa-building-columns text-lg"></i>
                                            <div class="font-semibold">Conta</div>
                                        </div>
                                        <select name="account_id" id="account_id"
                                            class="select select-bordered w-full mt-3">
                                            <option value="">—</option>
                                            @foreach ($accounts as $acc)
                                                <option value="{{ $acc->id }}" @selected(old('account_id') == $acc->id)>
                                                    {{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('account_id')
                                            <div class="text-error text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="card bg-base-200 hover:bg-base-300 transition cursor-pointer"
                                    id="cardPickCard">
                                    <div class="card-body p-4">
                                        <div class="flex items-center gap-3">
                                            <i class="fa-regular fa-credit-card text-lg"></i>
                                            <div class="font-semibold">Cartão</div>
                                        </div>
                                        <select name="card_id" id="card_id" class="select select-bordered w-full mt-3">
                                            <option value="">—</option>
                                            @foreach ($cards as $c)
                                                <option value="{{ $c->id }}" @selected(old('card_id') == $c->id)>
                                                    {{ $c->name }} @if ($c->last4)
                                                        (••{{ $c->last4 }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('card_id')
                                            <div class="text-error text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="text-xs opacity-70 mt-2">Se método = “Cartão de crédito”, o destino será o Cartão.
                            </div>
                        </div>

                        {{-- Transferência --}}
                        <div id="section_transfer" class="hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <label class="form-control">
                                    <div class="label"><span class="label-text">Conta de origem</span></div>
                                    <select id="transfer_from" class="select select-bordered w-full">
                                        <option value="">—</option>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="form-control">
                                    <div class="label"><span class="label-text">Conta de destino</span></div>
                                    <select id="transfer_to" class="select select-bordered w-full">
                                        <option value="">—</option>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <div class="text-xs opacity-70 mt-1">Para contabilidade correta você pode criar 2 regras (saída
                                e entrada). Aqui salvaremos uma única regra (ex.: <code>transfer_out</code>) conforme a
                                conta principal escolhida no seu fluxo.</div>
                        </div>

                        {{-- Categorias (apenas receita/despesa) --}}
                        <div id="section_categories">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="form-control">
                                    <div class="label">
                                        <span class="label-text">Categoria</span>
                                        <span class="label-text-alt" id="badgeGroup"></span>
                                    </div>
                                    <select name="category_id" id="category_id" class="select select-bordered w-full">
                                        <option value="">—</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}" data-group="{{ (int) $cat->group_id }}"
                                                @selected(old('category_id') == $cat->id)>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="form-control">
                                    <div class="label"><span class="label-text">Subcategoria</span></div>
                                    <select name="subcategory_id" id="subcategory_id"
                                        class="select select-bordered w-full">
                                        <option value="">—</option>
                                    </select>
                                </label>
                            </div>
                        </div>

                        <label class="form-control">
                            <div class="label"><span class="label-text">Observações</span></div>
                            <textarea name="notes" rows="3" class="textarea textarea-bordered w-full"
                                placeholder="Ex.: Spotify família, 6 perfis...">{{ old('notes') }}</textarea>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Coluna direita: Recorrência --}}
            <div class="space-y-5">
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body gap-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="form-control">
                                <div class="label"><span class="label-text">Frequência*</span></div>
                                <select name="freq" id="freq" class="select select-bordered w-full" required>
                                    @foreach (['monthly' => 'Mensal', 'weekly' => 'Semanal', 'yearly' => 'Anual', 'custom' => 'Custom'] as $v => $l)
                                        <option value="{{ $v }}" @selected(old('freq') === $v)>
                                            {{ $l }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="form-control">
                                <div class="label"><span class="label-text">Intervalo*</span></div>
                                <input type="number" min="1" max="12" name="interval"
                                    value="{{ old('interval', 1) }}" class="input input-bordered w-full" required>
                                <div class="label"><span class="label-text-alt opacity-70">1 = todo mês; 2 =
                                        bimestral…</span></div>
                            </label>
                        </div>

                        <label class="form-control">
                            <div class="label"><span class="label-text">Dia do mês</span></div>
                            <input type="number" min="1" max="31" name="by_month_day"
                                value="{{ old('by_month_day') }}" class="input input-bordered w-full"
                                placeholder="ex.: 5">
                            <div class="label"><span class="label-text-alt opacity-70">Para mensal/anual
                                    (opcional).</span></div>
                        </label>

                        <label class="form-control">
                            <div class="label"><span class="label-text">Regra de deslocamento</span></div>
                            <select name="shift_rule" class="select select-bordered w-full">
                                @foreach (['exact' => 'Exata', 'previous_business_day' => 'Dia útil anterior', 'next_business_day' => 'Próximo dia útil'] as $v => $l)
                                    <option value="{{ $v }}" @selected(old('shift_rule', 'exact') === $v)>{{ $l }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="form-control">
                                <div class="label"><span class="label-text">Início*</span></div>
                                <input type="date" name="start_date" value="{{ old('start_date') }}"
                                    class="input input-bordered w-full" required>
                            </label>
                            <label class="form-control">
                                <div class="label"><span class="label-text">Término</span></div>
                                <input type="date" name="end_date" value="{{ old('end_date') }}"
                                    class="input input-bordered w-full">
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Sticky action (mobile) --}}
                <div class="lg:hidden fixed bottom-3 left-0 right-0 px-3">
                    <button type="submit" class="btn btn-primary btn-block shadow-lg">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Salvar
                    </button>
                </div>

                {{-- Action (desktop) --}}
                <div class="hidden lg:block">
                    <button type="submit" class="btn btn-primary w-full"><i class="fa-solid fa-floppy-disk me-2"></i>
                        Salvar</button>
                </div>
            </div>
        </form>
    </div>

    {{-- JS --}}
    <script>
        (function() {
            // Natureza → type
            const typeHidden = document.getElementById('type_hidden');
            const nIncome = document.getElementById('n_income');
            const nExpense = document.getElementById('n_expense');
            const nTransfer = document.getElementById('n_transfer');
            document.querySelectorAll('[data-inv]').forEach(el => {
                el.addEventListener('click', () => {
                    typeHidden.value = el.dataset.inv;
                    nIncome.checked = nExpense.checked = nTransfer.checked = false;
                    showInvest();
                });
            });
            nIncome.addEventListener('change', () => {
                if (nIncome.checked) {
                    typeHidden.value = 'income';
                    showIncomeExpense('income');
                }
            });
            nExpense.addEventListener('change', () => {
                if (nExpense.checked) {
                    typeHidden.value = 'expense';
                    showIncomeExpense('expense');
                }
            });
            nTransfer.addEventListener('change', () => {
                if (nTransfer.checked) {
                    typeHidden.value = 'transfer_out';
                    showTransfer();
                }
            });

            // Se vier old('type')
            const oldType = @json(old('type'));
            if (oldType) {
                if (oldType === 'income') {
                    nIncome.checked = true;
                    showIncomeExpense('income');
                } else if (oldType === 'expense') {
                    nExpense.checked = true;
                    showIncomeExpense('expense');
                } else if (oldType.startsWith('transfer')) {
                    nTransfer.checked = true;
                    showTransfer();
                } else if (oldType.startsWith('inv_')) {
                    showInvest();
                }
            } else {
                // default
                nExpense.checked = true;
                typeHidden.value = 'expense';
                showIncomeExpense('expense');
            }

            // Método força cartão
            const method = document.getElementById('method');
            const accCard = document.getElementById('cardPickAccount');
            const cardCard = document.getElementById('cardPickCard');
            const selAcc = document.getElementById('account_id');
            const selCard = document.getElementById('card_id');

            method.addEventListener('change', () => {
                if (method.value === 'credit_card') {
                    selAcc.value = '';
                    highlightDest('card');
                }
            });

            accCard.addEventListener('click', () => {
                if (method.value !== 'credit_card') highlightDest('account');
            });
            cardCard.addEventListener('click', () => highlightDest('card'));

            function highlightDest(which) {
                if (which === 'card') {
                    cardCard.classList.add('ring', 'ring-primary');
                    accCard.classList.remove('ring', 'ring-primary');
                } else {
                    accCard.classList.add('ring', 'ring-primary');
                    cardCard.classList.remove('ring', 'ring-primary');
                    selCard.value = '';
                }
            }

            // Se nenhum destacado, destaca conta por padrão
            highlightDest(selCard.value ? 'card' : 'account');

            // Seções
            const sectionNormal = document.getElementById('section_target_normal');
            const sectionTransf = document.getElementById('section_transfer');
            const sectionCats = document.getElementById('section_categories');
            const badgeGroup = document.getElementById('badgeGroup');

            function showIncomeExpense(kind) {
                sectionNormal.classList.remove('hidden');
                sectionTransf.classList.add('hidden');
                sectionCats.classList.remove('hidden');
                badgeGroup.textContent = kind === 'income' ? 'Grupo: Receitas' : 'Grupo: Despesas';
                filterCategoriesByGroup(kind);
                fillSubcategories(catSelect.value || '', true);
            }

            function showTransfer() {
                sectionNormal.classList.add('hidden');
                sectionTransf.classList.remove('hidden');
                sectionCats.classList.add('hidden');
            }

            function showInvest() {
                sectionNormal.classList.remove('hidden');
                sectionTransf.classList.add('hidden');
                sectionCats.classList.add('hidden'); // se quiser categorias para investimentos, basta exibir aqui
            }

            // Categoria → Subcategoria
            const catSelect = document.getElementById('category_id');
            const subSelect = document.getElementById('subcategory_id');

            // mapa subcategorias por categoria
            const SUBS_BY_CAT = @json(collect($subcategories)->groupBy('category_id')->map->values()->map(fn($items) => $items->map(fn($s) => ['id' => $s->id, 'name' => $s->name])));

            // snapshot de categorias (id, group, name)
            const CAT_OPTIONS = Array.from(catSelect.querySelectorAll('option')).map(o => ({
                id: o.value,
                group: o.dataset.group || '',
                name: o.textContent
            }));

            function filterCategoriesByGroup(kind) {
                const wantGroup = (kind === 'income') ? '4' : '5'; // ajuste se seus group_id forem outros
                const keep = catSelect.value;
                catSelect.innerHTML = '<option value="">—</option>';
                CAT_OPTIONS.forEach(opt => {
                    if (!opt.id) return;
                    if (String(opt.group) === String(wantGroup)) {
                        const el = document.createElement('option');
                        el.value = opt.id;
                        el.textContent = opt.name;
                        catSelect.appendChild(el);
                    }
                });
                const exists = Array.from(catSelect.options).some(o => o.value === keep);
                catSelect.value = exists ? keep : '';
            }

            function fillSubcategories(categoryId, keepOld = false) {
                const old = @json(old('subcategory_id'));
                subSelect.innerHTML = '<option value="">—</option>';
                if (!categoryId || !SUBS_BY_CAT[categoryId]) return;
                SUBS_BY_CAT[categoryId].forEach(s => {
                    const o = document.createElement('option');
                    o.value = s.id;
                    o.textContent = s.name;
                    subSelect.appendChild(o);
                });
                if (keepOld && old) {
                    subSelect.value = old;
                    if (subSelect.value !== String(old)) subSelect.value = '';
                }
            }

            catSelect.addEventListener('change', () => fillSubcategories(catSelect.value, false));
        })();
    </script>
@endsection
