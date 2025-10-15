<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Card;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Http\Request; // lá no topo
use Illuminate\Validation\Rule;

class ClientAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    protected function resolveClient(Request $request, $consultant): Client
    {
        /** @var Client $client */
        $client = Client::query()
            ->where('user_id', $request->user()->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        return $client;
    }

    public function index(Request $request, $consultant)
    {
        $user = $request->user();

        /** @var Client $client */
        $client = Client::query()
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        // ===== Contas do cliente com banco, cartões e últimos lançamentos =====
        // - transactions (conta): últimas 20, desc
        // - cards.transactions: últimas 20, desc
        $accounts = Account::query()
            ->where('client_id', $client->id)
            ->with([
                'bank',
                'cards' => function ($q) {
                    $q->orderBy('name');
                },
                'cards.transactions' => function ($q) {
                    $q->orderByDesc('created_at')->limit(20);
                },
                'transactions' => function ($q) {
                    $q->orderByDesc('created_at')->limit(20);
                },
            ])
            ->orderBy('name')
            ->get();

        // ===== Soma de transações por conta (saldo = opening_balance + SUM(transactions.amount)) =====
        $txSums = Transaction::query()
            ->whereIn('account_id', $accounts->pluck('id'))
            ->selectRaw('account_id, SUM(amount) as total_amount')
            ->groupBy('account_id')
            ->pluck('total_amount', 'account_id');

        // ===== Monta lista PLANA de contas para o carrossel e a seção detalhada =====
        // Cada item representa UMA conta (ex.: "Santander - PF" e "Santander - PJ" aparecem separadas)
        $accountsData = $accounts->map(function (Account $a) use ($txSums) {
            $bank = optional($a->bank);

            // saldo da conta
            $opening = (float) ($a->opening_balance ?? 0);
            $sumTx = (float) ($txSums[$a->id] ?? 0);
            $balance = $opening + $sumTx;

            // cartões "vinculados" à conta: prioriza payment_account_id == conta.id
            $cardsForAccount = $a->cards
                ->filter(fn (Card $c) => (string) ($c->payment_account_id ?? '') === (string) $a->id)
                ->values();

            // fallback: se nenhum cartão com payment_account_id, mantém todos os que chegaram pelo eager (se fizer sentido no seu domínio)
            if ($cardsForAccount->isEmpty()) {
                $cardsForAccount = $a->cards->values();
            }

            // extrato combinado (conta + cartões desta conta) -- usa os eagers já limitados
            $txCombined = collect();

            foreach ($a->transactions as $t) {
                $arr = [
                    'id' => $t->id,
                    'description' => $t->description,
                    'amount' => (float) $t->amount,
                    'created_at' => $t->created_at,
                    '_source' => 'Conta '.($a->name ?? ''),
                ];
                $txCombined->push($arr);
            }

            foreach ($cardsForAccount as $c) {
                foreach ($c->transactions as $t) {
                    $arr = [
                        'id' => $t->id,
                        'description' => $t->description,
                        'amount' => (float) $t->amount,
                        'created_at' => $t->created_at,
                        '_source' => 'Cartão '.($c->name ?? ''),
                    ];
                    $txCombined->push($arr);
                }
            }

            $txCombined = $txCombined
                ->sortByDesc(fn ($t) => $t['created_at'] ?? null)
                ->take(50)
                ->values();

            return [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
                'currency' => $a->currency,
                'bank_id' => $a->bank_id,
                'bank' => $bank ? [
                    'id' => $bank->id,
                    'name' => $bank->name,
                    'slug' => $bank->slug,
                    'logo_svg' => $bank->logo_svg,
                    'color_primary' => $bank->color_primary,
                    'color_secondary' => $bank->color_secondary,
                    'color_bg' => $bank->color_bg,
                    'color_text' => $bank->color_text,
                ] : null,
                'balance_total' => (float) $balance,

                // cartões exibidos na UI (detalhes inline)
                'cards' => $cardsForAccount->map(function (Card $c) {
                    return [
                        'id' => $c->id,
                        'name' => $c->name,
                        'brand' => $c->brand,
                        'last4' => $c->last4,
                        'limit_amount' => (float) $c->limit_amount,
                        'close_day' => $c->close_day,
                        'due_day' => $c->due_day,
                        'payment_account_id' => $c->payment_account_id,
                        'bank_id' => $c->bank_id,
                        // as transactions vêm limitadas/ordenadas no eager
                        'transactions' => $c->transactions->map(function (Transaction $t) {
                            return [
                                'id' => $t->id,
                                'description' => $t->description,
                                'amount' => (float) $t->amount,
                                'created_at' => $t->created_at,
                            ];
                        })->values(),
                    ];
                })->values(),

                // extrato combinado para a tabela de "Extrato combinado" (conta + cartões)
                'recent_transactions' => $txCombined,
            ];
        });

        // Ordena as contas por saldo desc só para UX
        $accountsData = $accountsData->sortByDesc('balance_total')->values();

        // ===== Listas auxiliares para os selects dos modais =====
        $banks = Bank::orderBy('name')->get(['id', 'name', 'slug', 'logo_svg']);

        // Para o modal de cartão: bank_id => [ {id, name, bank_id}, ... ]
        $accountsByBank = $accounts->groupBy('bank_id')->map(function ($items) {
            return $items->map(fn (Account $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'bank_id' => $a->bank_id,
            ])->values();
        });

        // (opcional) mapa de compras recentes por cartão, caso alguma view antiga ainda use
        $cardsRecentPurchases = Card::query()
            ->whereIn('payment_account_id', $accounts->pluck('id'))
            ->with(['transactions' => fn ($q) => $q->orderByDesc('created_at')->limit(10)])
            ->get()
            ->keyBy('id');

        return view('consultants.clients.accounts.index', [
            'consultantId' => $consultant,
            // mantive $accounts se alguma partial antiga ainda usar
            'accounts' => $accounts,
            // >>> usado pelo novo Blade/JS (sem agrupamento por banco)
            'accountsData' => $accountsData,
            // selects dos modais
            'banks' => $banks,
            'accountsByBank' => $accountsByBank,
            // legado/compatibilidade
            'cardsRecentPurchases' => $cardsRecentPurchases,
        ]);
    }

    public function storeCard(Request $request, $consultant)
    {
        $client = $this->resolveClient($request, $consultant);

        /**
         * Normaliza campos numéricos — remove zeros à esquerda e força null
         * Isso evita o erro "The close day must be an integer"
         */
        $normalized = $request->all();

        foreach (['close_day', 'due_day'] as $field) {
            $v = $request->input($field);
            $normalized[$field] = is_numeric($v) ? (int) $v : null;
        }

        // Substitui os dados normalizados no request
        $request->merge($normalized);

        // === Validação ===
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:50'],
            'last4' => ['nullable', 'digits:4'],
            'limit_amount' => ['nullable', 'numeric', 'min:0'],
            'close_day' => ['nullable', 'integer', 'between:1,31'],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
            'payment_account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')
                ->where(fn ($q) => $q->where('client_id', $client->id))],
        ]);

        // === Criação ===
        $card = new Card;
        $card->client_id = $client->id;
        $card->name = $data['name'];
        $card->brand = $data['brand'] ?? null;
        $card->last4 = $data['last4'] ?? null;
        $card->limit_amount = (float) ($data['limit_amount'] ?? 0);
        $card->close_day = $data['close_day'] ?? null;
        $card->due_day = $data['due_day'] ?? null;
        $card->payment_account_id = $data['payment_account_id'] ?? null;
        $card->save();

        return redirect()
            ->route('client.accounts.index', ['consultant' => $consultant])
            ->with('success', "Cartão '{$card->name}' criado com sucesso!");
    }

    public function updateCard(Request $request, $consultant, Card $card)
    {
        $client = $this->resolveClient($request, $consultant);

        // segurança: o cartão tem que ser do mesmo cliente
        abort_if($card->client_id !== $client->id, 403);

        $data = $request->validate([
            'last4' => ['nullable', 'digits:4'],
            'limit_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $card->last4 = $data['last4'] ?? $card->last4;
        $card->limit_amount = isset($data['limit_amount']) ? (float) $data['limit_amount'] : $card->limit_amount;
        $card->save();

        return back()->with('success', 'Cartão atualizado com sucesso.');
    }

    public function storeAccount(Request $request, $consultant)
    {
        $user = $request->user();

        $client = Client::query()
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        // validação
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:5'],
            'opening_balance' => ['nullable', 'numeric'],
            'bank_id' => ['nullable', 'integer', 'exists:banks,id'], // ✅ integer
        ]);

        // normaliza bank_id ('' -> null)
        $bankId = $request->filled('bank_id') ? (int) $request->input('bank_id') : null;

        // cria explicitamente (sem depender do fillable)
        $account = new Account;
        $account->client_id = $client->id;
        $account->name = $data['name'];
        $account->type = $data['type'] ?? null;
        $account->currency = $data['currency'] ?? 'BRL';
        $account->opening_balance = (float) ($data['opening_balance'] ?? 0);
        $account->on_budget = true;
        $account->bank_id = $bankId;                 // ✅ grava o banco
        $account->save();

        return redirect()
            ->route('client.accounts.index', ['consultant' => $consultant])
            ->with('ok', "Conta '{$account->name}' criada com sucesso!");
    }
}
