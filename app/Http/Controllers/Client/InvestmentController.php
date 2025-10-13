<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvestmentController extends Controller
{
    public function move(Request $request, $consultant)
    {
        $user = $request->user();

        /** @var Client $client */
        $client = Client::where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        $data = $request->validate([
            'move' => ['required', Rule::in(['deposit', 'withdraw'])], // deposit: conta -> investimento | withdraw: investimento -> conta
            'date' => ['required', 'date'],
            'amount_abs' => ['required', 'numeric', 'min:0.01'],
            'from_account_id' => ['required', 'exists:accounts,id'],
            'to_account_id' => ['required', 'exists:accounts,id'],

            // classificação opcional
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],

            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ((int) $data['from_account_id'] === (int) $data['to_account_id']) {
            return back()->withErrors('Origem e destino não podem ser a mesma conta.')->withInput();
        }

        $amount = (float) $data['amount_abs'];
        $date = Carbon::parse($data['date']);
        $notes = $data['note'] ?? null;

        DB::transaction(function () use ($client, $data, $amount, $date, $notes) {
            // saída
            $out = Transaction::create([
                'client_id' => $client->id,
                'account_id' => (int) $data['from_account_id'],
                'card_id' => null,
                'date' => $date,
                'amount' => -abs($amount),
                'status' => 'confirmed',
                'method' => 'investment',
                'notes' => $notes ?: ($data['move'] === 'deposit' ? 'Aporte em investimento' : 'Resgate de investimento'),
            ]);

            // entrada
            $in = Transaction::create([
                'client_id' => $client->id,
                'account_id' => (int) $data['to_account_id'],
                'card_id' => null,
                'date' => $date,
                'amount' => abs($amount),
                'status' => 'confirmed',
                'method' => 'investment',
                'notes' => $notes ?: ($data['move'] === 'deposit' ? 'Aporte em investimento' : 'Resgate de investimento'),
            ]);

            $out->update(['transfer_pair_id' => $in->id]);
            $in->update(['transfer_pair_id' => $out->id]);

            // Classificação (opcional)
            if (! empty($data['category_id']) || ! empty($data['subcategory_id'])) {
                $out->category()->create([
                    'category_id' => $data['category_id'] ?? null,
                    'subcategory_id' => $data['subcategory_id'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Movimentação de investimento registrada!');
    }
}
