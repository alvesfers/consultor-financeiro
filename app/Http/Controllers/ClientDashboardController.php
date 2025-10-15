<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Card;
use App\Models\Category;
use App\Models\Client;
use App\Models\Goal;
use App\Models\Subcategory;
use App\Models\Task;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientDashboardController extends Controller
{
    public function index(Request $request, $consultant)
    {
        $user = $request->user();
        $tz = $user->timezone ?? 'America/Sao_Paulo';

        /** @var Client $client */
        $client = Client::query()
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->firstOrFail();

        $clientId = (int) $client->id;

        // ===================== Contas & saldos =====================
        $accounts = Account::query()
            ->where('client_id', $clientId)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'on_budget', 'currency', 'opening_balance']);

        $opening = (float) $accounts->where('on_budget', true)->sum('opening_balance');

        $txSum = (float) DB::table('transactions')
            ->where('client_id', $clientId)
            ->whereNotNull('account_id')
            ->sum('amount');

        $balance = $opening + $txSum;

        // Total investido (contas type=investment)
        $investAccs = $accounts->where('type', 'investment');
        $investAccIds = $investAccs->pluck('id')->all();

        $investedMov = 0.0;
        if (! empty($investAccIds)) {
            $investedMov = (float) DB::table('transactions')
                ->where('client_id', $clientId)
                ->whereIn('account_id', $investAccIds)
                ->sum('amount');
        }

        $investedTotal = (float) $investAccs->sum('opening_balance') + $investedMov;
        $netWorth = $balance + $investedTotal;

        // ===================== Tasks / Goals =====================
        $tasksPendingCount = Task::where('client_id', $clientId)
            ->where('assigned_to', $user->id)
            ->where('status', 'open')
            ->count();

        $goalsPendingCount = Goal::where('client_id', $clientId)
            ->whereIn('status', ['ativo', 'pausado', 'atrasado'])
            ->count();

        // ===================== Filtros transações (lista recente) =====================
        $type = $request->query('type');           // 'income' | 'expense' | null
        $accountId = $request->query('account_id');
        $q = trim($request->query('q', ''));

        $recentQuery = DB::table('transactions as t')
            ->leftJoin('accounts as a', 'a.id', '=', 't.account_id')
            ->leftJoin('transaction_categories as tc', 'tc.transaction_id', '=', 't.id')
            ->leftJoin('categories as c', 'c.id', '=', 'tc.category_id')
            ->leftJoin('subcategories as sc', 'sc.id', '=', 'tc.subcategory_id')
            ->where('t.client_id', $clientId);

        if ($type === 'income') {
            $recentQuery->where('t.amount', '>', 0);
        } elseif ($type === 'expense') {
            $recentQuery->where('t.amount', '<', 0);
        }

        if (! empty($accountId)) {
            $recentQuery->where('t.account_id', $accountId);
        }

        if ($q !== '') {
            $recentQuery->where('t.notes', 'like', "%{$q}%");
        }

        $recentTransactions = $recentQuery
            ->orderByDesc('t.date')
            ->limit(10)
            ->get([
                't.id',
                't.date',
                't.amount',
                't.notes',
                'a.name as account_name',
                'c.name as category_name',      // << aqui
                'sc.name as subcategory_name',  // << aqui
            ]);

        // ===================== Gráfico de despesas (30d) =====================
        $since = Carbon::now($tz)->subDays(30)->startOfDay();

        $byCategory = DB::table('transactions as t')
            ->leftJoin('transaction_categories as tc', 'tc.transaction_id', '=', 't.id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'tc.category_id')
            ->where('t.client_id', $clientId)
            ->where('t.date', '>=', $since)
            ->where('t.amount', '<', 0)
            ->groupBy('tc.category_id', 'cat.name')
            ->selectRaw('COALESCE(cat.name, "Sem categoria") as name, SUM(ABS(t.amount)) as total')
            ->orderByDesc('total')
            ->get();

        $chartCategories = [
            'labels' => $byCategory->pluck('name'),
            'values' => $byCategory->pluck('total')->map(fn ($v) => (float) $v),
        ];

        // ===================== Faturas dos cartões =====================
        $cards = Card::where('client_id', $clientId)
            ->orderBy('name')
            ->get(['id', 'name', 'brand', 'limit_amount', 'close_day', 'due_day', 'payment_account_id']);

        $cardInvoices = [];
        $now = Carbon::now($tz);

        foreach ($cards as $card) {
            $cycle = $this->currentCycle($card, $now);
            $invoiceMonth = $cycle['invoice_month'];

            $total = (float) DB::table('transactions')
                ->where('client_id', $clientId)
                ->where('card_id', $card->id)
                ->where('invoice_month', $invoiceMonth)
                ->sum('amount');

            $totalAbs = abs(min(0, $total));

            $limit = (float) ($card->limit_amount ?? 0);
            $ocupado = abs(min(0, (float) DB::table('transactions')
                ->where('client_id', $clientId)
                ->where('card_id', $card->id)
                ->where('invoice_paid', false)
                ->sum('amount')));

            $available = $limit > 0 ? max(0, $limit - $ocupado) : null;

            $unpaidCount = DB::table('transactions')
                ->where('client_id', $clientId)
                ->where('card_id', $card->id)
                ->where('invoice_month', $invoiceMonth)
                ->where('invoice_paid', false)
                ->count();

            $cardInvoices[] = [
                'card' => $card,
                'invoice_month' => $invoiceMonth,
                'close_date' => $cycle['close_date'],
                'due_date' => $cycle['due_date'],
                'total' => $totalAbs,
                'limit' => $limit,
                'available' => $available,
                'all_paid' => $unpaidCount === 0,
            ];
        }

        // ===================== Metas mensais por categoria =====================
        $monthStart = Carbon::now($tz)->startOfMonth()->toDateString();
        $monthEnd = Carbon::now($tz)->endOfMonth()->toDateString();

        $categoryGoals = DB::table('category_goals')
            ->join('categories', 'category_goals.category_id', '=', 'categories.id')
            ->where('category_goals.client_id', $clientId)
            ->where('category_goals.month', Carbon::parse($monthStart)->startOfMonth()->toDateString())
            ->orderBy('categories.name')
            ->get([
                'categories.id as category_id',
                'categories.name as category_name',
                'category_goals.limit_amount',
            ]);

        // >>> soma de gastos do mês por categoria via transaction_categories
        $spendingByCategory = DB::table('transactions as t')
            ->join('transaction_categories as tc', 'tc.transaction_id', '=', 't.id')
            ->where('t.client_id', $clientId)
            ->whereBetween('t.date', [$monthStart, $monthEnd])
            ->groupBy('tc.category_id')
            ->select('tc.category_id', DB::raw('SUM(CASE WHEN t.amount < 0 THEN ABS(t.amount) ELSE 0 END) as total'))
            ->pluck('total', 'tc.category_id');

        $goalsComparative = $categoryGoals->map(function ($g) use ($spendingByCategory) {
            $spent = (float) ($spendingByCategory[$g->category_id] ?? 0);
            $limit = (float) $g->limit_amount;
            $remaining = $limit - $spent;
            $ratio = $limit > 0 ? max(0, min(1, $spent / $limit)) : 0;

            return [
                'category_id' => $g->category_id,
                'category_name' => $g->category_name,
                'limit' => $limit,
                'spent' => $spent,
                'remaining' => $remaining,
                'exceeded' => $spent > $limit,
                'ratio' => $ratio,
            ];
        });

        $goalsMonthTitle = Carbon::parse($monthStart)->locale('pt_BR')->translatedFormat('F/Y');

        // ===================== Categorias/Subcategorias p/ modal de Transação =====================
        $categories = Category::query()
            ->where('is_active', true)
            ->where(function ($q2) use ($clientId) {
                $q2->whereNull('client_id')->orWhere('client_id', $clientId);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'group_id']);

        $categoryIds = $categories->pluck('id')->all();

        $subcategories = collect();
        if (! empty($categoryIds)) {
            $subcategories = Subcategory::query()
                ->where('is_active', true)
                ->where(function ($q2) use ($clientId) {
                    $q2->whereNull('client_id')->orWhere('client_id', $clientId);
                })
                ->whereIn('category_id', $categoryIds)
                ->orderBy('name')
                ->get(['id', 'name', 'category_id']);
        }

        $subcategoriesByCategory = [];
        foreach ($subcategories as $s) {
            $subcategoriesByCategory[$s->category_id][] = ['id' => (int) $s->id, 'name' => $s->name];
        }

        $catsExpense = $categories->whereIn('group_id', [5, 3])
            ->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])
            ->values();

        $catsIncome = $categories->whereIn('group_id', [1, 2])
            ->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])
            ->values();

        $categoriesByKind = [
            'expense' => $catsExpense,
            'income' => $catsIncome,
        ];

        $categoriesByGroup = [];
        foreach ($categories as $cat) {
            $categoriesByGroup[$cat->id] = $subcategories
                ->where('category_id', $cat->id)
                ->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->name])
                ->values()
                ->toArray();
        }

        // ===================== Helpers específicos (Investimento/Resgate) =====================
        $childrenOf = function (string $name) use ($clientId) {
            $root = Category::query()
                ->where('is_active', true)
                ->where(function ($q) use ($clientId) {
                    $q->whereNull('client_id')->orWhere('client_id', $clientId);
                })
                ->where('name', $name)
                ->first(['id']);

            if (! $root) {
                return collect();
            }

            return Subcategory::query()
                ->where('category_id', $root->id)
                ->where('is_active', true)
                ->where(function ($q) use ($clientId) {
                    $q->whereNull('client_id')->orWhere('client_id', $clientId);
                })
                ->orderBy('name')
                ->get(['id', 'name']);
        };

        $invDepositCats = $childrenOf('Investimento')->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])->values();
        $invWithdrawCats = $childrenOf('Resgate')->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])->values();

        $investmentRoot = Category::query()
            ->where('is_active', true)
            ->where(function ($q2) use ($clientId) {
                $q2->whereNull('client_id')->orWhere('client_id', $clientId);
            })
            ->where('name', 'Investimento')
            ->first(['id']);

        if ($investmentRoot) {
            $investments = Subcategory::query()
                ->where('category_id', $investmentRoot->id)
                ->where('is_active', true)
                ->where(function ($q2) use ($clientId) {
                    $q2->whereNull('client_id')->orWhere('client_id', $clientId);
                })
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])
                ->values();
        } else {
            $investments = $accounts
                ->where('type', 'investment')
                ->map(fn ($a) => ['id' => (int) $a->id, 'name' => $a->name])
                ->values();
        }

        return view('consultants.clients.dashboard', [
            'accounts' => $accounts,
            'balance' => $balance,
            'investedTotal' => $investedTotal,
            'netWorth' => $netWorth,
            'tasksPendingCount' => $tasksPendingCount,
            'goalsPendingCount' => $goalsPendingCount,
            'recentTransactions' => $recentTransactions,
            'chartCategories' => $chartCategories,
            'consultantId' => (int) $consultant,
            'cards' => $cards,
            'cardInvoices' => $cardInvoices,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'categoriesByGroup' => $categoriesByGroup,
            'categoriesByKind' => $categoriesByKind,
            'subcategoriesByCategory' => $subcategoriesByCategory,
            'investments' => $investments,
            'invDepositCats' => $invDepositCats,
            'invWithdrawCats' => $invWithdrawCats,

            // novas variáveis de metas mensais
            'goalsComparative' => $goalsComparative,
            'goalsMonthTitle' => $goalsMonthTitle,
        ]);
    }

    // =================== Helpers de fatura ===================
    protected function nextBusinessDay(Carbon $date): Carbon
    {
        $d = $date->copy();
        while (in_array($d->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true)) {
            $d->addDay();
        }

        return $d;
    }

    protected function businessDayForYearMonth(int $year, int $month, int $day): Carbon
    {
        $base = Carbon::create($year, $month, 1);
        $safeDay = min($day, $base->daysInMonth);

        return $this->nextBusinessDay(Carbon::create($year, $month, $safeDay));
    }

    protected function closeDateForMonth(Card $card, int $year, int $month): Carbon
    {
        return $this->businessDayForYearMonth($year, $month, (int) $card->close_day);
    }

    protected function dueDateForMonth(Card $card, int $year, int $month): Carbon
    {
        $next = Carbon::create($year, $month, 1)->addMonth();

        return $this->businessDayForYearMonth($next->year, $next->month, (int) $card->due_day);
    }

    protected function currentCycle(Card $card, CarbonInterface $ref): array
    {
        $closeThis = $this->closeDateForMonth($card, $ref->year, $ref->month);

        if ($ref->lessThanOrEqualTo($closeThis)) {
            $prev = $ref->copy()->subMonth();
            $closePrev = $this->closeDateForMonth($card, $prev->year, $prev->month);
            $start = $closePrev->addDay()->startOfDay();
            $end = $closeThis->copy()->endOfDay();
            $invoiceMonth = $closeThis->format('Y-m');
        } else {
            $next = $ref->copy()->addMonth();
            $closeNext = $this->closeDateForMonth($card, $next->year, $next->month);
            $start = $closeThis->addDay()->startOfDay();
            $end = $closeNext->copy()->endOfDay();
            $invoiceMonth = $closeNext->format('Y-m');
        }

        return [
            'start' => $start,
            'end' => $end,
            'invoice_month' => $invoiceMonth,
            'close_date' => $closeThis,
            'due_date' => $this->dueDateForMonth($card, (int) substr($invoiceMonth, 0, 4), (int) substr($invoiceMonth, 5, 2)),
        ];
    }
}
