{{-- resources/views/client/invoices/show.blade.php --}}
@extends('layouts.app')

@section('content')
    @php
        // Helpers
        function money_br($v)
        {
            return 'R$ ' . number_format((float) $v, 2, ',', '.');
        }

        // Parâmetros de rota (evita precisar receber explicitamente do controller)
        $consultantId = request()->route('consultant');
        $invoiceId = request()->route('invoice'); // id sintético (CRC32)
        $monthKey = $monthKey ?? null; // 'YYYY-MM'

        // Metadados do cartão (tentamos descobrir a partir do join; fallback seguro)
        $first = $transactions[0] ?? null;
        $cardName = $first->card_name ?? ($first->name ?? 'Cartão');
        $cardLast4 = $first->card_last4 ?? ($first->last4 ?? null);

        // Totais (coleção pode vir como array stdClass)
        $txs = collect($transactions);

        $totalPurchases = $txs->reduce(function ($acc, $t) {
            return $acc + ((float) $t->amount < 0 ? abs((float) $t->amount) : 0);
        }, 0.0);

        $totalCredits = $txs->reduce(function ($acc, $t) {
            return $acc + ((float) $t->amount > 0 ? (float) $t->amount : 0);
        }, 0.0);

        $remaining = $totalPurchases - $totalCredits;

        $unpaidCount = $txs->reduce(function ($acc, $t) {
            $isDebit = (float) $t->amount < 0;
            $paid = (int) ($t->invoice_paid ?? 0) === 1;
            return $acc + ($isDebit && !$paid ? 1 : 0);
        }, 0);

        $status = $unpaidCount === 0 ? 'paid' : 'open';

        $statusBadge = function (string $s) {
            return match ($s) {
                'paid'
                    => '<span class="badge badge-success gap-1"><i class="fa-solid fa-circle-check text-xs"></i> paga</span>',
                'overdue'
                    => '<span class="badge badge-error gap-1"><i class="fa-solid fa-triangle-exclamation text-xs"></i> vencida</span>',
                default
                    => '<span class="badge badge-warning gap-1"><i class="fa-regular fa-clock text-xs"></i> em aberto</span>',
            };
        };

        // Rótulos
        $monthLabel = $monthKey ?: $first?->invoice_month ?? '';
    @endphp

    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex items-start gap-3">
                <div class="avatar placeholder">
                    <div class="bg-neutral text-neutral-content rounded-full w-12">
                        <span class="text-lg"><i class="fa-solid fa-credit-card"></i></span>
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">
                        {{ $cardName }} {!! $cardLast4 ? '<span class="opacity-60 font-normal"> •••• ' . $cardLast4 . '</span>' : '' !!}
                    </h1>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="badge badge-outline"><i
                                class="fa-regular fa-calendar mr-1"></i>{{ $monthLabel }}</span>
                        {!! $statusBadge($status) !!}
                    </div>
                </div>
            </div>

            {{-- Ações principais --}}
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('client.invoices.index', ['consultant' => $consultantId]) }}" class="btn">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Voltar
                </a>

                @if ($status !== 'paid')
                    <form method="POST"
                        action="{{ route('client.invoices.markPaid', ['consultant' => $consultantId, 'invoice' => $invoiceId]) }}"
                        onsubmit="return confirm('Marcar esta fatura como paga?')">
                        @csrf
                        <button type="submit" class="btn btn-outline">
                            <i class="fa-solid fa-check mr-1"></i> Marcar paga
                        </button>
                    </form>

                    <details class="dropdown dropdown-end">
                        <summary class="btn btn-primary">
                            <i class="fa-solid fa-money-bill-wave mr-1"></i> Pagar
                        </summary>
                        <div class="dropdown-content z-[1] card card-compact bg-base-100 shadow p-3 w-72 mt-2">
                            <form method="POST"
                                action="{{ route('client.invoices.pay', ['consultant' => $consultantId, 'invoice' => $invoiceId]) }}"
                                onsubmit="return confirm('Confirmar pagamento da fatura {{ $monthLabel }} de {{ $cardName }}?')">
                                @csrf
                                <label class="label">
                                    <span class="label-text text-xs">Data do pagamento (opcional)</span>
                                </label>
                                <input type="date" name="date" class="input input-bordered w-full mb-3">
                                <button type="submit" class="btn btn-primary w-full">Confirmar pagamento</button>
                            </form>
                            <div class="mt-2 text-xs opacity-70">
                                Será lançado um débito na conta de pagamento vinculada ao cartão e todas as compras do ciclo
                                serão marcadas como pagas.
                            </div>
                        </div>
                    </details>
                @endif
            </div>
        </div>

        {{-- Resumo --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="stats shadow bg-base-100">
                <div class="stat">
                    <div class="stat-title">Total de compras</div>
                    <div class="stat-value text-base md:text-xl">{{ money_br($totalPurchases) }}</div>
                </div>
            </div>
            <div class="stats shadow bg-success/10">
                <div class="stat">
                    <div class="stat-title">Créditos / estornos</div>
                    <div class="stat-value text-base md:text-xl">{{ money_br($totalCredits) }}</div>
                </div>
            </div>
            <div class="stats shadow {{ $remaining > 0 ? 'bg-warning/10' : 'bg-success/10' }}">
                <div class="stat">
                    <div class="stat-title">Restante</div>
                    <div class="stat-value text-base md:text-xl {{ $remaining > 0 ? 'text-error' : '' }}">
                        {{ money_br($remaining) }}
                    </div>
                    <div class="stat-desc">{{ $unpaidCount }} compra(s)
                        {{ $unpaidCount === 1 ? 'em aberto' : 'em aberto' }}</div>
                </div>
            </div>
        </div>

        {{-- Lista de transações --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                @if ($txs->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 110px;">Data</th>
                                    <th>Descrição</th>
                                    <th class="text-center">Método</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($txs->sortBy('date') as $t)
                                    @php
                                        $isDebit = (float) $t->amount < 0;
                                        $paid = (int) ($t->invoice_paid ?? 0) === 1;

                                        $method = $t->method ?? '—';
                                        $notes = $t->notes ?? null;

                                        $rowBg = $isDebit ? ($paid ? 'bg-success/5' : 'bg-warning/5') : 'bg-base-100'; // créditos ficam neutros

                                        $statusCell = $isDebit
                                            ? ($paid
                                                ? '<span class="badge badge-success gap-1"><i class="fa-solid fa-check text-xs"></i> pago</span>'
                                                : '<span class="badge badge-warning gap-1"><i class="fa-regular fa-clock text-xs"></i> em aberto</span>')
                                            : '<span class="badge badge-ghost gap-1"><i class="fa-solid fa-arrow-rotate-left text-xs"></i> crédito</span>';
                                    @endphp
                                    <tr class="{{ $rowBg }}">
                                        <td>{{ \Carbon\Carbon::parse($t->date)->format('d/m/Y') }}</td>
                                        <td>
                                            @if ($notes)
                                                <div class="font-medium">{{ $notes }}</div>
                                            @else
                                                <div class="font-medium opacity-70">—</div>
                                            @endif
                                            <div class="text-xs opacity-60">
                                                @php
                                                    // informação auxiliar opcional
                                                    $extra = [];
                                                    if (!empty($t->type)) {
                                                        $extra[] = $t->type;
                                                    }
                                                    if (
                                                        !empty($t->installment_count) &&
                                                        !empty($t->installment_index)
                                                    ) {
                                                        $extra[] = "{$t->installment_index}/{$t->installment_count}";
                                                    }
                                                    echo implode(' · ', $extra);
                                                @endphp
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-outline">{{ $method }}</span>
                                        </td>
                                        <td class="text-center">{!! $statusCell !!}</td>
                                        <td
                                            class="text-right {{ $isDebit ? 'text-error font-semibold' : 'text-success' }}">
                                            {{ money_br(abs((float) $t->amount)) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3"></th>
                                    <th class="text-right">Totais</th>
                                    <th class="text-right">
                                        {{-- Mostra total líquido como info adicional --}}
                                        <div class="text-xs opacity-70">Compras: {{ money_br($totalPurchases) }}</div>
                                        <div class="text-xs opacity-70">Créditos: {{ money_br($totalCredits) }}</div>
                                        <div class="mt-1 {{ $remaining > 0 ? 'text-error font-semibold' : 'opacity-70' }}">
                                            Restante: {{ money_br($remaining) }}
                                        </div>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="py-10 text-center">
                        <div class="text-5xl mb-3">📭</div>
                        <h3 class="text-lg font-semibold">Sem transações neste ciclo</h3>
                        <p class="opacity-70">Volte para a lista de faturas para escolher outro período.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
