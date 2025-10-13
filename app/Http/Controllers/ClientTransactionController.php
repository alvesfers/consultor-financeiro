<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Card;
use App\Models\Category;
use App\Models\Client;
use App\Models\Subcategory;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\TransactionSplit;
use Illuminate\Http\Request;
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

        $clientId = $client->id;

        // ==========================
        // Validação dos campos
        // ==========================
        $data = $request->validate([
            'date'           => ['required', 'date'],
            'amount_abs'     => ['required', 'numeric', 'min:0'],
            'type'           => ['required', Rule::in(['income', 'expense', 'transfer', 'transfer_in', 'transfer_out'])],
            'method'         => ['nullable', 'string', 'max:32'],
            'notes'          => ['nullable', 'string', 'max:1000'],

            // quando não for transferência
            'account_id'     => ['nullable', 'exists:accounts,id'],
            'card_id'        => ['nullable', 'exists:cards,id'],

            // classificação (opcional)
            'category_id'    => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
        ]);

        // ==========================
        // Normalização do valor
        // ==========================
        $amount = (float) $data['amount_abs'];
        if ($data['type'] === 'expense') {
            $amount *= -1;
        }

        // ==========================
        // Consistência categoria/subcategoria
        // ==========================
        if (!empty($data['subcategory_id']) && !empty($data['category_id'])) {
            $belongs = Subcategory::where('id', $data['subcategory_id'])
                ->where('category_id', $data['category_id'])
                ->exists();

            abort_unless(
                $belongs,
                422,
                'A subcategoria selecionada não pertence à categoria informada.'
            );
        }

        // ==========================
        // Cria a transação
        // ==========================
        $tx = Transaction::create([
            'client_id'            => $clientId,
            'account_id'           => $data['account_id'] ?? null,
            'card_id'              => $data['card_id'] ?? null,
            'date'                 => $data['date'],
            'amount'               => $amount,
            'status'               => Transaction::STATUS_CONFIRMED,
            'type'                 => $data['type'],
            'invoice_paid'         => false,
            'method'               => $data['method'] ?? null,
            'notes'                => $data['notes'] ?? null,
        ]);

        // ==========================
        // Vincula categoria/subcategoria
        // ==========================
        if (!empty($data['category_id']) || !empty($data['subcategory_id'])) {
            TransactionCategory::create([
                'transaction_id' => $tx->id,
                'client_id'      => $clientId,
                'category_id'    => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
            ]);
        }

        // (Futuro: se houver splits, adicionar TransactionSplit::create aqui)

        return back()->with('success', 'Transação criada com sucesso!');
    }
}
