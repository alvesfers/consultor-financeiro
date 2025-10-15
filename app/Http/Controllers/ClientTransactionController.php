<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Subcategory;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Http\Request;
use App\Models\Card;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class ClientTransactionController extends Controller
{
    /**
     * Armazena uma nova transação para o cliente.
     */
    public function store(Request $request, $consultant)
    {
        $user = $request->user();

        /** @var Client $client */
        $client = $user->client ?? Client::where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        $clientId = (int) $client->id;

        // 1) herda type de kind se necessário
        if ($request->filled('kind') && ! $request->filled('type')) {
            $request->merge(['type' => $request->input('kind')]);
        }

        // 2) validação
        $rules = [
            'date' => ['required', 'date'],
            'amount_abs' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', Rule::in(['income', 'expense', 'transfer', 'transfer_in', 'transfer_out'])],
            'method' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'mode' => ['nullable', Rule::in(['account', 'card'])],

            // recursos
            'account_id' => ['nullable', 'exists:accounts,id'],
            'card_id' => ['nullable', 'exists:cards,id'],

            // classif
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],

            // transferência
            'from_account_id' => ['nullable', 'exists:accounts,id'],
            'to_account_id' => ['nullable', 'exists:accounts,id'],

            // parcelado (cartão)
            'is_installment' => ['nullable', 'boolean'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:48'],
            'first_invoice_month' => ['nullable', 'date_format:Y-m'],
        ];

        $validator = \Validator::make($request->all(), $rules);
        $validator->after(function ($v) use ($request) {
            $t = $request->input('type');
            $mode = $request->input('mode');

            if ($t === 'transfer') {
                if (! $request->filled('from_account_id') || ! $request->filled('to_account_id')) {
                    $v->errors()->add('from_account_id', 'Origem e destino são obrigatórios.');
                } elseif ($request->input('from_account_id') == $request->input('to_account_id')) {
                    $v->errors()->add('to_account_id', 'Origem e destino não podem ser iguais.');
                }
                if ($request->filled('card_id')) {
                    $v->errors()->add('card_id', 'Cartão não é válido para transferência.');
                }
            } else {
                // expense/income
                if ($mode === 'card' || $request->filled('card_id')) {
                    if (! $request->filled('card_id')) {
                        $v->errors()->add('card_id', 'Selecione um cartão.');
                    }
                } else {
                    if (! $request->filled('account_id')) {
                        $v->errors()->add('account_id', 'Selecione uma conta.');
                    }
                }
            }

            // subcategoria deve pertencer à categoria
            if ($request->filled('subcategory_id') && $request->filled('category_id')) {
                $ok = Subcategory::where('id', $request->input('subcategory_id'))
                    ->where('category_id', $request->input('category_id'))
                    ->exists();
                if (! $ok) {
                    $v->errors()->add('subcategory_id', 'A subcategoria não pertence à categoria.');
                }
            }

            // regras de parcelado
            if ($t === 'expense' && ($mode === 'card' || $request->filled('card_id'))) {
                $isInstallment = (bool) $request->boolean('is_installment');
                $n = (int) $request->input('installments', 1);
                if ($isInstallment && $n < 2) {
                    $v->errors()->add('installments', 'Número de parcelas mínimo é 2.');
                }
            }
        });
        $validator->validate();
        $data = $validator->validated();

        // 3) normalização de valores
        $amountTotal = (float) $data['amount_abs'];
        $isExpense = $data['type'] === 'expense';
        $isIncome = $data['type'] === 'income';

        // 4) transferência (gera 2 lançamentos)
        if ($data['type'] === 'transfer') {
            $out = Transaction::create([
                'client_id' => $clientId,
                'account_id' => $data['from_account_id'],
                'card_id' => null,
                'date' => $data['date'],
                'amount' => -abs($amountTotal),
                'status' => Transaction::STATUS_CONFIRMED,
                'type' => 'transfer_out',
                'invoice_paid' => false,
                'method' => $data['method'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $in = Transaction::create([
                'client_id' => $clientId,
                'account_id' => $data['to_account_id'],
                'card_id' => null,
                'date' => $data['date'],
                'amount' => abs($amountTotal),
                'status' => Transaction::STATUS_CONFIRMED,
                'type' => 'transfer_in',
                'invoice_paid' => false,
                'method' => $data['method'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            return back()->with('success', 'Transferência registrada com sucesso!');
        }

        // 5) gasto/ganho: conta (simples) OU cartão (com/sem parcelas)
        $accountId = $data['account_id'] ?? null;
        $cardId = $data['card_id'] ?? null;

        // helper: calcula o invoice_month pelo ciclo do cartão
        $calcInvoiceMonth = function (Card $card, Carbon $purchaseDate): string {
            $y = (int) $purchaseDate->year;
            $m = (int) $purchaseDate->month;

            $closeThis = Carbon::create($y, $m, 1)->day(min($card->close_day, Carbon::create($y, $m, 1)->daysInMonth));
            // empurra p/ dia útil? se quiser, dá pra adaptar igual no dashboard

            if ($purchaseDate->lessThanOrEqualTo($closeThis)) {
                return $closeThis->format('Y-m');         // dentro do ciclo que fecha neste mês
            }
            $next = $purchaseDate->copy()->addMonth();
            $closeNext = Carbon::create($next->year, $next->month, 1)->day(min($card->close_day, Carbon::create($next->year, $next->month, 1)->daysInMonth));

            return $closeNext->format('Y-m');            // cai pra fatura que fecha no mês seguinte
        };

        // ===== Conta (não cartão) -> 1 lançamento simples
        if (empty($cardId)) {
            $amount = $isExpense ? -abs($amountTotal) : abs($amountTotal);

            $tx = Transaction::create([
                'client_id' => $clientId,
                'account_id' => $accountId,
                'card_id' => null,
                'date' => $data['date'],
                'amount' => $amount,
                'status' => Transaction::STATUS_CONFIRMED,
                'type' => $data['type'],
                'invoice_paid' => false,
                'invoice_month' => null,
                'method' => $data['method'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['category_id']) || ! empty($data['subcategory_id'])) {
                TransactionCategory::create([
                    'transaction_id' => $tx->id,
                    'client_id' => $clientId,
                    'category_id' => $data['category_id'] ?? null,
                    'subcategory_id' => $data['subcategory_id'] ?? null,
                ]);
            }

            return back()->with('success', 'Transação criada com sucesso!');
        }

        // ===== Cartão (com/sem parcelas)
        /** @var Card $card */
        $card = Card::where('id', $cardId)->where('client_id', $clientId)->firstOrFail();

        $purchaseDate = Carbon::parse($data['date']);
        $isInstallment = (bool) ($data['is_installment'] ?? false);
        $n = (int) ($data['installments'] ?? 1);
        if (! $isInstallment || $n < 2) {
            $n = 1;
        }

        // distribui valor por parcelas (resolvendo centavos)
        $base = floor(($amountTotal / $n) * 100) / 100; // duas casas
        $parcelas = array_fill(0, $n, $base);
        $diff = round($amountTotal - array_sum($parcelas), 2);
        if ($diff !== 0.0) {
            $parcelas[0] = round($parcelas[0] + $diff, 2);
        }

        // primeira fatura (opcional) enviada pelo form (YYYY-MM)
        $firstInvoiceMonth = $data['first_invoice_month'] ?? null;
        if ($n === 1) {
            $firstInvoiceMonth = null;
        } // 1x não precisa

        // parent (para agrupar parcelas)
        $parentId = null;

        for ($i = 1; $i <= $n; $i++) {
            // data “contábil” do lançamento = data da compra; mês de fatura = calculado
            $invoiceMonth = $firstInvoiceMonth
                ? Carbon::createFromFormat('Y-m', $firstInvoiceMonth)->copy()->addMonths($i - 1)->format('Y-m')
                : $calcInvoiceMonth($card, $purchaseDate->copy()->addMonths($i - 1));

            $amount = -abs($parcelas[$i - 1]); // cartão é despesa (negativo)

            $tx = Transaction::create([
                'client_id' => $clientId,
                'account_id' => null,
                'card_id' => $card->id,
                'date' => $purchaseDate->toDateTimeString(),
                'amount' => $amount,
                'status' => Transaction::STATUS_CONFIRMED,
                'type' => 'expense',
                'invoice_paid' => false,
                'invoice_month' => $invoiceMonth,
                'method' => $data['method'] ?? null,
                'notes' => $data['notes'] ?? null,
                'installment_count' => $n,
                'installment_index' => $i,
                'parent_transaction_id' => $parentId,
            ]);

            // define o parent na 1ª
            if ($i === 1 && $n > 1) {
                $parentId = $tx->id;
                $tx->parent_transaction_id = $parentId;
                $tx->save();
            }

            if (! empty($data['category_id']) || ! empty($data['subcategory_id'])) {
                TransactionCategory::create([
                    'transaction_id' => $tx->id,
                    'client_id' => $clientId,
                    'category_id' => $data['category_id'] ?? null,
                    'subcategory_id' => $data['subcategory_id'] ?? null,
                ]);
            }
        }

        return back()->with('success', $n > 1 ? 'Compra parcelada registrada!' : 'Transação criada com sucesso!');
    }
}
