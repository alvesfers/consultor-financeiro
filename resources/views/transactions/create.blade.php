{{-- resources/views/transactions/create.blade.php --}}
@extends('layouts.app')

@section('content')
    <script>
        // Dados para IA
        window.__pageData = {
            consultantId: @json($consultantId),
            clientId: @json($clientId),
            accounts: @json($accounts),
            cards: @json($cards),
            categories: @json($categories),
            subcategories: @json($subcategories),

            api: {
                text: @json(url('/api/ai/parse-text')),
                audio: @json(url('/api/ai/parse-audio')),
                image: @json(url('/api/ai/parse-image')),
                save: @json(url('/api/transactions')),
            }
        };
    </script>

    <div class="max-w-lg mx-auto p-4 space-y-4">
        {{-- Header --}}
        <div class="rounded-xl bg-gradient-to-br from-base-200 to-base-300 p-4 border border-base-300">
            <h1 class="text-xl font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 opacity-80" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M12 1.75a.75.75 0 0 1 .75.75v2.25h2.25a.75.75 0 0 1 0 1.5H12.75v2.25a.75.75 0 0 1-1.5 0V6.25H9a.75.75 0 0 1 0-1.5h2.25V2.5a.75.75 0 0 1 .75-.75Z" />
                    <path
                        d="M7.5 8.25A2.75 2.75 0 0 0 4.75 11v6A2.75 2.75 0 0 0 7.5 19.75h9A2.75 2.75 0 0 0 19.25 17v-6a2.75 2.75 0 0 0-2.75-2.75h-9Z" />
                </svg>
                Nova transação
            </h1>
            <p class="text-sm opacity-70 mt-1">Digite, fale ou fotografe que a IA preenche o resto.</p>
        </div>

        {{-- TEXTO --}}
        <div class="card bg-base-200 shadow-sm border border-base-300">
            <div class="card-body gap-3">
                <label class="text-sm font-medium">Texto livre</label>
                <input id="freeText" class="input input-bordered w-full"
                    placeholder="Ex.: Ofner 120 crédito Nubank 2x hoje" />
                <button id="btnText" type="button" class="btn btn-primary w-full">
                    <span>Entender com IA</span>
                    <span class="loading loading-spinner loading-sm hidden" id="loadText"></span>
                </button>
            </div>
        </div>

        {{-- ÁUDIO --}}
        <div class="card bg-base-200 shadow-sm border border-base-300">
            <div class="card-body gap-3">
                <label class="text-sm font-medium">Áudio (segure para falar)</label>
                <button id="btnHoldToTalk" type="button" class="btn btn-outline w-full">Segure para falar</button>
                <p class="text-xs opacity-70" id="recStatus">Pronto para gravar</p>
            </div>
        </div>

        {{-- IMAGEM --}}
        <div class="card bg-base-200 shadow-sm border border-base-300">
            <div class="card-body gap-3">
                <label class="text-sm font-medium">Foto de recibo/notinha</label>
                <input id="fileImage" type="file" accept="image/*" capture="environment"
                    class="file-input file-input-bordered w-full" />
                <button id="btnImage" type="button" class="btn w-full">
                    <span>Ler recibo com IA</span>
                    <span class="loading loading-spinner loading-sm hidden" id="loadImg"></span>
                </button>
            </div>
        </div>

        {{-- Resultado IA --}}
        <div id="aiPanel" class="hidden">
            <div id="aiHeader" class="flex items-center justify-between mb-2">
                <div class="badge badge-outline gap-2 hidden" id="aiConfidence">
                    <span class="text-xs">Confiança:</span><span id="aiConfVal">—</span>
                </div>
                <div class="text-xs opacity-70 hidden" id="aiMethodHint"></div>
            </div>

            <div id="aiQuestions" class="alert alert-info mb-3 hidden"></div>

            <div class="grid grid-cols-2 gap-3 bg-base-200 rounded-xl p-3 border border-base-300">
                <label class="form-control">
                    <div class="label"><span class="label-text">Valor (R$)</span></div>
                    <input id="amount" type="number" step="0.01" class="input input-bordered">
                </label>
                <label class="form-control">
                    <div class="label"><span class="label-text">Tipo</span></div>
                    <select id="type" class="select select-bordered">
                        <option value="expense">Despesa</option>
                        <option value="income">Receita</option>
                        <option value="transfer">Transferência</option>
                        <option value="adjustment">Ajuste</option>
                    </select>
                </label>

                <label class="form-control col-span-2">
                    <div class="label"><span class="label-text">Estabelecimento</span></div>
                    <input id="merchant" class="input input-bordered">
                </label>

                <label class="form-control">
                    <div class="label"><span class="label-text">Método</span></div>
                    <select id="method" class="select select-bordered">
                        <option value="">—</option>
                        <option value="pix">PIX</option>
                        <option value="debit">Débito</option>
                        <option value="credit_card">Crédito</option>
                        <option value="cash">Dinheiro</option>
                        <option value="transfer">Transferência</option>
                        <option value="boleto">Boleto</option>
                        <option value="adjustment">Ajuste</option>
                    </select>
                </label>
                <label class="form-control">
                    <div class="label"><span class="label-text">Data</span></div>
                    <input id="date" type="datetime-local" class="input input-bordered">
                </label>

                <label class="form-control">
                    <div class="label"><span class="label-text">Conta</span></div>
                    <select id="account_id" class="select select-bordered">
                        <option value="">—</option>
                        @foreach ($accounts as $a)
                            <option value="{{ $a['id'] }}">{{ $a['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="form-control">
                    <div class="label"><span class="label-text">Cartão</span></div>
                    <select id="card_id" class="select select-bordered">
                        <option value="">—</option>
                        @foreach ($cards as $c)
                            <option value="{{ $c['id'] }}">{{ $c['label'] }}
                                {{ $c['last4'] ? ' ••••' . $c['last4'] : '' }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="form-control">
                    <div class="label"><span class="label-text">Categoria</span></div>
                    <select id="category_id" class="select select-bordered">
                        <option value="">—</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c['id'] }}">{{ $c['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="form-control">
                    <div class="label"><span class="label-text">Subcategoria</span></div>
                    <select id="subcategory_id" class="select select-bordered">
                        <option value="">—</option>
                        @foreach ($subcategories as $s)
                            <option value="{{ $s['id'] }}" data-cat="{{ $s['category_id'] }}">{{ $s['label'] }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="collapse collapse-arrow col-span-2 bg-base-100 border border-base-300 rounded-lg">
                    <input type="checkbox">
                    <div class="collapse-title">Mais detalhes</div>
                    <div class="collapse-content space-y-3">
                        <label class="form-control">
                            <div class="label"><span class="label-text">Parcelas (qtde)</span></div>
                            <input id="installment_count" type="number" min="1" class="input input-bordered">
                        </label>
                        <label class="form-control">
                            <div class="label"><span class="label-text">Parcela atual</span></div>
                            <input id="installment_index" type="number" min="1" class="input input-bordered">
                        </label>
                        <label class="form-control">
                            <div class="label"><span class="label-text">Notas</span></div>
                            <textarea id="notes" class="textarea textarea-bordered" rows="3"></textarea>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer sticky --}}
    <div
        class="sticky bottom-0 left-0 right-0 border-t border-base-300 bg-base-100/90 backdrop-blur supports-[backdrop-filter]:bg-base-100/60">
        <div class="max-w-lg mx-auto p-3">
            <button id="btnSave" type="button" class="btn btn-primary w-full shadow-md">
                <span>Salvar</span>
                <span class="loading loading-spinner loading-sm hidden" id="loadSave"></span>
            </button>
        </div>
    </div>

    {{-- ====== SCRIPT INLINE (sem @push, sem type=module) ====== --}}
    <script defer>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[tx] script carregado');

            const $ = (s) => document.querySelector(s);
            const API = window.__pageData.api;
            const ctx = {
                accounts: window.__pageData.accounts,
                cards: window.__pageData.cards,
                categories: window.__pageData.categories,
                subcategories: window.__pageData.subcategories,
            };

            const aiPanel = $('#aiPanel');
            const qBox = $('#aiQuestions');
            const confWrap = $('#aiConfidence');
            const confVal = $('#aiConfVal');
            const methodHint = $('#aiMethodHint');

            function setLoading(btnSel, spinnerSel, on) {
                const btn = (typeof btnSel === 'string') ? $(btnSel) : btnSel;
                const sp = (typeof spinnerSel === 'string') ? $(spinnerSel) : spinnerSel;
                if (!btn || !sp) return;
                if (on) {
                    btn.setAttribute('disabled', 'disabled');
                    sp.classList.remove('hidden');
                } else {
                    btn.removeAttribute('disabled');
                    sp.classList.add('hidden');
                }
            }

            function fillFromAI(d) {
                aiPanel.classList.remove('hidden');
                const setv = (sel, val) => {
                    if (val !== undefined && val !== null && val !== '') $(sel).value = val;
                };

                setv('#amount', d.amount);
                setv('#type', d.type);
                setv('#merchant', d.merchant);
                setv('#method', d.method);
                if (d.date) setv('#date', (d.date || '').slice(0, 16));
                setv('#account_id', d.account_id);
                setv('#card_id', d.card_id);
                setv('#category_id', d.category_id);
                setv('#subcategory_id', d.subcategory_id);
                setv('#installment_count', d.installment_count);
                setv('#installment_index', d.installment_index);
                setv('#notes', d.notes);

                if (typeof d.confidence === 'number') {
                    confVal.textContent = Math.round(d.confidence * 100) + '%';
                    confWrap.classList.remove('hidden');
                } else {
                    confWrap.classList.add('hidden');
                }

                methodHint.textContent = d.method ? ('Método sugerido: ' + d.method) : '';
                methodHint.classList.toggle('hidden', !d.method);

                if (Array.isArray(d.questions) && d.questions.length) {
                    qBox.textContent = d.questions.join(' • ');
                    qBox.classList.remove('hidden');
                } else qBox.classList.add('hidden');
            }

            // -------- TEXTO
            $('#btnText').addEventListener('click', async function() {
                console.log('[tx] clique: texto');
                setLoading('#btnText', '#loadText', true);
                try {
                    const text = $('#freeText').value.trim();
                    if (!text) {
                        alert('Digite algo.');
                        return;
                    }
                    const r = await fetch(API.text, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            text,
                            context: ctx
                        })
                    });
                    console.log('[tx] /parse-text status', r.status);
                    const j = await r.json();
                    if (j?.data) fillFromAI(j.data);
                    else {
                        console.warn('Resposta IA sem data', j);
                        alert('IA sem dados.');
                    }
                } catch (e) {
                    console.error(e);
                    alert('Falha ao usar IA (texto).');
                } finally {
                    setLoading('#btnText', '#loadText', false);
                }
            });

            // -------- ÁUDIO (segure para falar)
            const btnTalk = $('#btnHoldToTalk');
            let mediaRecorder = null,
                chunks = [];

            function canRecord() {
                const gum = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
                const mr = (typeof MediaRecorder !== 'undefined');
                return gum && mr;
            }

            async function startRec() {
                console.log('[tx] startRec');
                $('#recStatus').textContent = 'Gravando...';
                try {
                    if (!canRecord()) {
                        alert(
                            'Seu navegador não suporta gravação de áudio (requer HTTPS/localhost). Use Chrome/Edge atual.');
                        $('#recStatus').textContent = 'Sem suporte';
                        return;
                    }
                    const stream = await navigator.mediaDevices.getUserMedia({
                        audio: true
                    });
                    mediaRecorder = new MediaRecorder(stream);
                    chunks = [];
                    mediaRecorder.ondataavailable = e => chunks.push(e.data);
                    mediaRecorder.start();
                    btnTalk.classList.add('btn-active');
                } catch (e) {
                    console.error(e);
                    alert('Permita o microfone para gravar.');
                    $('#recStatus').textContent = 'Permissão negada';
                }
            }
            async function stopRec() {
                console.log('[tx] stopRec');
                $('#recStatus').textContent = 'Processando áudio...';
                btnTalk.classList.remove('btn-active');
                if (!mediaRecorder) return;
                mediaRecorder.stop();
                mediaRecorder.onstop = async () => {
                    try {
                        const blob = new Blob(chunks, {
                            type: 'audio/webm'
                        });
                        const fd = new FormData();
                        fd.append('audio', blob, 'note.webm');
                        fd.append('context', new Blob([JSON.stringify(ctx)], {
                            type: 'application/json'
                        }));
                        const r = await fetch(API.audio, {
                            method: 'POST',
                            body: fd
                        });
                        console.log('[tx] /parse-audio status', r.status);
                        const j = await r.json();
                        if (j?.data) fillFromAI(j.data);
                        else {
                            console.warn('Resposta IA sem data', j);
                            alert('IA sem dados (áudio).');
                        }
                        $('#recStatus').textContent = 'Pronto';
                    } catch (e) {
                        console.error(e);
                        alert('Falha ao usar IA (áudio).');
                    }
                };
            }

            // Eventos (touch + mouse)
            btnTalk.addEventListener('touchstart', startRec, {
                passive: true
            });
            btnTalk.addEventListener('mousedown', startRec);
            btnTalk.addEventListener('touchend', stopRec);
            btnTalk.addEventListener('mouseup', stopRec);
            // Segurança: se sair do botão, encerra
            btnTalk.addEventListener('mouseleave', () => {
                if (mediaRecorder && mediaRecorder.state === 'recording') stopRec();
            });

            // -------- IMAGEM
            $('#btnImage').addEventListener('click', async function() {
                console.log('[tx] clique: imagem');
                setLoading('#btnImage', '#loadImg', true);
                try {
                    const file = $('#fileImage').files?.[0];
                    if (!file) {
                        alert('Selecione uma imagem/recibo.');
                        return;
                    }
                    const fd = new FormData();
                    fd.append('image', file);
                    fd.append('context', new Blob([JSON.stringify(ctx)], {
                        type: 'application/json'
                    }));
                    const r = await fetch(API.image, {
                        method: 'POST',
                        body: fd
                    });
                    console.log('[tx] /parse-image status', r.status);
                    const j = await r.json();
                    if (j?.data) fillFromAI(j.data);
                    else {
                        console.warn('Resposta IA sem data', j);
                        alert('IA sem dados (imagem).');
                    }
                } catch (e) {
                    console.error(e);
                    alert('Falha ao usar IA (imagem).');
                } finally {
                    setLoading('#btnImage', '#loadImg', false);
                }
            });

            // -------- Filtro subcategoria por categoria
            $('#category_id').addEventListener('change', function() {
                const cat = this.value;
                const sub = $('#subcategory_id');
                sub.querySelectorAll('option').forEach(o => {
                    const need = o.getAttribute('data-cat');
                    o.hidden = (need && cat && need !== cat);
                });
            });

            // -------- Salvar
            $('#btnSave').addEventListener('click', async function() {
                console.log('[tx] clique: salvar');
                setLoading('#btnSave', '#loadSave', true);
                try {
                    const payload = {
                        client_id: window.__pageData.clientId,
                        amount: parseFloat($('#amount').value || 0),
                        type: $('#type').value || 'expense',
                        method: $('#method').value || null,
                        merchant: $('#merchant').value || null,
                        date: $('#date').value ? new Date($('#date').value).toISOString() : null,
                        account_id: $('#account_id').value ? parseInt($('#account_id').value) :
                            null,
                        card_id: $('#card_id').value ? parseInt($('#card_id').value) : null,
                        category_id: $('#category_id').value ? parseInt($('#category_id').value) :
                            null,
                        subcategory_id: $('#subcategory_id').value ? parseInt($('#subcategory_id')
                            .value) : null,
                        installment_count: $('#installment_count').value ? parseInt($(
                            '#installment_count').value) : null,
                        installment_index: $('#installment_index').value ? parseInt($(
                            '#installment_index').value) : null,
                        notes: $('#notes').value || null,
                    };

                    if (!payload.amount || isNaN(payload.amount)) {
                        alert('Informe um valor válido.');
                        return;
                    }
                    if (payload.method === 'credit_card' && !payload.card_id) {
                        alert('Selecione o cartão.');
                        return;
                    }

                    const r = await fetch(API.save, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    console.log('[tx] /transactions status', r.status);
                    const j = await r.json();
                    if (j?.ok) {
                        alert('Transação salva!');
                        window.location.reload();
                    } else {
                        console.error(j);
                        alert('Falha ao salvar.');
                    }
                } catch (e) {
                    console.error(e);
                    alert('Erro ao salvar.');
                } finally {
                    setLoading('#btnSave', '#loadSave', false);
                }
            });

        });
    </script>
@endsection
