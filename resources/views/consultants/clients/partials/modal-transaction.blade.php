<dialog id="txModal" class="modal">
    <div class="modal-box w-full max-w-md p-0" x-data="txWizard()" x-init="init()"
        data-sub-by-cat='@json($subcategoriesByCategory)'>

        <!-- Header -->
        <div class="p-4 border-b">
            <h3 class="font-bold text-lg">
                <i class="fa-solid fa-plus mr-2"></i> Nova transação
            </h3>
            <div class="steps steps-horizontal mt-3">
                <button class="step" :class="{ 'step-primary': step >= 1 }">Tipo</button>
                <button class="step" :class="{ 'step-primary': step >= 2 }">Destino</button>
                <button class="step" :class="{ 'step-primary': step >= 3 }">Detalhes</button>
            </div>
        </div>

        <!-- Body -->
        <form method="POST" action="{{ route('client.transactions.store', ['consultant' => $consultantId]) }}"
            @submit="handleSubmit" class="p-4 space-y-4">
            @csrf

            <!-- ====== ESPELHOS HIDDEN (SEMPRE NO DOM) ====== -->
            <input type="hidden" name="kind" :value="form.kind">
            <input type="hidden" name="type" :value="form.kind"> <!-- o back usa 'type' -->
            <input type="hidden" name="mode" :value="form.mode">
            <input type="hidden" name="date" :value="form.date">

            <input type="hidden" name="account_id"
                :value="form.kind !== 'transfer' && form.mode==='account' ? form.account_id : ''">
            <input type="hidden" name="card_id"
                :value="form.kind !== 'transfer' && form.mode==='card' ? form.card_id : ''">

            <input type="hidden" name="from_account_id" :value="form.kind === 'transfer' ? form.from_account_id : ''">
            <input type="hidden" name="to_account_id" :value="form.kind === 'transfer' ? form.to_account_id : ''">

            <input type="hidden" name="category_id" :value="form.kind !== 'transfer' ? form.category_id : ''">
            <input type="hidden" name="subcategory_id" :value="form.kind !== 'transfer' ? form.subcategory_id : ''">

            <!-- NOVOS: flags de parcelado -->
            <input type="hidden" name="is_installment"
                :value="form.mode === 'card' && form.kind==='expense' && form.is_installment ? 1 : 0">
            <input type="hidden" name="installments"
                :value="form.mode === 'card' && form.kind==='expense' ? (form.is_installment ? form.installments : 1) : ''">
            <input type="hidden" name="first_invoice_month"
                :value="form.mode === 'card' && form.kind==='expense' && form.is_installment ? form.first_invoice_month : ''">
            <!-- ====== FIM ESPELHOS ====== -->

            <!-- STEP 1: Tipo -->
            <template x-if="step===1">
                <div class="space-y-3">
                    <label class="label"><span class="label-text">Tipo</span></label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" class="btn" :class="btnKind('expense')"
                            @click="setKind('expense')">Gasto</button>
                        <button type="button" class="btn" :class="btnKind('income')"
                            @click="setKind('income')">Ganho</button>
                        <button type="button" class="btn" :class="btnKind('transfer')"
                            @click="setKind('transfer')">Transf.</button>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text">Data</span></label>
                        <!-- SEM name/required: o hidden 'date' envia -->
                        <input type="datetime-local" class="input input-bordered" x-model="form.date"
                            value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                </div>
            </template>

            <!-- STEP 2: Destino -->
            <template x-if="step===2">
                <div class="space-y-3">
                    <label class="label"><span class="label-text">Lançar em</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" class="btn" :class="btnMode('account')"
                            @click="setMode('account')">Conta</button>
                        <button type="button" class="btn"
                            :class="btnMode('card') + (form.kind==='transfer' ? ' btn-disabled' : '')"
                            :disabled="form.kind === 'transfer'"
                            @click="if(form.kind!=='transfer') setMode('card')">Cartão</button>
                    </div>

                    <!-- Conta única (gasto/ganho) -->
                    <template x-if="form.mode==='account' && form.kind!=='transfer'">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Conta</span></label>
                            <!-- SEM name/required: hidden 'account_id' envia -->
                            <select class="select select-bordered" x-model="form.account_id">
                                <option value="" disabled selected>Selecione uma conta</option>
                                @foreach ($accounts as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </template>

                    <!-- Transferência conta->conta -->
                    <template x-if="form.kind==='transfer'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label"><span class="label-text">Origem</span></label>
                                <select class="select select-bordered" x-model="form.from_account_id">
                                    <option value="" disabled selected>Conta de origem</option>
                                    @foreach ($accounts as $a)
                                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">Destino</span></label>
                                <select class="select select-bordered" x-model="form.to_account_id">
                                    <option value="" disabled selected>Conta de destino</option>
                                    @foreach ($accounts as $a)
                                        <option value="{{ $a->id }}"
                                            :disabled="String(form.from_account_id) === '{{ $a->id }}'">
                                            {{ $a->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="opacity-60"
                                    x-show="form.from_account_id && form.to_account_id && form.from_account_id===form.to_account_id">
                                    Origem e destino não podem ser a mesma conta.
                                </small>
                            </div>
                        </div>
                    </template>

                    <!-- Cartão (somente gasto) -->
                    <template x-if="form.mode==='card' && form.kind==='expense'">
                        <div class="space-y-3">
                            <div class="form-control">
                                <label class="label"><span class="label-text">Cartão</span></label>
                                <!-- SEM name/required: hidden 'card_id' envia -->
                                <select class="select select-bordered" x-model="form.card_id">
                                    <option value="" disabled selected>Selecione um cartão</option>
                                    @foreach ($cards ?? [] as $c)
                                        <option value="{{ $c->id }}">{{ $c->name ?? 'Cartão #' . $c->id }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="cursor-pointer label justify-start gap-3">
                                    <input type="checkbox" class="toggle" x-model="form.is_installment">
                                    <span class="label-text">Compra parcelada</span>
                                </label>
                            </div>

                            <template x-if="form.is_installment">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Nº parcelas</span></label>
                                        <input type="number" min="2" max="48"
                                            class="input input-bordered" x-model.number="form.installments">
                                    </div>
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">1ª fatura
                                                (opcional)</span></label>
                                        <input type="month" class="input input-bordered"
                                            x-model="form.first_invoice_month">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            <!-- STEP 3: Detalhes -->
            <template x-if="step===3">
                <div class="space-y-3">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text" x-text="amountLabel()"></span>
                        </label>
                        <!-- este fica no DOM no submit, pode ter name/required -->
                        <input type="number" step="0.01" min="0" name="amount_abs"
                            class="input input-bordered text-lg" inputmode="decimal" x-model.number="form.amount_abs"
                            required>
                    </div>

                    <!-- Método só para gasto/ganho -->
                    <template x-if="form.kind!=='transfer'">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Método (opcional)</span></label>
                            <input type="text" name="method" class="input input-bordered"
                                placeholder="pix, débito, boleto..." x-model="form.method">
                        </div>
                    </template>

                    <!-- Categoria/Sub somente para gasto/ganho -->
                    <template x-if="form.kind!=='transfer'">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Classificação (opcional)</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <!-- Categoria (sem name; hidden category_id envia) -->
                                <select class="select select-bordered" x-model="form.category_id"
                                    @change="form.subcategory_id=''">
                                    <option value="">Sem categoria</option>
                                    @foreach ($categoriesByKind['expense'] ?? [] as $c)
                                        <option value="{{ data_get($c, 'id') }}" x-show="form.kind==='expense'">
                                            {{ data_get($c, 'name') }}</option>
                                    @endforeach
                                    @foreach ($categoriesByKind['income'] ?? [] as $c)
                                        <option value="{{ data_get($c, 'id') }}" x-show="form.kind==='income'">
                                            {{ data_get($c, 'name') }}</option>
                                    @endforeach
                                </select>

                                <!-- Subcategoria (sem name; hidden subcategory_id envia) -->
                                <select class="select select-bordered" x-model="form.subcategory_id"
                                    :disabled="!form.category_id || subcategories().length === 0">
                                    <option value="">Sem subcategoria</option>
                                    <template x-for="s in subcategories()" :key="s.id">
                                        <option :value="s.id" x-text="s.name"></option>
                                    </template>
                                </select>
                            </div>
                            <p class="text-xs opacity-60 mt-1"
                                x-show="form.category_id && subcategories().length===0">
                                Esta categoria não possui subcategorias ativas.
                            </p>
                        </div>
                    </template>

                    <div class="form-control">
                        <label class="label"><span class="label-text">Notas (opcional)</span></label>
                        <textarea name="notes" class="textarea textarea-bordered" rows="3" x-model="form.notes"></textarea>
                    </div>
                </div>
            </template>

            <!-- Footer fixo (mobile) -->
            <div class="sticky bottom-0 left-0 right-0 bg-base-100 border-t p-3 mt-2 flex gap-2">
                <button type="button" class="btn flex-1" @click="prev()" :disabled="step === 1">Voltar</button>
                <button type="button" class="btn btn-primary flex-1" x-show="step<3" :disabled="!canNext()"
                    @click="next()">Próximo</button>
                <button x-show="step===3" class="btn btn-primary flex-1" :disabled="!canSubmit()">Salvar</button>
            </div>
        </form>

        <!-- mini-debug opcional -->
        <div class="p-3 text-xs opacity-60">
            <span class="mr-4" x-text="'kind='+form.kind"></span>
            <span class="mr-4" x-text="'mode='+form.mode"></span>
            <span x-text="'subs='+subcategories().length"></span>
        </div>
    </div>

    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('txWizard', () => ({
            step: 1,
            subByCat: {},
            form: {
                date: "{{ now()->format('Y-m-d\TH:i') }}",
                kind: 'expense', // 'expense' | 'income' | 'transfer'
                mode: 'account', // 'account' | 'card'
                account_id: '',
                from_account_id: '',
                to_account_id: '',
                card_id: '',
                is_installment: false,
                installments: 2,
                first_invoice_month: '',
                amount_abs: null,
                method: '',
                category_id: '',
                subcategory_id: '',
                notes: ''
            },

            init() {
                // carrega mapa de subcategorias do atributo data-*
                try {
                    const raw = this.$el.dataset.subByCat || '{}';
                    this.subByCat = JSON.parse(raw);
                } catch (e) {
                    console.error('Falha ao parsear subByCat:', e);
                    this.subByCat = {};
                }
            },

            // UI helpers
            btnKind(k) {
                return this.form.kind === k ? 'btn-primary' : 'btn-outline';
            },
            btnMode(m) {
                return this.form.mode === m ? 'btn-primary' : 'btn-outline';
            },

            setKind(k) {
                this.form.kind = k;
                if (k === 'transfer') {
                    // transferência sempre em contas
                    this.form.mode = 'account';
                    this.form.card_id = '';
                    this.form.account_id = '';
                    this.form.from_account_id = '';
                    this.form.to_account_id = '';
                    this.form.category_id = '';
                    this.form.subcategory_id = '';
                } else {
                    // gasto/ganho
                    if (!['account', 'card'].includes(this.form.mode)) this.form.mode = 'account';
                }
            },

            setMode(m) {
                this.form.mode = m;
                if (m === 'card') {
                    this.form.account_id = '';
                } else {
                    this.form.card_id = '';
                    this.form.is_installment = false;
                    this.form.installments = 2;
                    this.form.first_invoice_month = '';
                }
            },

            amountLabel() {
                if (this.form.kind === 'income') return 'Valor (ganho)';
                if (this.form.kind === 'transfer') return 'Valor (transferência)';
                return 'Valor (gasto)';
            },

            subcategories() {
                const key = String(this.form.category_id || '');
                return this.subByCat[key] || [];
            },

            // Navegação / validação
            canNext() {
                if (this.step === 1) {
                    return !!this.form.date && ['expense', 'income', 'transfer'].includes(this.form
                        .kind);
                }
                if (this.step === 2) {
                    if (this.form.kind === 'transfer') {
                        return this.form.from_account_id && this.form.to_account_id &&
                            this.form.from_account_id !== this.form.to_account_id;
                    }
                    if (this.form.mode === 'account') return !!this.form.account_id;
                    if (this.form.mode === 'card') return !!this.form.card_id;
                }
                return true;
            },
            next() {
                if (this.canNext() && this.step < 3) this.step++;
            },
            prev() {
                if (this.step > 1) this.step--;
            },

            canSubmit() {
                const val = Number(this.form.amount_abs || 0);
                if (!(val > 0)) return false;

                if (this.form.kind === 'transfer') {
                    return this.form.from_account_id && this.form.to_account_id &&
                        this.form.from_account_id !== this.form.to_account_id;
                }
                if (this.form.mode === 'account') return !!this.form.account_id;
                if (this.form.mode === 'card') return !!this.form.card_id;
                return false;
            },

            handleSubmit(e) {
                // força positivo
                if (this.form.amount_abs) this.form.amount_abs = Math.abs(Number(this.form
                    .amount_abs));

                // se cartão e marcado parcelado, pelo menos 2x; se não marcado, fixa 1x
                if (this.form.mode === 'card' && this.form.kind === 'expense') {
                    if (this.form.is_installment) {
                        if (!this.form.installments || this.form.installments < 2) this.form
                            .installments = 2;
                    } else {
                        this.form.installments = 1;
                        this.form.first_invoice_month = '';
                    }
                }
            },
        }));
    });
</script>
