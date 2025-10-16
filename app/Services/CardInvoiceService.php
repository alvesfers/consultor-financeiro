<?php

// app/Services/CardInvoiceService.php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CardInvoiceService
{
    public function rebuildForClient(int $clientId): void
    {
        $cards = DB::table('cards')
            ->where('client_id', $clientId)
            ->get(['id', 'client_id', 'payment_account_id', 'close_day', 'due_day']);

        foreach ($cards as $c) {
            $this->rebuildForCard($c);
        }
    }

    public function rebuildForCard(object $card): void
    {
        // pega meses existentes no histórico de transações desse cartão
        $months = DB::table('transactions as t')
            ->where('t.client_id', $card->client_id)
            ->where('t.card_id', $card->id)
            ->selectRaw("DATE_FORMAT(COALESCE(t.invoice_month, t.`date`), '%Y-%m-01') as month_ref")
            ->groupBy('month_ref')
            ->pluck('month_ref');

        foreach ($months as $monthRef) {
            $this->upsertInvoiceForMonth($card, Carbon::parse($monthRef)->startOfMonth());
        }

        // garante ciclo corrente (mês atual) criado/atualizado
        $this->upsertInvoiceForMonth($card, now()->startOfMonth());
    }

    public function upsertInvoiceForMonth(object $card, Carbon $monthRef): void
    {
        // ciclo = (dia 1 do mêsRef anterior + close_day + 1) até (mêsRef + close_day)
        // fatura "YYYY-MM" representa compras ENTRE o fechamento anterior e o fechamento atual
        $closeDay = max(1, min(28, (int) ($card->close_day ?? 28)));
        $dueDay = max(1, min(28, (int) ($card->due_day ?? 10)));

        $cycleEnd = $monthRef->copy()->day($closeDay);
        $cycleStart = $monthRef->copy()->subMonth()->day($closeDay)->addDay(); // +1 dia após o fechamento anterior

        // due_on no mêsRef, limitado ao fim do mês
        $dueOn = $monthRef->copy()->day(min($dueDay, $monthRef->daysInMonth));

        // cria/atualiza header
        $invoiceId = DB::table('card_invoices')->updateOrInsert(
            ['card_id' => $card->id, 'month_ref' => $monthRef->toDateString()],
            [
                'client_id' => $card->client_id,
                'cycle_start' => $cycleStart->toDateString(),
                'cycle_end' => $cycleEnd->toDateString(),
                'due_on' => $dueOn->toDateString(),
                'updated_at' => now(), 'created_at' => now(),
            ]
        );

        // pega id (updateOrInsert não retorna id; recupere)
        $invoice = DB::table('card_invoices')
            ->where('card_id', $card->id)
            ->whereDate('month_ref', $monthRef)
            ->first();

        if (! $invoice) {
            return;
        }

        // vincula transações daquele ciclo à fatura
        DB::table('transactions')
            ->where('client_id', $card->client_id)
            ->where('card_id', $card->id)
            ->whereBetween('date', [$cycleStart->toDateString(), $cycleEnd->toDateString()])
            ->update(['invoice_id' => $invoice->id]);

        // total (despesa = amount < 0), pago (crédito = amount > 0)
        $sums = DB::table('transactions')
            ->where('invoice_id', $invoice->id)
            ->selectRaw('
               SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END) as purchases,
               SUM(CASE WHEN amount > 0 THEN  amount ELSE 0 END) as credits
            ')
            ->first();

        $total = (float) ($sums->purchases ?? 0);
        $paid = (float) ($sums->credits ?? 0);
        $rem = max(0, $total - $paid);

        // status
        $status = 'open';
        $closedAt = null;
        $paidAt = null;
        if (now()->toDateString() > $cycleEnd->toDateString()) {
            $status = 'closed';
            $closedAt = now();
        }
        if ($rem <= 0 && $total > 0) {
            $status = 'paid';
            $paidAt = now();
        } elseif ($rem > 0 && now()->toDateString() > $dueOn->toDateString()) {
            $status = 'overdue';
        }

        DB::table('card_invoices')
            ->where('id', $invoice->id)
            ->update([
                'total_amount' => $total,
                'paid_amount' => $paid,
                'remaining_amount' => $rem,
                'status' => $status,
                'closed_at' => $closedAt,
                'paid_at' => $paidAt,
                'updated_at' => now(),
            ]);
    }
}
