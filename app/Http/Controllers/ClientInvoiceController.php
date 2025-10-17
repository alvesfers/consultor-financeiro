<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Card;
use App\Models\Client;
use App\Models\Transaction;
use Carbon\Carbon;
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
        $tz = $user->timezone ?? 'America/Sao_Paulo';

        /** @var Client $client */
        $client = Client::query()
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        $clientId = $client->id;
        $asOfDate = Carbon::now($tz)->toDateString(); // para cálculo de overdue

        // -------- Filtros
        $bankId = $request->integer('bank_id');
        $accountId = $request->integer('account_id');
        $cardId = $request->integer('card_id');
        $status = $request->input('status');  // 'open' | 'paid' | 'overdue'
        $year = $request->integer('year');
        $month = $request->integer('month'); // 1-12
        $q = trim((string) $request->input('q', ''));

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
                ci.purchases_amount     as total_amount,
                ci.credits_amount       as paid_amount,
                ci.remaining_amount,
                ci.unpaid_debit_count,
                CASE
                    WHEN ci.unpaid_debit_count = 0 THEN 'paid'
                    WHEN ci.remaining_amount  > 0 AND ci.due_on < ? THEN 'overdue'
                    ELSE 'open'
                END as status
            ", [$asOfDate]);

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
        if ($year) {
            $sub->whereRaw('YEAR(ci.month_ref) = ?', [$year]);
        }
        if ($month) {
            $sub->whereRaw('MONTH(ci.month_ref) = ?', [$month]);
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
                'year' => $year,
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

        return view('consultants.clients.invoices.show', [
            'transactions' => $transactions,
            'monthKey' => $group->month_key,
        ]);
    }

    /**
     * Marca todas as transactions do grupo (cartão + mês) como pagas (não cria lançamento na conta).
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

        // Seleciona e atualiza
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

    /**
     * Paga a fatura do grupo (cartão + mês):
     * - cria a saída na conta de pagamento (method=credit_card_invoice)
     * - marca as transactions do grupo como pagas
     */
    public function pay(Request $request, $consultant, $invoice)
    {
        $user = $request->user();
        $tz = $user->timezone ?? 'America/Sao_Paulo';

        /** @var Client $client */
        $client = Client::query()
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        $clientId = $client->id;

        // Descobre o grupo + captura dados necessários (card + month + valores)
        $base = $this->baseInvoiceAggregationQuery($clientId);

        $group = DB::query()->fromSub($base, 'ci')
            ->selectRaw('
                ci.card_id,
                ci.card_name,
                ci.pay_account_id,
                ci.month_ref,
                ci.due_on,
                ci.remaining_amount,
                ci.unpaid_debit_count
            ')
            ->whereRaw("CRC32(CONCAT(ci.card_id, '|', DATE_FORMAT(ci.month_ref, '%Y-%m'))) = ?", [$invoice])
            ->first();

        abort_unless($group, 404);

        // Verificações
        if (! $group->pay_account_id) {
            return back()->withErrors('Defina a conta de pagamento para este cartão.')->withInput();
        }

        $toPay = (float) max(0, $group->remaining_amount); // só paga se houver restante
        if ($toPay <= 0 || (int) $group->unpaid_debit_count === 0) {
            return back()->with('success', 'Não há valor a pagar nesta fatura.');
        }

        // Data do pagamento: fornecida ou due_on
        $payDate = $request->date
            ? Carbon::parse($request->date, $tz)
            : Carbon::parse($group->due_on, $tz);

        // Marcação + lançamento
        [$monthRefExpr] = $this->monthAndDueExpressions('t', 'c');

        DB::transaction(function () use ($clientId, $group, $toPay, $payDate, $monthRefExpr) {

            // 1) Lançamento na conta de pagamento (saída)
            Transaction::create([
                'client_id' => $clientId,
                'account_id' => $group->pay_account_id,
                'card_id' => null,
                'date' => $payDate->toDateString(),
                'amount' => -$toPay,
                'status' => 'confirmed',
                'method' => 'credit_card_invoice',
                'notes' => "Pagamento fatura {$group->card_name} (".Carbon::parse($group->month_ref)->format('Y-m').')',
            ]);

            // 2) Marcar todas as compras do ciclo como pagas
            $ids = DB::table('transactions as t')
                ->join('cards as c', 'c.id', '=', 't.card_id')
                ->where('t.client_id', $clientId)
                ->where('t.card_id', $group->card_id)
                ->whereRaw("DATE_FORMAT($monthRefExpr, '%Y-%m') = ?", [Carbon::parse($group->month_ref)->format('Y-m')])
                ->pluck('t.id');

            if ($ids->isNotEmpty()) {
                DB::table('transactions')
                    ->whereIn('id', $ids)
                    ->update(['invoice_paid' => 1]);
            }
        });

        return back()->with('success', 'Fatura paga com sucesso!');
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

        // due_on: pega o dia 'due_day' dentro do mês_ref (limitado ao último dia do mês)
        $dueOnExpr = "DATE_ADD(
            $monthRefExpr,
            INTERVAL LEAST(COALESCE($c.due_day, 10), DAY(LAST_DAY($cycleBase))) - 1 DAY
        )";

        return [$monthRefExpr, $dueOnExpr];
    }

    /**
     * Query base agregando por cartão + mês de referência,
     * somando compras (débitos), créditos (estornos) e restante,
     * e contando quantas compras estão com `invoice_paid = 0`.
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

                -- totais
                SUM(CASE WHEN t.amount < 0 THEN -t.amount ELSE 0 END) as purchases_amount,
                SUM(CASE WHEN t.amount > 0 THEN  t.amount ELSE 0 END) as credits_amount,
                SUM(CASE WHEN t.amount < 0 THEN -t.amount ELSE 0 END)
                  - SUM(CASE WHEN t.amount > 0 THEN  t.amount ELSE 0 END) as remaining_amount,

                SUM(CASE WHEN t.amount < 0 AND COALESCE(t.invoice_paid,0) = 0 THEN 1 ELSE 0 END) as unpaid_debit_count
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
