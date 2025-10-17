<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $clientId = 1;
        $cardId = 3;

        // Helper para inserir transações + vincular categoria/subcategoria
        $addTx = function (array $tx, ?array $cat = null) use ($now) {
            $tx['installment_count'] = $tx['installment_count'] ?? 0;
            $tx['installment_index'] = $tx['installment_index'] ?? 0;
            $tx['created_at'] = $now;
            $tx['updated_at'] = $now;

            $id = DB::table('transactions')->insertGetId($tx);

            if ($cat) {
                DB::table('transaction_categories')->insert([
                    'transaction_id' => $id,
                    'category_id' => $cat['category_id'] ?? null,
                    'subcategory_id' => $cat['subcategory_id'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $id;
        };

        DB::beginTransaction();

        // =====================================================================================
        // NOVEMBRO/2025  (invoice_month = 2025-11)  — valor no app: 1.454,15
        // =====================================================================================
        $ym = '2025-11';
        $d = fn (int $day) => Carbon::create(2025, 11, min($day, 28))->toDateString();

        // Parcelas que já existiam antes (sem parent_transaction_id aqui)
        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -489.24, 'installment_count' => 12, 'installment_index' => 10,
            'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'SENAC Web (10/12)',
        ], ['category_id' => 10, 'subcategory_id' => 46]); // Educação > Curso

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -158.25, 'installment_count' => 12, 'installment_index' => 8,
            'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Loja TIM (8/12)',
        ], ['category_id' => 11, 'subcategory_id' => 69]); // Governo > Telefones

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -162.23, 'installment_count' => 12, 'installment_index' => 8,
            'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'PITZI (8/12)',
        ], ['category_id' => 11, 'subcategory_id' => 69]); // Governo > Telefones

        // PIX crédito (4/4) – última
        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(6),
            'amount' => -368.96, 'installment_count' => 4, 'installment_index' => 4,
            'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'PIX Crédito (4/4)',
        ], ['category_id' => 13, 'subcategory_id' => 123]); // Impostos > Empréstimos

        // Compras avulsas
        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(7),
            'amount' => -82.87, 'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'CS GO Skins',
        ], ['category_id' => 14, 'subcategory_id' => 140]); // Lazer > Diversão

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(7),
            'amount' => -25.00, 'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Atali Grazieli',
        ], ['category_id' => 18, 'subcategory_id' => 221]); // Outros > Checar

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(7),
            'amount' => -5.95, 'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'iFood',
        ], ['category_id' => 9, 'subcategory_id' => 40]); // Alimentação > Restaurante

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(7),
            'amount' => -2.90, 'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'IOF Nacional',
        ], ['category_id' => 13, 'subcategory_id' => 125]); // Impostos > IOF

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(8),
            'amount' => -21.90, 'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Gamers Club',
        ], ['category_id' => 14, 'subcategory_id' => 140]); // Lazer > Diversão

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(8),
            'amount' => -100.00, 'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Abastecimento carro',
        ], ['category_id' => 16, 'subcategory_id' => 189]); // Veículo > Gasolina

        // INÍCIO de novas parcelas em novembro (vão gerar parent_transaction_id para as próximas)
        $celularParentId = $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(9),
            'amount' => -239.07, 'installment_count' => 10, 'installment_index' => 1,
            'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Celular Fernando (1/10)',
        ], ['category_id' => 11, 'subcategory_id' => 69]); // Governo > Telefones

        $zooParentId = $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(9),
            'amount' => -107.88, 'installment_count' => 5, 'installment_index' => 1,
            'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Zoológico (1/5)',
        ], ['category_id' => 14, 'subcategory_id' => 140]); // Lazer > Diversão

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(10),
            'amount' => -189.90, 'status' => 'confirmed', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Barbearia',
        ], ['category_id' => 15, 'subcategory_id' => 152]); // Saúde > Barbearia

        // Ajuste para bater com o valor do APP (1.454,15)
        // Soma dos itens = 1.791,92 → ajuste -337,77
        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(28),
            'amount' => +337.77, // crédito na fatura (reduz total)
            'status' => 'confirmed', 'type' => 'income', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Ajuste APP novembro (diferença)',
        ], ['category_id' => 18, 'subcategory_id' => 221]); // Outros > Checar

        // =====================================================================================
        // DEZEMBRO/2025  (invoice_month = 2025-12) — total a bater no app: 1.156,60
        // =====================================================================================
        $ym = '2025-12';
        $d = fn (int $day) => Carbon::create(2025, 12, min($day, 28))->toDateString();

        // Continuação das parcelas antigas (sem parent aqui)
        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -489.24, 'installment_count' => 12, 'installment_index' => 11,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'SENAC Web (11/12)',
        ], ['category_id' => 10, 'subcategory_id' => 46]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -158.25, 'installment_count' => 12, 'installment_index' => 9,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Loja TIM (9/12)',
        ], ['category_id' => 11, 'subcategory_id' => 69]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -162.23, 'installment_count' => 12, 'installment_index' => 9,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'PITZI (9/12)',
        ], ['category_id' => 11, 'subcategory_id' => 69]);

        // Celular / Zoológico (usando parent_transaction_id da 1ª parcela de novembro)
        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(9),
            'amount' => -239.07, 'installment_count' => 10, 'installment_index' => 2,
            'parent_transaction_id' => $celularParentId,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Celular Fernando (2/10)',
        ], ['category_id' => 11, 'subcategory_id' => 69]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(9),
            'amount' => -107.88, 'installment_count' => 5, 'installment_index' => 2,
            'parent_transaction_id' => $zooParentId,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Zoológico (2/5)',
        ], ['category_id' => 14, 'subcategory_id' => 140]);

        // Ajuste centavos para bater 1.156,60 (soma dá 1.156,67)
        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(28),
            'amount' => +0.07, // crédito
            'status' => 'pending', 'type' => 'income', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Ajuste APP dezembro (-0,07)',
        ], ['category_id' => 18, 'subcategory_id' => 221]);

        // =====================================================================================
        // JANEIRO/2026  (invoice_month = 2026-01) — total a bater: 1.156,60
        // =====================================================================================
        $ym = '2026-01';
        $d = fn (int $day) => Carbon::create(2026, 1, min($day, 28))->toDateString();

        // SENAC (12/12) – última
        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -489.24, 'installment_count' => 12, 'installment_index' => 12,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'SENAC Web (12/12)',
        ], ['category_id' => 10, 'subcategory_id' => 46]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -158.25, 'installment_count' => 12, 'installment_index' => 10,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Loja TIM (10/12)',
        ], ['category_id' => 11, 'subcategory_id' => 69]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -162.23, 'installment_count' => 12, 'installment_index' => 10,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'PITZI (10/12)',
        ], ['category_id' => 11, 'subcategory_id' => 69]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(9),
            'amount' => -239.07, 'installment_count' => 10, 'installment_index' => 3,
            'parent_transaction_id' => $celularParentId,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Celular Fernando (3/10)',
        ], ['category_id' => 11, 'subcategory_id' => 69]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(9),
            'amount' => -107.88, 'installment_count' => 5, 'installment_index' => 3,
            'parent_transaction_id' => $zooParentId,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Zoológico (3/5)',
        ], ['category_id' => 14, 'subcategory_id' => 140]);

        // Ajuste centavos para bater 1.156,60 (soma dá 1.156,67)
        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(28),
            'amount' => +0.07, // crédito
            'status' => 'pending', 'type' => 'income', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Ajuste APP janeiro (-0,07)',
        ], ['category_id' => 18, 'subcategory_id' => 221]);

        // =====================================================================================
        // FEVEREIRO/2026  (invoice_month = 2026-02) — total a bater: 667,36
        // =====================================================================================
        $ym = '2026-02';
        $d = fn (int $day) => Carbon::create(2026, 2, min($day, 28))->toDateString();

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -158.25, 'installment_count' => 12, 'installment_index' => 11,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Loja TIM (11/12)',
        ], ['category_id' => 11, 'subcategory_id' => 69]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -162.23, 'installment_count' => 12, 'installment_index' => 11,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'PITZI (11/12)',
        ], ['category_id' => 11, 'subcategory_id' => 69]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(9),
            'amount' => -239.07, 'installment_count' => 10, 'installment_index' => 4,
            'parent_transaction_id' => $celularParentId,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Celular Fernando (4/10)',
        ], ['category_id' => 11, 'subcategory_id' => 69]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(9),
            'amount' => -107.88, 'installment_count' => 5, 'installment_index' => 4,
            'parent_transaction_id' => $zooParentId,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Zoológico (4/5)',
        ], ['category_id' => 14, 'subcategory_id' => 140]);

        // Ajuste centavos para bater 667,36 (soma dá 667,43)
        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(28),
            'amount' => +0.07, // crédito
            'status' => 'pending', 'type' => 'income', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Ajuste APP fevereiro (-0,07)',
        ], ['category_id' => 18, 'subcategory_id' => 221]);

        // =====================================================================================
        // MARÇO/2026  (invoice_month = 2026-03) — total a bater: 667,36
        // =====================================================================================
        $ym = '2026-03';
        $d = fn (int $day) => Carbon::create(2026, 3, min($day, 28))->toDateString();

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -158.25, 'installment_count' => 12, 'installment_index' => 12,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Loja TIM (12/12)',
        ], ['category_id' => 11, 'subcategory_id' => 69]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(5),
            'amount' => -162.23, 'installment_count' => 12, 'installment_index' => 12,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'PITZI (12/12)',
        ], ['category_id' => 11, 'subcategory_id' => 69]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(9),
            'amount' => -239.07, 'installment_count' => 10, 'installment_index' => 5,
            'parent_transaction_id' => $celularParentId,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Celular Fernando (5/10)',
        ], ['category_id' => 11, 'subcategory_id' => 69]);

        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(9),
            'amount' => -107.88, 'installment_count' => 5, 'installment_index' => 5,
            'parent_transaction_id' => $zooParentId,
            'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Zoológico (5/5)',
        ], ['category_id' => 14, 'subcategory_id' => 140]);

        // Ajuste centavos para bater 667,36 (soma dá 667,43)
        $addTx([
            'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $d(28),
            'amount' => +0.07, // crédito
            'status' => 'pending', 'type' => 'income', 'invoice_paid' => 0,
            'method' => 'credit_card', 'notes' => 'Ajuste APP março (-0,07)',
        ], ['category_id' => 18, 'subcategory_id' => 221]);

        // =====================================================================================
        // ABRIL → AGOSTO/2026  — 239,07 por mês (Celular 6/10 → 10/10)
        // =====================================================================================
        foreach (['2026-04', '2026-05', '2026-06', '2026-07', '2026-08'] as $i => $ym) {
            $date = Carbon::createFromFormat('Y-m', $ym)->startOfMonth()->addDays(9)->toDateString();
            $addTx([
                'client_id' => $clientId, 'card_id' => $cardId, 'invoice_month' => $ym, 'date' => $date,
                'amount' => -239.07, 'installment_count' => 10, 'installment_index' => 6 + $i,
                'parent_transaction_id' => $celularParentId,
                'status' => 'pending', 'type' => 'expense', 'invoice_paid' => 0,
                'method' => 'credit_card', 'notes' => 'Celular Fernando ('.(6 + $i).'/10)',
            ], ['category_id' => 11, 'subcategory_id' => 69]);
        }

        DB::commit();
    }
}
