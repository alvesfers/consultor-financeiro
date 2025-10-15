<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('investmentWizard', () => ({
            step: 1,
            subMap: {},

            // state principal do formulário
            form: {
                move: 'deposit', // 'deposit' | 'withdraw'
                date: "{{ now()->format('Y-m-d\TH:i') }}",
                account_id: '',
                investment_id: '',
                category_id: '', // controlado via currentCatId (hidden)
                subcategory_id: '',
                amount_abs: null,
                notes: ''
            },

            // categorias selecionadas por aba
            catDeposit: '',
            catWithdraw: '',

            // lifecycle
            init() {
                // carrega mapa category_id => [{id, name}]
                try {
                    const raw = this.$el.dataset.subByCat || '{}';
                    const map = JSON.parse(raw);
                    // normaliza
                    this.subMap = Object.keys(map).reduce((acc, k) => {
                        acc[String(k)] = (map[k] || []).map(s => ({
                            id: Number(s.id),
                            name: s.name
                        }));
                        return acc;
                    }, {});
                } catch (e) {
                    console.error('Falha ao parsear subByCat:', e);
                    this.subMap = {};
                }

                // focar valor quando chegar na etapa 4
                this.$watch('step', (val) => {
                    if (val === 4) this.$nextTick(() => this.$refs.amount?.focus());
                });
            },

            // computeds
            get currentCatId() {
                return this.form.move === 'deposit' ? this.catDeposit : this.catWithdraw;
            },
            get availableSubs() {
                const key = String(this.currentCatId || '');
                return this.subMap[key] || [];
            },

            // helpers UI
            btnMove(m) {
                return this.form.move === m ? 'btn-primary' : 'btn-outline';
            },
            setMove(m) {
                if (this.form.move === m) return;
                this.form.move = m;
                // limpando seleção de subcategoria ao alternar a raiz
                this.form.subcategory_id = '';
            },

            // navegação/validação
            canNext() {
                if (this.step === 1) {
                    return !!this.form.date && ['deposit', 'withdraw'].includes(this.form.move);
                }
                if (this.step === 2) {
                    return !!this.form.account_id && !!this.form.investment_id;
                }
                if (this.step === 3) {
                    // classificação é opcional; sempre pode avançar
                    return true;
                }
                return true;
            },
            next() {
                if (this.canNext() && this.step < 4) this.step++;
            },
            prev() {
                if (this.step > 1) this.step--;
            },

            canSubmit() {
                const val = Number(this.form.amount_abs || 0);
                return val > 0 && !!this.form.account_id && !!this.form.investment_id;
            },

            handleSubmit(e) {
                // mantém amount_abs sempre positivo
                if (this.form.amount_abs) {
                    this.form.amount_abs = Math.abs(Number(this.form.amount_abs));
                }
                // garante que o hidden category_id vai com a categoria certa
                this.form.category_id = this.currentCatId || '';
                // deixe o submit seguir normal
            },

            // ações rápidas de valor
            bump(n) {
                const cur = Number(this.form.amount_abs || 0);
                this.form.amount_abs = (cur + n).toFixed(2);
            },
        }));
    });
</script>

