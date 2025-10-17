<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $clientId = 1; // <<< SOMENTE CLIENTE 1
        $now = Carbon::now('America/Sao_Paulo');

        // Cartões do cliente com conta de pagamento definida
        $cards = DB::table('cards')
            ->where('client_id', $clientId)
            ->whereNotNull('payment_account_id')
            ->get(['id', 'name', 'due_day', 'payment_account_id']);

        foreach ($cards as $card) {
            // Meses de fatura existentes (pelo invoice_month)
            $months = DB::table('transactions')
                ->where('client_id', $clientId)
                ->where('card_id', $card->id)
                ->whereNotNull('invoice_month')
                ->selectRaw('invoice_month')
                ->groupBy('invoice_month')
                ->pluck('invoice_month');

            foreach ($months as $invoiceMonth) {
                // Verifica se todas as COMPRAS (<0) do ciclo estão marcadas como pagas
                $unpaidDebits = DB::table('transactions')
                    ->where('client_id', $clientId)
                    ->where('card_id', $card->id)
                    ->where('invoice_month', $invoiceMonth)
                    ->where('amount', '<', 0)
                    ->where(function ($q) {
                        $q->whereNull('invoice_paid')->orWhere('invoice_paid', 0);
                    })
                    ->count();

                if ($unpaidDebits > 0) {
                    continue; // ainda tem compra em aberto; não tratamos nessa seed
                }

                // Totais do ciclo no cartão (para calcular o "restante")
                $tot = DB::table('transactions')
                    ->where('client_id', $clientId)
                    ->where('card_id', $card->id)
                    ->where('invoice_month', $invoiceMonth)
                    ->selectRaw('
                        SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END) as purchases,
                        SUM(CASE WHEN amount > 0 THEN  amount ELSE 0 END) as credits
                    ')
                    ->first();

                $purchases = (float) ($tot->purchases ?? 0);
                $credits = (float) ($tot->credits ?? 0);
                $remaining = max(0, $purchases - $credits);

                if ($remaining <= 0) {
                    continue; // já está zerado
                }

                // Data padrão do pagamento: dia de vencimento no mês da fatura
                $dueDay = $card->due_day ?: 10;
                $payDate = Carbon::parse($invoiceMonth.'-'.str_pad((string) $dueDay, 2, '0', STR_PAD_LEFT))
                    ->startOfDay()
                    ->toDateTimeString();

                // Idempotência: se já aplicamos pagamento/ajuste/crédito desse mês, pula
                $alreadyApplied = Transaction::where('client_id', $clientId)
                    ->where(function ($q) use ($card, $invoiceMonth) {
                        $q->where(function ($q2) use ($card, $invoiceMonth) {
                            $q2->whereNull('card_id')
                                ->where('method', 'credit_card_invoice')
                                ->where('notes', 'like', "%{$card->name}%")
                                ->where('notes', 'like', "%{$invoiceMonth}%");
                        })->orWhere(function ($q3) use ($card, $invoiceMonth) {
                            $q3->where('card_id', $card->id)
                                ->where('method', 'credit_card_statement_payment')
                                ->where('invoice_month', $invoiceMonth);
                        });
                    })
                    ->exists();

                if ($alreadyApplied) {
                    continue;
                }

                DB::transaction(function () use ($clientId, $card, $invoiceMonth, $remaining, $payDate, $now) {

                    // (A) AJUSTE POSITIVO NA CONTA (repõe o saldo)  -> invoice_paid = 0 (NOT NULL)
                    Transaction::create([
                        'client_id' => $clientId,
                        'account_id' => $card->payment_account_id,
                        'card_id' => null,
                        'invoice_month' => null,
                        'date' => $payDate,
                        'amount' => $remaining,
                        'status' => 'confirmed',
                        'type' => null,
                        'invoice_paid' => 0,
                        'method' => 'adjustment_seed',
                        'notes' => "Ajuste histórico para pagamento fatura {$card->name} ({$invoiceMonth})",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    // (B) PAGAMENTO DA FATURA NA CONTA (saída)  -> invoice_paid = 0 (NOT NULL)
                    Transaction::create([
                        'client_id' => $clientId,
                        'account_id' => $card->payment_account_id,
                        'card_id' => null,
                        'invoice_month' => null,
                        'date' => $payDate,
                        'amount' => -$remaining,
                        'status' => 'confirmed',
                        'type' => null,
                        'invoice_paid' => 0,
                        'method' => 'credit_card_invoice',
                        'notes' => "Pagamento fatura {$card->name} ({$invoiceMonth})",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    // (C) CRÉDITO NA FATURA DO CARTÃO (zera o Restante) -> invoice_paid = 1
                    Transaction::create([
                        'client_id' => $clientId,
                        'account_id' => null,
                        'card_id' => $card->id,
                        'invoice_month' => $invoiceMonth,
                        'date' => $payDate,
                        'amount' => $remaining,
                        'status' => 'confirmed',
                        'type' => null,
                        'invoice_paid' => 1,
                        'method' => 'credit_card_statement_payment',
                        'notes' => "Aplicação de pagamento na fatura ({$invoiceMonth})",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    // (D) Por segurança, garante compras marcadas como pagas
                    DB::table('transactions')
                        ->where('client_id', $clientId)
                        ->where('card_id', $card->id)
                        ->where('invoice_month', $invoiceMonth)
                        ->where('amount', '<', 0)
                        ->update(['invoice_paid' => 1]);
                });
            }
        }

        $this->command->info('Cliente 1: faturas históricas quitadas sem alterar o saldo líquido e zerando o "Restante".');
    }
}
