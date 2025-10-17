<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Card;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientInvoiceController extends Controller
{
    /**
     * Lista “faturas” agregadas a partir das transactions, com filtros.
     */
    public function index(Request $request, $consultant)
    {
        $user = $request->user();

        /** @var Client $client */
        $client = Client::query()
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        $clientId = $client->id;

        // -------- Filtros
        $bankId = $request->integer('bank_id');
        $accountId = $request->integer('account_id');
        $cardId = $request->integer('card_id');
        $status = $request->input('status');  // 'open' | 'paid' | 'overdue'
        $month = $request->input('month');   // 'YYYY-MM'
        $q = trim((string) $request->input('q'));

        // -------- Fontes para selects
        $banks = DB::table('banks')->orderBy('name')->get(['id', 'name']);

        $accounts = Account::query()
            ->where('client_id', $clientId)
            ->orderBy('name')
            ->get(['id', 'name', 'bank_id']);

        $cards = Card::query()
            ->where('client_id', $clientId)
            ->orderBy('name')
            ->get(['id', 'name', 'last4', 'payment_account_id', 'close_day', 'due_day']);

        // ====== Base agregada por cartão + mês ======
        $base = $this->baseInvoiceAggregationQuery($clientId);

        // ====== Subselect com status calculado e id sintético (CRC32)
        $sub = DB::query()->fromSub($base, 'ci')
            ->selectRaw("
                CRC32(CONCAT(ci.card_id, '|', DATE_FORMAT(ci.month_ref, '%Y-%m'))) as id,
                ci.card_id,
                ci.card_name,
                ci.card_last4,
                ci.bank_id,
                ci.bank_name,
                ci.pay_account_id,
                ci.pay_account_name,
                ci.month_ref,
                ci.due_on,
                ci.purchases_amount as total_amount,
                ci.credits_amount   as paid_amount,
                ci.remaining_amount,
                CASE
                    WHEN ci.remaining_amount <= 0 THEN 'paid'
                    WHEN ci.remaining_amount  > 0 AND ci.due_on < CURDATE() THEN 'overdue'
                    ELSE 'open'
                END as status
            ");

        // ====== Filtros
        if ($bankId) {
            $sub->where('ci.bank_id', $bankId);
        }
        if ($accountId) {
            $sub->where('ci.pay_account_id', $accountId);
        }
        if ($cardId) {
            $sub->where('ci.card_id', $cardId);
        }
        if ($status) {
            $sub->where('status', $status);
        }
        if ($month) {
            $sub->whereRaw("DATE_FORMAT(ci.month_ref, '%Y-%m') = ?", [$month]);
        }
        if ($q !== '') {
            $like = '%'.str_replace(' ', '%', $q).'%';
            $sub->where(function ($w) use ($like) {
                $w->where('ci.card_name', 'like', $like)
                    ->orWhere('ci.bank_name', 'like', $like)
                    ->orWhere('ci.pay_account_name', 'like', $like)
                    ->orWhere('ci.card_last4', 'like', $like);
            });
        }

        // ====== Paginação e resumo
        $invoices = $sub
            ->orderByDesc('ci.month_ref')
            ->orderBy('ci.card_name')
            ->paginate(15)
            ->withQueryString();

        $all = (clone $sub)->get();

        $summary = [
            'total' => (float) $all->sum('total_amount'),
            'paid' => (float) $all->sum('paid_amount'),
            'open' => (float) $all->where('status', 'open')->sum('total_amount'),
            'overdue' => (float) $all->where('status', 'overdue')->sum('total_amount'),
        ];

        return view('consultants.clients.invoices.index', [
            'consultantId' => $consultant,
            'banks' => $banks,
            'accounts' => $accounts,
            'cards' => $cards,
            'invoices' => $invoices,
            'summary' => $summary,
            'filters' => [
                'bank_id' => $bankId,
                'account_id' => $accountId,
                'card_id' => $cardId,
                'status' => $status,
                'month' => $month,
                'q' => $q,
            ],
        ]);
    }

    /**
     * Mostra a fatura (lista de transações) a partir do id sintético.
     */
    public function show(Request $request, $consultant, $invoice)
    {
        $user = $request->user();

        $client = Client::query()
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        $clientId = $client->id;

        // Acha o grupo (cartão + mês) com a MESMA base usada no index:
        $base = $this->baseInvoiceAggregationQuery($clientId);

        $group = DB::query()->fromSub($base, 'ci')
            ->selectRaw("
                ci.card_id,
                DATE_FORMAT(ci.month_ref, '%Y-%m') as month_key
            ")
            ->whereRaw("CRC32(CONCAT(ci.card_id, '|', DATE_FORMAT(ci.month_ref, '%Y-%m'))) = ?", [$invoice])
            ->first();

        abort_unless($group, 404);

        // Agora busca as transactions do grupo, usando a MESMA expressão de month_ref.
        [$monthRefExpr] = $this->monthAndDueExpressions('t', 'c');

        $transactions = DB::table('transactions as t')
            ->join('cards as c', 'c.id', '=', 't.card_id') // para usar close_day/due_day na expressão
            ->where('t.client_id', $clientId)
            ->where('t.card_id', $group->card_id)
            ->whereRaw("DATE_FORMAT($monthRefExpr, '%Y-%m') = ?", [$group->month_key])
            ->orderBy('t.date')
            ->get();

        return view('client.invoices.show', [
            'transactions' => $transactions,
            'monthKey' => $group->month_key,
        ]);
    }

    /**
     * Marca todas as transactions do grupo (cartão + mês) como pagas.
     */
    public function markPaid(Request $request, $consultant, $invoice)
    {
        $user = $request->user();

        $client = Client::query()
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        $clientId = $client->id;

        // Descobre o grupo com a mesma lógica do index
        $base = $this->baseInvoiceAggregationQuery($clientId);

        $group = DB::query()->fromSub($base, 'ci')
            ->selectRaw("
                ci.card_id,
                DATE_FORMAT(ci.month_ref, '%Y-%m') as month_key
            ")
            ->whereRaw("CRC32(CONCAT(ci.card_id, '|', DATE_FORMAT(ci.month_ref, '%Y-%m'))) = ?", [$invoice])
            ->first();

        abort_unless($group, 404);

        // Seleciona os IDs das transactions do grupo e faz update em lote
        [$monthRefExpr] = $this->monthAndDueExpressions('t', 'c');

        $ids = DB::table('transactions as t')
            ->join('cards as c', 'c.id', '=', 't.card_id')
            ->where('t.client_id', $clientId)
            ->where('t.card_id', $group->card_id)
            ->whereRaw("DATE_FORMAT($monthRefExpr, '%Y-%m') = ?", [$group->month_key])
            ->pluck('t.id');

        if ($ids->isNotEmpty()) {
            DB::table('transactions')
                ->whereIn('id', $ids)
                ->update(['invoice_paid' => 1]);
        }

        return back()->with('success', 'Fatura marcada como paga.');
    }

    // ============================================================
    // ===================== HELPERS PRIVADOS =====================
    // ============================================================

    /**
     * Retorna as expressões SQL (month_ref, due_on) para usar em SELECTs/WHEREs,
     * baseadas nas colunas t.`date`, t.invoice_month, c.close_day, c.due_day.
     *
     * @param  string  $t  alias da tabela de transactions
     * @param  string  $c  alias da tabela de cards
     * @return array{0:string,1:string} [$monthRefExpr, $dueOnExpr]
     */
    private function monthAndDueExpressions(string $t = 't', string $c = 'c'): array
    {
        $txDate = "$t.`date`";

        // Base do ciclo: se o dia da transação é > close_day, pertence ao mês seguinte
        $cycleBase = "CASE
            WHEN DAY($txDate) > COALESCE($c.close_day, 28)
                THEN DATE_ADD($txDate, INTERVAL 1 MONTH)
            ELSE $txDate
        END";

        // month_ref: prioriza invoice_month se existir; senão, usa o cycleBase
        $monthRefExpr = "COALESCE(
            DATE_FORMAT($t.invoice_month, '%Y-%m-01'),
            DATE_FORMAT($cycleBase, '%Y-%m-01')
        )";

        // due_on: pega o dia 'due_day' dentro do mês_ref (clamp para o último dia)
        $dueOnExpr = "DATE_ADD(
            $monthRefExpr,
            INTERVAL LEAST(COALESCE($c.due_day, 10), DAY(LAST_DAY($cycleBase))) - 1 DAY
        )";

        return [$monthRefExpr, $dueOnExpr];
    }

    /**
     * Monta a query base agregando por cartão + mês de referência,
     * já trazendo totais de compras (debits), créditos e remaining.
     */
    private function baseInvoiceAggregationQuery(int $clientId)
    {
        [$monthRefExpr, $dueOnExpr] = $this->monthAndDueExpressions('t', 'c');

        return DB::table('transactions as t')
            ->join('cards as c', 'c.id', '=', 't.card_id')
            ->leftJoin('accounts as a', 'a.id', '=', 'c.payment_account_id')
            ->leftJoin('banks as b', 'b.id', '=', 'a.bank_id')
            ->where('t.client_id', $clientId)
            ->whereNotNull('t.card_id')
            ->selectRaw("
                c.id   as card_id,
                c.name as card_name,
                c.last4 as card_last4,
                a.id   as pay_account_id,
                a.name as pay_account_name,
                b.id   as bank_id,
                b.name as bank_name,
                $monthRefExpr as month_ref,
                $dueOnExpr    as due_on,
                SUM(CASE WHEN t.amount < 0 THEN -t.amount ELSE 0 END) as purchases_amount,
                SUM(CASE WHEN t.amount > 0 THEN  t.amount ELSE 0 END) as credits_amount,
                SUM(CASE WHEN t.amount < 0 THEN -t.amount ELSE 0 END)
                  - SUM(CASE WHEN t.amount > 0 THEN  t.amount ELSE 0 END) as remaining_amount
            ")
            ->groupBy([
                'c.id', 'c.name', 'c.last4',
                'a.id', 'a.name',
                'b.id', 'b.name',
                DB::raw($monthRefExpr),
                DB::raw($dueOnExpr),

                // Necessário em ambientes com ONLY_FULL_GROUP_BY
                'c.close_day', 'c.due_day',
            ]);
    }
}