<dialog id="investmentModal" class="modal">
    <div class="modal-box w-full max-w-md p-0" x-data="investmentWizard()" x-init="init()"
        data-sub-by-cat='@json($subcategoriesByCategory)'>

        {{-- Header --}}
        <div class="p-4 border-b">
            <h3 class="font-bold text-lg">
                <i class="fa-solid fa-arrow-trend-up mr-2"></i> Movimentação de investimento
            </h3>
            <div class="steps steps-horizontal mt-3">
                <button class="step" :class="{ 'step-primary': step >= 1 }">Tipo</button>
                <button class="step" :class="{ 'step-primary': step >= 2 }">Origem/Destino</button>
                <button class="step" :class="{ 'step-primary': step >= 3 }">Classificação</button>
                <button class="step" :class="{ 'step-primary': step >= 4 }">Detalhes</button>
            </div>
        </div>

        <form method="POST" action="{{ route('client.investments.move', ['consultant' => $consultantId]) }}"
            @submit="handleSubmit" class="p-4 space-y-4">
            @csrf

            {{-- STEP 1: Tipo (Depositar / Resgatar) + Data --}}
            <template x-if="step===1">
                <div class="space-y-3">
                    <label class="label"><span class="label-text">Tipo de movimento</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" class="btn" :class="btnMove('deposit')"
                            @click="setMove('deposit')">Depositar</button>
                        <button type="button" class="btn" :class="btnMove('withdraw')"
                            @click="setMove('withdraw')">Resgatar</button>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text">Data</span></label>
                        <input type="datetime-local" name="date" class="input input-bordered" required
                            x-model="form.date" value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                </div>
            </template>

            {{-- STEP 2: Origem/Destino (Conta + Investimento) --}}
            <template x-if="step===2">
                <div class="grid grid-cols-1 gap-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Conta</span></label>
                        <select name="account_id" class="select select-bordered" x-model="form.account_id" required>
                            <option value="" disabled selected>Selecione uma conta</option>
                            @foreach ($accounts ?? [] as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text">Investimento</span></label>
                        <select name="investment_id" class="select select-bordered" x-model="form.investment_id"
                            required>
                            <option value="" disabled selected>Selecione um investimento</option>
                            @foreach ($investments ?? [] as $inv)
                                <option value="{{ (int) data_get($inv, 'id') }}">{{ data_get($inv, 'name') }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </template>

            {{-- STEP 3: Classificação (opcional) — categorias por movimento + subcategoria dependente --}}
            <template x-if="step===3">
                <div class="form-control">
                    <label class="label"><span class="label-text">Classificação (opcional)</span></label>

                    {{-- hidden sempre refletindo a categoria ativa --}}
                    <input type="hidden" name="category_id" :value="currentCatId || ''">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        {{-- Categoria (Depositar) --}}
                        <select class="select select-bordered" x-show="form.move==='deposit'" x-model="catDeposit">
                            <option value="">Sem categoria</option>
                            @foreach ($invDepositCats ?? [] as $c)
                                <option value="{{ (int) data_get($c, 'id') }}">{{ data_get($c, 'name') }}</option>
                            @endforeach
                        </select>

                        {{-- Categoria (Resgatar) --}}
                        <select class="select select-bordered" x-show="form.move==='withdraw'" x-model="catWithdraw">
                            <option value="">Sem categoria</option>
                            @foreach ($invWithdrawCats ?? [] as $c)
                                <option value="{{ (int) data_get($c, 'id') }}">{{ data_get($c, 'name') }}</option>
                            @endforeach
                        </select>

                        {{-- Subcategoria --}}
                        <select name="subcategory_id" class="select select-bordered" x-model="form.subcategory_id"
                            :disabled="availableSubs.length === 0">
                            <template x-if="availableSubs.length===0">
                                <option value="">Sem subcategoria</option>
                            </template>
                            <template x-for="s in availableSubs" :key="s.id">
                                <option :value="s.id" x-text="s.name"></option>
                            </template>
                        </select>
                    </div>

                    <p class="text-xs opacity-70 mt-1" x-show="currentCatId && availableSubs.length===0">
                        Esta categoria não possui subcategorias ativas.
                    </p>
                </div>
            </template>

            {{-- STEP 4: Detalhes (valor + notas) --}}
            <template x-if="step===4">
                <div class="space-y-3">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text"
                                x-text="form.move==='deposit' ? 'Valor a investir' : 'Valor a resgatar'"></span>
                        </label>
                        <div class="join w-full">
                            <input type="number" step="0.01" min="0" name="amount_abs"
                                class="input input-bordered join-item w-full text-lg" inputmode="decimal"
                                x-model.number="form.amount_abs" x-ref="amount" required>
                            {{-- Ações rápidas de valor --}}
                            <div class="join-item dropdown dropdown-end">
                                <div tabindex="0" role="button" class="btn btn-outline">+ Rápido</div>
                                <ul class="dropdown-content menu bg-base-100 rounded-box z-[1] p-2 shadow">
                                    <li><a @click.prevent="bump(10)">+10</a></li>
                                    <li><a @click.prevent="bump(50)">+50</a></li>
                                    <li><a @click.prevent="bump(100)">+100</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text">Notas (opcional)</span></label>
                        <textarea name="notes" class="textarea textarea-bordered" rows="3" x-model="form.notes"></textarea>
                    </div>
                </div>
            </template>

            {{-- camadas hidden coerentes --}}
            <input type="hidden" name="move" :value="form.move">

            {{-- Rodapé fixo (mobile) --}}
            <div class="sticky bottom-0 left-0 right-0 bg-base-100 border-t p-3 mt-2 flex gap-2">
                <button type="button" class="btn flex-1" @click="prev()" :disabled="step === 1">Voltar</button>

                <button type="button" class="btn btn-primary flex-1" x-show="step<4" :disabled="!canNext()"
                    @click="next()">Próximo</button>

                <button x-show="step===4" class="btn btn-primary flex-1" :disabled="!canSubmit()">Salvar</button>
            </div>
        </form>

        {{-- debugzinho opcional --}}
        <div class="p-3 text-xs opacity-60">
            <span class="mr-3" x-text="'move='+form.move"></span>
            <span class="mr-3" x-text="'cat='+currentCatId"></span>
            <span class="mr-3" x-text="'subs='+availableSubs.length"></span>
        </div>
    </div>

    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
