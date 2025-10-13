@php
    $txSubByCatJs = json_encode(
        (object) ($subcategoriesByCategory ?? []),
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
    );
@endphp

<script type="application/json" id="tx-subByCatData">{!! $txSubByCatJs !!}</script>

<dialog id="txModal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl" x-data="{
        mode: 'account',
        kind: 'expense', // 'expense'|'income'|'transfer'
        selectedCategory: '',
        selectedSubcategory: '',
        fromAccountId: '',
        toAccountId: '',
        subByCat: {},
        subcategories() {
            const key = String(this.selectedCategory || '');
            return this.subByCat[key] || [];
        }
    }" x-init="(() => {
        const sb = document.getElementById('tx-subByCatData');
        if (sb?.textContent?.trim()) {
            try { this.subByCat = JSON.parse(sb.textContent); } catch (e) { this.subByCat = {}; }
        }
        // watchers
        $watch('kind', () => {
            this.selectedCategory = '';
            this.selectedSubcategory = '';
            if (this.kind === 'transfer') this.mode = 'account'; // transferência sempre em conta
        });
        $watch('selectedCategory', () => { this.selectedSubcategory = ''; });
    })()">

        <h3 class="font-bold text-lg mb-3"><i class="fa-solid fa-plus mr-2"></i> Nova transação</h3>

        <form method="POST" action="{{ route('client.transactions.store', ['consultant' => $consultantId]) }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label"><span class="label-text">Data</span></label>
                    <input type="datetime-local" name="date" class="input input-bordered" required
                        value="{{ now()->format('Y-m-d\TH:i') }}">
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">Tipo</span></label>
                    <div class="tabs tabs-boxed w-fit">
                        <button type="button" class="tab" :class="{ 'tab-active': kind==='expense' }"
                            @click="kind='expense'">Gasto</button>
                        <button type="button" class="tab" :class="{ 'tab-active': kind==='income' }"
                            @click="kind='income'">Ganho</button>
                        <button type="button" class="tab" :class="{ 'tab-active': kind==='transfer' }"
                            @click="kind='transfer'">Transferência</button>
                    </div>
                    <small class="opacity-60">“Gasto” grava valor negativo automaticamente.</small>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span id="amountLabel" class="label-text"
                            x-text="kind==='income' ? 'Valor (ganho)' : (kind==='transfer' ? 'Valor (transferência)' : 'Valor (gasto)')">Valor</span>
                    </label>
                    <input type="number" name="amount_abs" step="0.01" min="0" class="input input-bordered"
                        required>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">Método (opcional)</span></label>
                    <input type="text" name="method" class="input input-bordered"
                        placeholder="pix, débito, boleto...">
                </div>

                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text">Lançar em</span></label>
                    <div class="tabs tabs-boxed w-fit">
                        <button type="button" class="tab" :class="{ 'tab-active': mode==='account' }"
                            @click="mode='account'">Conta</button>
                        <button type="button" class="tab"
                            :class="{ 'tab-active': mode==='card', 'tab-disabled': kind==='transfer' }"
                            :disabled="kind === 'transfer'" @click="if(kind!=='transfer'){ mode='card' }">Cartão</button>
                    </div>
                </div>

                <!-- Conta única (não-transfer) -->
                <template x-if="mode === 'account' && kind !== 'transfer'">
                    <div class="form-control md:col-span-2">
                        <label class="label"><span class="label-text">Conta</span></label>
                        <select name="account_id" class="select select-bordered" required>
                            <option value="" disabled selected>Selecione uma conta</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </template>

                <!-- Transferência conta->conta -->
                <template x-if="kind === 'transfer'">
                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Origem</span></label>
                            <select name="from_account_id" class="select select-bordered" required
                                x-model="fromAccountId">
                                <option value="" disabled selected>Conta de origem</option>
                                @foreach ($accounts as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Destino</span></label>
                            <select name="to_account_id" class="select select-bordered" required x-model="toAccountId">
                                <option value="" disabled selected>Conta de destino</option>
                                @foreach ($accounts as $a)
                                    <option value="{{ $a->id }}"
                                        :disabled="String(fromAccountId) === '{{ $a->id }}'">
                                        {{ $a->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="opacity-60"
                                x-show="fromAccountId && toAccountId && fromAccountId===toAccountId">
                                Origem e destino não podem ser a mesma conta.
                            </small>
                        </div>
                    </div>
                </template>

                {{-- Classificação (opcional): só para gasto/ganho --}}
                <template x-if="kind !== 'transfer'">
                    <div class="form-control md:col-span-2">
                        <label class="label"><span class="label-text">Classificação (opcional)</span></label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            {{-- Categorias para GASTO --}}
                            <select x-show="kind==='expense'" :name="kind === 'expense' ? 'category_id' : null"
                                class="select select-bordered" x-model="selectedCategory">
                                <option value="">Sem categoria</option>
                                @foreach ($categoriesByKind['expense'] ?? [] as $c)
                                    <option value="{{ data_get($c, 'id') }}">{{ data_get($c, 'name') }}</option>
                                @endforeach
                            </select>

                            {{-- Categorias para GANHO --}}
                            <select x-show="kind==='income'" :name="kind === 'income' ? 'category_id' : null"
                                class="select select-bordered" x-model="selectedCategory">
                                <option value="">Sem categoria</option>
                                @foreach ($categoriesByKind['income'] ?? [] as $c)
                                    <option value="{{ data_get($c, 'id') }}">{{ data_get($c, 'name') }}</option>
                                @endforeach
                            </select>

                            {{-- Subcategorias dinâmicas --}}
                            <select name="subcategory_id" class="select select-bordered"
                                x-model="selectedSubcategory"
                                :disabled="!selectedCategory || subcategories().length === 0">
                                <option value="">Sem subcategoria</option>
                                <template x-for="s in subcategories()" :key="s.id">
                                    <option :value="s.id" x-text="s.name"></option>
                                </template>
                            </select>
                        </div>

                        <p class="text-xs opacity-60 mt-1" x-show="selectedCategory && subcategories().length === 0">
                            Esta categoria não possui subcategorias ativas.
                        </p>
                    </div>
                </template>

                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text">Notas (opcional)</span></label>
                    <textarea name="notes" class="textarea textarea-bordered" rows="3"></textarea>
                </div>
            </div>

            <div class="modal-action">
                <button type="button" class="btn"
                    onclick="document.getElementById('txModal').close()">Cancelar</button>
                <button class="btn btn-primary"
                    onclick="
                  const f = this.closest('form');
                  const inp = f.querySelector('input[name=&quot;amount_abs&quot;]');
                  if (inp && inp.value) { inp.value = Math.abs(inp.value); }
                ">
                    Salvar
                </button>
            </div>
        </form>
    </div>

    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
