{{-- resources/views/transactions/create.blade.php --}}
@extends('layouts.app')

@section('content')
    @php
        $title = 'Nova transação (IA)';
    @endphp

    <div class="p-4 space-y-6 max-w-[1200px] mx-auto">
        {{-- Breadcrumb + título --}}
        <div class="breadcrumbs text-sm text-base-content/70">
            <ul>
                <li><a href="{{ route('client.dashboard', ['consultant' => $consultantId]) }}" class="link">Início</a></li>
                <li><a href="#" class="link">Transações</a></li>
                <li class="font-medium">{{ $title }}</li>
            </ul>
        </div>

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">{{ $title }}</h1>
            <div class="text-xs opacity-70">
                Cliente #{{ $clientId }} • Consultor #{{ $consultantId }}
            </div>
        </div>

        {{-- Avisos / feedback --}}
        @if (session('success'))
            <div class="alert alert-success my-2">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error my-2">
                <ul class="list-disc ms-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Dados globais para JS --}}
        <script>
            window.__txCtx = {
                consultantId: @json($consultantId),
                clientId: @json($clientId),
                accounts: @json($accounts),
                cards: @json($cards),
                categories: @json($categories),
                subcategories: @json($subcategories),

                endpoints: {
                    parseText: @json(route('gemini.parse-text')),
                    parseImage: @json(route('gemini.parse-image')),
                }
            };
            console.log(window.__txCtx.endpoints);
        </script>

        {{-- Grid principal --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- COLUNA ESQUERDA: Entrada (texto / imagem) + resultado JSON --}}
            <div class="space-y-6">
                {{-- Card: Entrada texto / imagem --}}
                <div class="card bg-base-200">
                    <div class="card-body space-y-4">
                        <h2 class="card-title">Entrada</h2>

                        <label class="form-control w-full">
                            <div class="label">
                                <span class="label-text">Texto livre (ex.: “Mercado X 172,84 no Visa ••6982 12/10”)</span>
                            </div>
                            <textarea id="freeText" class="textarea textarea-bordered min-h-28" maxlength="4000" placeholder="Digite aqui..."></textarea>
                        </label>

                        <div class="divider">ou</div>

                        <label class="form-control w-full">
                            <div class="label">
                                <span class="label-text">Imagem de recibo (JPG/PNG/WebP)</span>
                            </div>
                            <input id="receiptFile" type="file" accept="image/png,image/jpeg,image/webp"
                                class="file-input file-input-bordered w-full">
                        </label>

                        <div class="flex flex-wrap gap-2 pt-2">
                            <button id="btnParseText" class="btn btn-primary">Extrair do texto (Gemini)</button>
                            <button id="btnParseImage" class="btn">Extrair da imagem (Gemini)</button>
                            <button id="btnClearInput" class="btn btn-ghost">Limpar</button>
                        </div>

                        <div id="runStatus" class="text-sm opacity-70"></div>
                    </div>
                </div>

                {{-- Card: JSON retornado --}}
                <div class="card bg-base-200">
                    <div class="card-body space-y-2">
                        <div class="flex items-center justify-between">
                            <h2 class="card-title">Resultado (JSON)</h2>
                            <div class="flex gap-2">
                                <button id="btnApplyToForm" class="btn btn-success btn-sm">Aplicar ao formulário</button>
                                <button id="btnCopyJson" class="btn btn-sm">Copiar JSON</button>
                            </div>
                        </div>
                        <pre id="jsonOut" class="mockup-code whitespace-pre-wrap text-xs p-3 overflow-auto max-h-72"><code>{}</code></pre>
                        <details class="collapse collapse-arrow bg-base-100">
                            <summary class="collapse-title text-sm">Prompt usado</summary>
                            <div class="collapse-content">
                                <pre id="debugPrompt" class="text-xs overflow-auto max-h-64"></pre>
                            </div>
                        </details>
                        <details class="collapse collapse-arrow bg-base-100">
                            <summary class="collapse-title text-sm">Bruto (API)</summary>
                            <div class="collapse-content">
                                <pre id="debugRaw" class="text-xs overflow-auto max-h-64"></pre>
                            </div>
                        </details>
                    </div>
                </div>
            </div>

            {{-- COLUNA DIREITA: Formulário editável para salvar --}}
            <div class="space-y-6">
                <div class="card bg-base-200">
                    <div class="card-body space-y-4">
                        <h2 class="card-title">Formulário</h2>

                        {{-- Ajuste a action para a sua rota de salvar --}}
                        <form id="saveForm" method="POST" action="{{ route('transactions.store') }}" class="space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {{-- amount --}}
                                <label class="form-control">
                                    <div class="label"><span class="label-text">Valor (R$)</span></div>
                                    <input id="f_amount" name="amount" type="text" inputmode="decimal"
                                        class="input input-bordered" placeholder="0,00">
                                </label>

                                {{-- type --}}
                                <label class="form-control">
                                    <div class="label"><span class="label-text">Tipo</span></div>
                                    <select id="f_type" name="type" class="select select-bordered">
                                        <option value="expense">expense</option>
                                        <option value="income">income</option>
                                        <option value="transfer">transfer</option>
                                        <option value="adjustment">adjustment</option>
                                    </select>
                                </label>

                                {{-- method --}}
                                <label class="form-control">
                                    <div class="label"><span class="label-text">Método</span></div>
                                    <select id="f_method" name="method" class="select select-bordered">
                                        <option value="">—</option>
                                        <option value="pix">pix</option>
                                        <option value="debit">debit</option>
                                        <option value="credit_card">credit_card</option>
                                        <option value="cash">cash</option>
                                        <option value="transfer">transfer</option>
                                        <option value="boleto">boleto</option>
                                        <option value="adjustment">adjustment</option>
                                    </select>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Descrição / Estabelecimento (atalho para notes) --}}
                                <label class="form-control">
                                    <div class="label"><span class="label-text">Descrição / Estabelecimento</span></div>
                                    <input id="f_notes_shortcut" type="text" class="input input-bordered"
                                        placeholder="Ex.: Mayumes Açaí, mercado, almoço, etc.">
                                </label>

                                {{-- date --}}
                                <label class="form-control">
                                    <div class="label"><span class="label-text">Data</span></div>
                                    <input id="f_date" name="date" type="datetime-local"
                                        class="input input-bordered">
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- account_id --}}
                                <label class="form-control">
                                    <div class="label">
                                        <span class="label-text">Conta</span>
                                        <span class="label-text-alt opacity-70">Habilita p/ pix, débito, etc.</span>
                                    </div>
                                    <select id="f_account_id" name="account_id" class="select select-bordered">
                                        <option value="">—</option>
                                    </select>
                                </label>

                                {{-- card_id --}}
                                <label class="form-control">
                                    <div class="label">
                                        <span class="label-text">Cartão</span>
                                        <span class="label-text-alt opacity-70">Habilita quando método = crédito</span>
                                    </div>
                                    <select id="f_card_id" name="card_id" class="select select-bordered" disabled>
                                        <option value="">—</option>
                                    </select>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- category_id --}}
                                <label class="form-control">
                                    <div class="label"><span class="label-text">Categoria</span></div>
                                    <select id="f_category_id" name="category_id" class="select select-bordered">
                                        <option value="">—</option>
                                    </select>
                                </label>

                                {{-- subcategory_id --}}
                                <label class="form-control">
                                    <div class="label"><span class="label-text">Subcategoria</span></div>
                                    <select id="f_subcategory_id" name="subcategory_id" class="select select-bordered">
                                        <option value="">—</option>
                                    </select>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {{-- installment_count --}}
                                <label class="form-control">
                                    <div class="label"><span class="label-text">Parcelas (qtd)</span></div>
                                    <input id="f_installment_count" name="installment_count" type="number"
                                        min="1" class="input input-bordered" placeholder="Ex.: 10">
                                </label>

                                {{-- installment_index --}}
                                <label class="form-control">
                                    <div class="label"><span class="label-text">Parcela (nº)</span></div>
                                    <input id="f_installment_index" name="installment_index" type="number"
                                        min="1" class="input input-bordered" placeholder="Ex.: 3">
                                </label>

                                {{-- status (opcional) --}}
                                <label class="form-control">
                                    <div class="label"><span class="label-text">Status</span></div>
                                    <select id="f_status" name="status" class="select select-bordered">
                                        <option value="">—</option>
                                        <option value="pending">pending</option>
                                        <option value="confirmed">confirmed</option>
                                    </select>
                                </label>
                            </div>

                            {{-- notes (campo real que será salvo) --}}
                            <label class="form-control">
                                <div class="label"><span class="label-text">Observações</span></div>
                                <textarea id="f_notes" name="notes" class="textarea textarea-bordered min-h-24" placeholder="Notas livres..."></textarea>
                            </label>

                            <div class="flex flex-wrap gap-2 pt-2">
                                <button type="submit" class="btn btn-primary">Salvar</button>
                                <button type="button" id="btnClearForm" class="btn btn-ghost">Limpar formulário</button>
                            </div>
                        </form>

                        <div class="text-xs opacity-70">
                            Dica: você pode ajustar qualquer campo acima antes de salvar.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- JS (vanilla) --}}
    <script>
        (function() {
            const ctx = window.__txCtx || {};
            const accounts = ctx.accounts || [];
            const cards = ctx.cards || [];
            const categories = ctx.categories || [];
            const subcategories = ctx.subcategories || [];

            // ---------- Utilidades ----------
            const $ = (sel) => document.querySelector(sel);
            const fmtJSON = (obj) => JSON.stringify(obj, null, 2);

            function normalizeMoneyToFloat(s) {
                if (!s) return null;
                const t = String(s).replace(/\./g, '').replace(',', '.').replace(/[^\d.]/g, '');
                const n = Number(t);
                return Number.isFinite(n) ? n : null;
            }

            function fillMoneyInput(el, val) {
                if (val === null || val === undefined || val === '') {
                    el.value = '';
                    return;
                }
                const n = Number(val);
                el.value = Number.isFinite(n) ? n.toFixed(2).replace('.', ',') : '';
            }

            // ---------- Elementos ----------
            const freeText = $('#freeText');
            const receiptFile = $('#receiptFile');
            const runStatus = $('#runStatus');
            const jsonOut = $('#jsonOut code');
            const debugPrompt = $('#debugPrompt');
            const debugRaw = $('#debugRaw');

            const btnParseText = $('#btnParseText');
            const btnParseImage = $('#btnParseImage');
            const btnClearInput = $('#btnClearInput');
            const btnCopyJson = $('#btnCopyJson');
            const btnApplyToForm = $('#btnApplyToForm');
            const btnClearForm = $('#btnClearForm');

            // Form Fields
            const f_amount = $('#f_amount');
            const f_type = $('#f_type');
            const f_method = $('#f_method');
            const f_date = $('#f_date');
            const f_account_id = $('#f_account_id');
            const f_card_id = $('#f_card_id');
            const f_category_id = $('#f_category_id');
            const f_subcategory_id = $('#f_subcategory_id');
            const f_installment_count = $('#f_installment_count');
            const f_installment_index = $('#f_installment_index');
            const f_status = $('#f_status');
            const f_notes = $('#f_notes');
            const f_notes_shortcut = $('#f_notes_shortcut');

            // ---------- Popular selects ----------
            function populateSelect(el, data, labelFn) {
                const cur = el.value;
                el.innerHTML = '<option value="">—</option>';
                data.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = labelFn ? labelFn(item) : (item.label ?? item.name ?? item.id);
                    el.appendChild(opt);
                });
                if (cur && data.some(d => String(d.id) === String(cur))) el.value = cur;
            }

            function linkCategoryToSub() {
                const catId = f_category_id.value || '';
                const filtered = catId ? subcategories.filter(s => String(s.category_id) === String(catId)) :
                    subcategories;
                populateSelect(f_subcategory_id, filtered, (s) => s.label);
            }

            populateSelect(f_account_id, accounts, (a) => a.label);
            populateSelect(f_card_id, cards, (c) => `${c.label}${c.last4 ? ' ••' + c.last4 : ''}`);
            populateSelect(f_category_id, categories, (c) => c.label);
            linkCategoryToSub();
            f_category_id.addEventListener('change', linkCategoryToSub);

            // Habilita/desabilita selects conforme método
            function refreshMethodDeps() {
                const isCredit = (f_method.value || '') === 'credit_card';
                f_card_id.disabled = !isCredit;
                if (!isCredit) f_card_id.value = '';
                f_account_id.disabled = false;
            }
            f_method.addEventListener('change', refreshMethodDeps);
            refreshMethodDeps();

            // ---------- Execução de chamadas ----------
            let lastJson = {};

            function setStatus(msg, kind = 'info') {
                const map = {
                    info: '',
                    ok: 'text-success',
                    err: 'text-error'
                };
                runStatus.className = 'text-sm ' + (map[kind] || '');
                runStatus.textContent = msg || '';
            }

            function showResult(payload) {
                const safe = payload?.data ?? {};
                lastJson = safe;
                jsonOut.textContent = fmtJSON(safe);
                debugPrompt.textContent = payload?.prompt ?? '';
                debugRaw.textContent = fmtJSON(payload?.raw ?? {});
            }

            function clearInput() {
                freeText.value = '';
                receiptFile.value = '';
                setStatus('');
            }

            function clearForm() {
                [f_amount, f_date, f_installment_count, f_installment_index].forEach(el => el.value = '');
                [f_type, f_method, f_account_id, f_card_id, f_category_id, f_subcategory_id, f_status].forEach(el => el
                    .value = '');
                f_notes.value = '';
                f_notes_shortcut.value = '';
                linkCategoryToSub();
                refreshMethodDeps();
            }

            async function parseText() {
                const text = (freeText.value || '').trim();
                if (!text) {
                    setStatus('Digite um texto para extrair.', 'err');
                    return;
                }
                setStatus('Processando texto...', 'info');
                try {
                    const body = {
                        text,
                        client_id: ctx.clientId,
                        context: {
                            accounts,
                            cards,
                            categories,
                            subcategories
                        }
                    };
                    const res = await fetch(ctx.endpoints.parseText, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(body)
                    });
                    const j = await res.json();
                    showResult(j);
                    setStatus(j.ok ? 'Extração concluída.' : 'Extração concluída com alertas.');
                } catch (e) {
                    setStatus('Falha na extração do texto.', 'err');
                }
            }

            async function parseImage() {
                const file = receiptFile.files?.[0];
                if (!file) {
                    setStatus('Selecione uma imagem primeiro.', 'err');
                    return;
                }
                setStatus('Processando imagem...', 'info');
                try {
                    const fd = new FormData();
                    fd.append('image', file);
                    fd.append('client_id', String(ctx.clientId));
                    fd.append('context', JSON.stringify({
                        accounts,
                        cards,
                        categories,
                        subcategories
                    }));

                    const res = await fetch(ctx.endpoints.parseImage, {
                        method: 'POST',
                        body: fd
                    });
                    const j = await res.json();
                    showResult(j);
                    setStatus(j.ok ? 'Extração concluída.' : 'Extração concluída com alertas.');
                } catch (e) {
                    setStatus('Falha na extração da imagem.', 'err');
                }
            }

            // ---------- Aplicar JSON ao formulário ----------
            function applyToForm(data) {
                if (!data || typeof data !== 'object') return;

                fillMoneyInput(f_amount, data.amount ?? null);
                f_type.value = data.type ?? 'expense';
                f_method.value = data.method ?? '';
                refreshMethodDeps();

                f_date.value = data.date ? data.date.replace(' ', 'T') : '';

                f_account_id.value = data.account_id ?? '';
                f_card_id.value = data.card_id ?? '';

                f_category_id.value = data.category_id ?? '';
                linkCategoryToSub();
                f_subcategory_id.value = data.subcategory_id ?? '';

                f_installment_count.value = data.installment_count ?? '';
                f_installment_index.value = data.installment_index ?? '';

                f_status.value = data.status ?? '';

                // Sem coluna merchant: usa notes, e se vier merchant da IA, faz fallback
                const notesFromAi = data.notes ?? data.merchant ?? '';
                f_notes.value = notesFromAi;
                f_notes_shortcut.value = notesFromAi;
            }

            // ---------- Listeners ----------
            btnParseText.addEventListener('click', parseText);
            btnParseImage.addEventListener('click', parseImage);
            btnClearInput.addEventListener('click', () => {
                clearInput();
                jsonOut.textContent = '{}';
                debugPrompt.textContent = '';
                debugRaw.textContent = '';
            });

            btnApplyToForm.addEventListener('click', () => applyToForm(lastJson));
            btnCopyJson.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(jsonOut.textContent || '{}');
                    setStatus('JSON copiado.', 'ok');
                } catch {
                    setStatus('Não foi possível copiar.', 'err');
                }
            });
            btnClearForm.addEventListener('click', clearForm);

            // Sincroniza atalho com notes
            const syncNotes = () => {
                f_notes.value = f_notes_shortcut.value;
            };
            f_notes_shortcut.addEventListener('input', syncNotes);
            f_notes.addEventListener('input', () => {
                f_notes_shortcut.value = f_notes.value;
            });

            // Normaliza valor ao sair do campo
            f_amount.addEventListener('blur', () => {
                const n = normalizeMoneyToFloat(f_amount.value);
                if (n !== null) fillMoneyInput(f_amount, n);
            });
        })();
    </script>
@endsection
