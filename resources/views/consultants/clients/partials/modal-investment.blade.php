<dialog id="investmentModal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl" x-data="{ move: 'deposit' }">
        <h3 class="font-bold text-lg mb-3"><i class="fa-solid fa-arrow-trend-up mr-2"></i> Movimentação de investimento
        </h3>

        <form method="POST" action="{{ route('client.investments.move', ['consultant' => $consultantId]) }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text">Tipo de movimento</span></label>
                    <div class="tabs tabs-boxed w-fit">
                        <button type="button" class="tab" :class="{ 'tab-active': move==='deposit' }"
                            @click="move='deposit'">Depositar</button>
                        <button type="button" class="tab" :class="{ 'tab-active': move==='withdraw' }"
                            @click="move='withdraw'">Resgatar</button>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">Data</span></label>
                    <input type="datetime-local" name="date" class="input input-bordered" required
                        value="{{ now()->format('Y-m-d\TH:i') }}">
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text"
                            x-text="move==='deposit' ? 'Valor a investir' : 'Valor a resgatar'">Valor</span></label>
                    <input type="number" name="amount_abs" step="0.01" min="0" class="input input-bordered"
                        required>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">Conta</span></label>
                    <select name="account_id" class="select select-bordered" required>
                        <option value="" disabled selected>Selecione uma conta</option>
                        @foreach ($accounts ?? [] as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">Investimento</span></label>
                    <select name="investment_id" class="select select-bordered" required>
                        <option value="" disabled selected>Selecione um investimento</option>
                        @foreach ($investments ?? [] as $inv)
                            <option value="{{ data_get($inv, 'id') }}">{{ data_get($inv, 'name') }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Classificação (opcional) — deposit: filhos de "Investimento"; withdraw: filhos de "Resgate" --}}
                <div class="form-control md:col-span-2" x-data="{ catDeposit: '', catWithdraw: '' }"
                    x-effect="
               const hidden = $el.querySelector('input[name=category_id]');
               hidden.value = (move==='deposit' ? catDeposit : catWithdraw) || '';
             ">
                    <label class="label"><span class="label-text">Classificação (opcional)</span></label>

                    <input type="hidden" name="category_id" value="">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <select class="select select-bordered" x-show="move==='deposit'" x-model="catDeposit">
                            <option value="">Sem categoria</option>
                            @foreach ($invDepositCats ?? [] as $c)
                                <option value="{{ data_get($c, 'id') }}">{{ data_get($c, 'name') }}</option>
                            @endforeach
                        </select>

                        <select class="select select-bordered" x-show="move==='withdraw'" x-model="catWithdraw">
                            <option value="">Sem categoria</option>
                            @foreach ($invWithdrawCats ?? [] as $c)
                                <option value="{{ data_get($c, 'id') }}">{{ data_get($c, 'name') }}</option>
                            @endforeach
                        </select>

                        <select name="subcategory_id" class="select select-bordered" disabled>
                            <option value="">Sem subcategoria</option>
                        </select>
                    </div>
                </div>

                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text">Notas (opcional)</span></label>
                    <textarea name="notes" class="textarea textarea-bordered" rows="3"></textarea>
                </div>
            </div>

            <div class="modal-action">
                <button type="button" class="btn"
                    onclick="document.getElementById('investmentModal').close()">Cancelar</button>
                <button class="btn btn-primary"
                    onclick="this.closest('form').querySelector('input[name=&quot;amount_abs&quot;]').value=Math.abs(this.closest('form').querySelector('input[name=&quot;amount_abs&quot;]').value)">
                    Salvar
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
