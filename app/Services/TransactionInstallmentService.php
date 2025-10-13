<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionInstallmentService
{
    /**
     * Cria transações parceladas numa compra de cartão.
     *
     * @param  array{
     *   client_id:int, card_id:int, date:string|Carbon, amount:float, notes?:string,
     *   method?:string, category_id?:int|null, subcategory_id?:int|null,
     *   installment_count:int
     * } $payload
     * @return array{parent:Transaction, children:\Illuminate\Support\Collection<Transaction>}
     */
    public function createCardInstallments(array $payload): array
    {
        $card = Card::findOrFail($payload['card_id']);
        $clientId = (int) $payload['client_id'];
        $count = max(1, (int) $payload['installment_count']);
        $purchaseDate = $payload['date'] instanceof Carbon ? $payload['date']->copy() : Carbon::parse($payload['date']);

        // Valor total é negativo (gasto); cada parcela divide igualmente (arredonda na última)
        $total = (float) $payload['amount'];
        if ($total > 0) {
            $total = -$total;
        }

        $per = round($total / $count, 2);
        $children = collect();

        return DB::transaction(function () use ($payload, $card, $clientId, $count, $purchaseDate, $total, $per, $children) {
            // Transação "mãe" apenas como marcador (sem impactar fatura); útil para rastrear a venda original
            $parent = Transaction::create([
                'client_id' => $clientId,
                'account_id' => null,
                'card_id' => $card->id,
                'date' => $purchaseDate,
                'amount' => 0,
                'status' => 'confirmed',
                'method' => $payload['method'] ?? 'credit',
                'notes' => trim(($payload['notes'] ?? '').' (compra parcelada)'),
                'installment_count' => $count,
            ]);

            // Mês da PRIMEIRA parcela
            $firstInvoiceMonth = $card->invoiceMonthForPurchase($purchaseDate);

            // Cria N parcelas, uma por mês, setando invoice_month adequadamente
            for ($i = 1; $i <= $count; $i++) {
                $im = Carbon::createFromFormat('Y-m', $firstInvoiceMonth)->addMonths($i - 1);
                $invoiceMonth = $card->closeDateForMonth((int) $im->year, (int) $im->month)->format('Y-m');

                // Ajusta centavos residuais na ÚLTIMA parcela
                $amount = ($i === $count)
                    ? round($total - $per * ($count - 1), 2)
                    : $per;

                $t = Transaction::create([
                    'client_id' => $clientId,
                    'account_id' => null,
                    'card_id' => $card->id,
                    // data “contábil” da parcela: usar o próprio fechamento do mês de fatura (ou 1º dia do ciclo)? Abaixo uso o fechamento p/ deixar óbvio.
                    'date' => $card->closeDateForMonth((int) $im->year, (int) $im->month),
                    'amount' => $amount,
                    'status' => 'confirmed',
                    'method' => $payload['method'] ?? 'credit',
                    'notes' => trim(($payload['notes'] ?? '')." ({$i}/{$count})"),
                    'installment_count' => $count,
                    'installment_index' => $i,
                    'parent_transaction_id' => $parent->id,
                    'invoice_month' => $invoiceMonth,
                ]);

                // (Opcional) Categoria 1:1
                if (! empty($payload['category_id']) || ! empty($payload['subcategory_id'])) {
                    $t->category()->create([
                        'category_id' => $payload['category_id'] ?? null,
                        'subcategory_id' => $payload['subcategory_id'] ?? null,
                    ]);
                }

                $children->push($t);
            }

            return ['parent' => $parent, 'children' => $children];
        });
    }
}
