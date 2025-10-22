<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Card;
use App\Models\Category;
use App\Models\Client;
use App\Models\RecurringRule;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecurringRuleController extends Controller
{
    /**
     * Lista de regras recorrentes do cliente autenticado no contexto do consultor.
     */
    public function index(Request $request, $consultant)
    {
        $user = $request->user();

        /** @var Client $client */
        $client = Client::query()
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        $clientId = (int) $client->id;

        $rules = RecurringRule::query()
            ->where('client_id', $clientId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20);

        return view('consultants.clients.recurrents.index', [
            'consultantId' => (int) $consultant,
            'clientId'     => $clientId,
            'rules'        => $rules,
        ]);
    }

    /**
     * Formulário de criação.
     */
    public function create(Request $request, $consultant)
    {
        $user = $request->user();

        /** @var Client $client */
        $client = Client::query()
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        $clientId = (int) $client->id;

        $accounts      = Account::where('client_id', $clientId)->orderBy('name')->get();
        $cards         = Card::where('client_id', $clientId)->orderBy('name')->get();
        $categories = Category::query()
            ->where(function ($q) use ($clientId) {
                $q->where('client_id', $clientId)
                ->orWhereNull('client_id');
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $subcategories = Subcategory::query()
            ->where(function ($q) use ($clientId) {
                $q->where('client_id', $clientId)
                ->orWhereNull('client_id');
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();


        return view('consultants.clients.recurrents.create', [
            'consultantId'  => (int) $consultant,
            'clientId'      => $clientId,
            'accounts'      => $accounts,
            'cards'         => $cards,
            'categories'    => $categories,
            'subcategories' => $subcategories,
        ]);
    }

    /**
     * Persistência.
     */
    public function store(Request $request, $consultant)
    {
        $user = $request->user();

        /** @var Client $client */
        $client = Client::query()
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        $clientId = (int) $client->id;

        $data = $request->all();

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'merchant'       => ['nullable', 'string', 'max:120'],

            'type'           => ['required', Rule::in(['income','expense','transfer','adjustment'])],
            'method'         => ['nullable', Rule::in(['pix','debit','credit_card','cash','transfer','boleto','adjustment'])],
            'amount'         => ['nullable','numeric'],

            'account_id'     => ['nullable','integer','exists:accounts,id'],
            'card_id'        => ['nullable','integer','exists:cards,id'],
            'category_id'    => ['nullable','integer','exists:categories,id'],
            'subcategory_id' => ['nullable','integer','exists:subcategories,id'],
            'notes'          => ['nullable','string','max:2000'],

            'freq'           => ['required', Rule::in(['monthly','weekly','yearly','custom'])],
            'interval'       => ['required','integer','min:1','max:12'],
            'by_month_day'   => ['nullable','integer','min:1','max:31'],
            'shift_rule'     => ['required', Rule::in(['exact','previous_business_day','next_business_day'])],
            'start_date'     => ['required','date'],
            'end_date'       => ['nullable','date','after_or_equal:start_date'],
            'autopay'        => ['nullable','boolean'],
            'is_active'      => ['nullable','boolean'],
        ],[
            'name.required'          => 'Informe o nome.',
            'type.required'          => 'Informe o tipo.',
            'freq.required'          => 'Informe a frequência.',
            'start_date.required'    => 'Informe a data inicial.',
        ]);

        // força client_id e flags
        $validated['client_id'] = $clientId;
        $validated['autopay']   = (bool)($data['autopay'] ?? false);
        $validated['is_active'] = (bool)($data['is_active'] ?? true);

        // precisa apontar para CONTA ou CARTÃO
        if (empty($validated['account_id']) && empty($validated['card_id'])) {
            return back()->withErrors(['account_id' => 'Selecione uma conta ou um cartão.'])->withInput();
        }

        // coerência method x destino
        if (($validated['method'] ?? null) === 'credit_card' && empty($validated['card_id'])) {
            return back()->withErrors(['card_id' => 'Para método "Cartão de crédito", selecione um cartão.'])->withInput();
        }

        // se vier account e card, resolve — exceto transferências
        if (!empty($validated['account_id']) && !empty($validated['card_id']) && $validated['type'] !== 'transfer') {
            if (($validated['method'] ?? null) === 'credit_card') {
                $validated['account_id'] = null;
            } else {
                $validated['card_id'] = null;
            }
        }

        DB::transaction(function () use ($validated) {
            RecurringRule::create($validated);
        });

        return redirect()->route('client.recurrents.index', ['consultant' => $consultant])
            ->with('success', 'Regra recorrente cadastrada com sucesso.');
    }
}
