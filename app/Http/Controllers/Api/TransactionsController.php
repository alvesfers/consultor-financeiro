<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Card;

class TransactionsController extends Controller
{
    public function store(Request $req)
    {
        $data = $req->validate([
            'client_id' => ['required','integer'],
            'amount'    => ['required','numeric'],
            'type'      => ['required','in:expense,income,transfer,adjustment'],
            'method'    => ['nullable','in:cash,debit,credit_card,pix,transfer,boleto,adjustment'],
            'merchant'  => ['nullable','string','max:190'],
            'date'      => ['nullable','date'],

            'account_id'     => ['nullable','integer'],
            'card_id'        => ['nullable','integer'],
            'category_id'    => ['nullable','integer'],
            'subcategory_id' => ['nullable','integer'],

            'installment_count' => ['nullable','integer','min:1'],
            'installment_index' => ['nullable','integer','min:1'],
            'notes' => ['nullable','string','max:1000'],
        ]);

        $date = isset($data['date']) ? Carbon::parse($data['date']) : now();

        // Sinal do valor conforme type
        $amount = (float) $data['amount'];
        if ($data['type'] === 'expense' && $amount > 0) $amount = -$amount;
        if ($data['type'] === 'income'  && $amount < 0) $amount =  abs($amount);

        // Preferência de id: crédito => card_id, senão account_id
        if (($data['method'] ?? null) === 'credit_card') {
            $data['account_id'] = null;
        } else {
            $data['card_id'] = $data['card_id'] ?? null; // pode ficar null
        }

        // Calcula invoice_month se tiver cartão e close_day
        $invoiceMonth = null;
        if (($data['method'] ?? null) === 'credit_card' && !empty($data['card_id'])) {
            $card = Card::find($data['card_id']);
            if ($card && $card->close_day) {
                $invoiceMonth = $this->invoiceMonth($date, (int)$card->close_day)->format('Y-m');
            }
        }

        $tx = new Transaction();
        $tx->client_id = (int)$data['client_id'];
        $tx->account_id = $data['account_id'] ?? null;
        $tx->card_id    = $data['card_id'] ?? null;
        $tx->invoice_month = $invoiceMonth;           // nullable
        $tx->date       = $date;
        $tx->amount     = $amount;
        $tx->installment_count = $data['installment_count'] ?? 0;
        $tx->installment_index = $data['installment_index'] ?? 0;
        $tx->status     = 'confirmed';
        $tx->type       = $data['type'];
        $tx->invoice_paid = 0;                        // ⚠️ obrigatório na sua tabela
        $tx->method     = $data['method'] ?? null;
        $tx->notes      = $data['notes'] ?? ($data['merchant'] ?? null);
        $tx->parent_transaction_id = null;
        $tx->save();

        // transaction_categories (1 linha por transação)
        if (!empty($data['category_id']) || !empty($data['subcategory_id'])) {
            $tc = new TransactionCategory();
            $tc->transaction_id = $tx->id;
            $tc->category_id    = $data['category_id'] ?? null;
            $tc->subcategory_id = $data['subcategory_id'] ?? null;
            $tc->save();
        }

        return response()->json(['ok'=>true,'transaction_id'=>$tx->id]);
    }

    private function invoiceMonth(Carbon $date, int $closeDay): Carbon
    {
        $d = $date->copy();
        return $d->day <= $closeDay
            ? Carbon::create($d->year, $d->month, 1)
            : Carbon::create($d->year, $d->month, 1)->addMonth();
    }
}
