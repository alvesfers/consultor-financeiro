<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Client;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CardInvoiceController extends Controller
{
    public function pay(Request $request, $consultant, Card $card, string $invoiceMonth)
    {
        $user = $request->user();

        /** @var Client $client */
        $client = Client::where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        abort_unless($card->client_id === $client->id, 403, 'Cartão inválido.');

        if (! $card->payment_account_id) {
            return back()->withErrors('Defina a conta de pagamento para este cartão.')->withInput();
        }

        $total = (float) DB::table('transactions')
            ->where('client_id', $client->id)
            ->where('card_id', $card->id)
            ->where('invoice_month', $invoiceMonth)
            ->sum('amount');

        $totalAbs = abs(min(0, $total));
        if ($totalAbs <= 0) {
            return back()->with('success', 'Não há valor a pagar nesta fatura.');
        }

        $payDate = $request->date
            ? Carbon::parse($request->date)
            : $card->dueDateForMonth((int) substr($invoiceMonth, 0, 4), (int) substr($invoiceMonth, 5, 2));

        DB::transaction(function () use ($client, $card, $invoiceMonth, $totalAbs, $payDate) {
            Transaction::create([
                'client_id' => $client->id,
                'account_id' => $card->payment_account_id,
                'card_id' => null,
                'date' => $payDate,
                'amount' => -$totalAbs,
                'status' => 'confirmed',
                'method' => 'credit_card_invoice',
                'notes' => "Pagamento fatura {$card->name} ({$invoiceMonth})",
            ]);

            Transaction::where('client_id', $client->id)
                ->where('card_id', $card->id)
                ->where('invoice_month', $invoiceMonth)
                ->update(['invoice_paid' => true]);
        });

        return back()->with('success', "Fatura {$invoiceMonth} paga com sucesso!");
    }
}
