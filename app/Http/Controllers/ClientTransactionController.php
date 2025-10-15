<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Subcategory;
use App\Models\Transaction;
use App\Models\TransactionCategory;
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

        // ============ 1) Normalização (kind -> type; saneamento) ============
        // O wizard manda "kind"; se "type" não vier, herda de "kind"
        if ($request->filled('kind') && ! $request->filled('type')) {
            $request->merge(['type' => $request->input('kind')]);
        }

        // Para transferência no wizard: type fica exatamente "transfer"
        // (vamos expandir em transfer_in/transfer_out na gravação)
        $type = $request->input('type');

        // ============ 2) Regras base + condicionais ============
        $baseRules = [
            'date' => ['required', 'date'], // se quiser travar formato: 'date_format:Y-m-d\TH:i'
            'amount_abs' => ['required', 'numeric', 'min:0'],
            'type' => ['required', Rule::in(['income', 'expense', 'transfer', 'transfer_in', 'transfer_out'])],
            'method' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // recursos
            'account_id' => ['nullable', 'exists:accounts,id'],
            'card_id' => ['nullable', 'exists:cards,id'],

            // classificação (opcional)
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],

            // transferência
            'from_account_id' => ['nullable', 'exists:accounts,id'],
            'to_account_id' => ['nullable', 'exists:accounts,id'],
            'mode' => ['nullable', Rule::in(['account', 'card'])], // vem do wizard
        ];

        // Validação em duas etapas para permitir condicionais
        $validator = \Validator::make($request->all(), $baseRules);
        $validator->after(function ($v) use ($request) {
            $t = $request->input('type');
            $mode = $request->input('mode');

            if ($t === 'transfer') {
                // Transferência: precisa de origem/destino e devem ser diferentes
                if (! $request->filled('from_account_id') || ! $request->filled('to_account_id')) {
                    $v->errors()->add('from_account_id', 'Conta de origem e destino são obrigatórias para transferência.');
                } elseif ($request->input('from_account_id') == $request->input('to_account_id')) {
                    $v->errors()->add('to_account_id', 'Origem e destino não podem ser a mesma conta.');
                }

                // Não deve enviar card_id em transferência
                if ($request->filled('card_id')) {
                    $v->errors()->add('card_id', 'Cartão não é válido para transferência.');
                }
            } else {
                // Gasto/Ganho: precisa ou de account_id (modo conta) ou de card_id (modo cartão)
                if ($mode === 'card' || $request->filled('card_id')) {
                    if (! $request->filled('card_id')) {
                        $v->errors()->add('card_id', 'Selecione um cartão.');
                    }
                    // Em modo cartão, ignoramos account_id (mas não precisa dar erro)
                } else {
                    // default/conta
                    if (! $request->filled('account_id')) {
                        $v->errors()->add('account_id', 'Selecione uma conta.');
                    }
                }
            }

            // Se mandou subcategoria, ela deve pertencer à categoria informada
            if ($request->filled('subcategory_id') && $request->filled('category_id')) {
                $belongs = Subcategory::where('id', $request->input('subcategory_id'))
                    ->where('category_id', $request->input('category_id'))
                    ->exists();
                if (! $belongs) {
                    $v->errors()->add('subcategory_id', 'A subcategoria não pertence à categoria selecionada.');
                }
            }
        });

        $validator->validate();
        $data = $validator->validated();

        // ============ 3) Normalização do valor ============
        $amount = (float) $data['amount_abs'];
        if ($data['type'] === 'expense') {
            $amount *= -1;
        }

        // ============ 4) Persistência ============
        if ($data['type'] === 'transfer') {
            // Cria duas transações: saída (negativa) e entrada (positiva)
            $out = Transaction::create([
                'client_id' => $clientId,
                'account_id' => $data['from_account_id'],
                'card_id' => null,
                'date' => $data['date'],
                'amount' => -abs($data['amount_abs']),
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
                'amount' => abs($data['amount_abs']),
                'status' => Transaction::STATUS_CONFIRMED,
                'type' => 'transfer_in',
                'invoice_paid' => false,
                'method' => $data['method'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Transferência não recebe categoria/sub por padrão
            return back()->with('success', 'Transferência registrada com sucesso!');
        }

        // Gasto / Ganho (conta ou cartão)
        $tx = Transaction::create([
            'client_id' => $clientId,
            'account_id' => $data['account_id'] ?? null,
            'card_id' => $data['card_id'] ?? null,
            'date' => $data['date'],
            'amount' => $amount,
            'status' => Transaction::STATUS_CONFIRMED,
            'type' => $data['type'], // 'income' ou 'expense'
            'invoice_paid' => false,
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
}
