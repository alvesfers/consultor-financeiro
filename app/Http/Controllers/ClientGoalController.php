<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClientGoalController extends Controller
{
    /**
     * Tela de comparativo mensal de metas por categoria
     *
     * Filtros (query string):
     * - months: quantidade de meses (padrão: 6)
     * - end: mês final (YYYY-MM), padrão: mês atual
     * - category_id: filtra uma categoria específica
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

        $clientId = (int) $client->id;

        // ---------------- Filtros ----------------
        $monthsCount = max(1, (int) $request->integer('months', 6));
        $endYm = $request->input('end', Carbon::now($tz)->format('Y-m'));
        $endMonth = Carbon::createFromFormat('Y-m', $endYm, $tz)->startOfMonth();
        $startMonth = (clone $endMonth)->subMonths($monthsCount - 1)->startOfMonth();

        $filterCategoryId = $request->integer('category_id') ?: null;

        // Lista de meses (mais novo -> mais antigo)
        $months = [];
        $cursor = $endMonth->copy();
        while ($cursor->gte($startMonth)) {
            $months[] = $cursor->copy();
            $cursor->subMonth();
        }

        // ---------------- Categorias de DESPESAS (group_id = 5) ----------------
        // (sem parent_id; sua categories é "flat")
        $expenseRootMap = DB::table('categories')
            ->where('is_active', true)
            ->where('group_id', 5)
            ->orderBy('name')
            ->pluck('name', 'id'); // [id => name]

        $expenseRootIds = array_map('intval', array_keys($expenseRootMap->toArray()));

        // Se houver filtro, restringe o universo de categorias
        if ($filterCategoryId) {
            $expenseRootIds = array_values(array_intersect($expenseRootIds, [$filterCategoryId]));
            if (empty($expenseRootIds)) {
                // filtro não pertence a despesas -> força ausência de resultados
                $expenseRootIds = [-1];
            }
        }

        // ---------------- Metas (category_goals) ----------------
        $goalsRows = DB::table('category_goals as cg')
            ->join('categories as c', 'c.id', '=', 'cg.category_id')
            ->where('cg.client_id', $clientId)
            ->whereBetween('cg.month', [$startMonth->toDateString(), $endMonth->toDateString()])
            ->when(! empty($expenseRootIds), fn ($q) => $q->whereIn('cg.category_id', $expenseRootIds))
            ->orderBy('cg.month', 'desc')
            ->orderBy('c.name')
            ->get([
                'cg.category_id',
                'cg.month',
                'cg.limit_amount',
                'c.name as category_name',
            ]);

        // Agrupa metas por (Y-m -> category_id)
        $goalsByMonthCat = [];
        foreach ($goalsRows as $r) {
            $ym = Carbon::parse($r->month, $tz)->format('Y-m');
            $goalsByMonthCat[$ym][(int) $r->category_id] = [
                'category_id' => (int) $r->category_id,
                'category_name' => (string) $r->category_name,
                'limit_amount' => (float) $r->limit_amount,
            ];
        }

        // ---------------- Subcategorias (para mapear root e nome) ----------------
        $subs = DB::table('subcategories')
            ->where('is_active', true)
            ->when(! empty($expenseRootIds), fn ($q) => $q->whereIn('category_id', $expenseRootIds))
            ->get(['id', 'category_id', 'name']);

        // subId => ['category_id'=>X, 'name'=>Y]
        $subsInfo = [];
        // rootId => [subId, subId, ...]
        $subByParent = [];
        foreach ($expenseRootIds as $cid) {
            $subByParent[$cid] = [];
        }
        foreach ($subs as $s) {
            $sid = (int) $s->id;
            $cid = (int) $s->category_id;
            $subsInfo[$sid] = ['category_id' => $cid, 'name' => (string) $s->name];
            if (! isset($subByParent[$cid])) {
                $subByParent[$cid] = [];
            }
            $subByParent[$cid][] = $sid;
        }

        // ---------------- Gastos por mês/categoria + detalhamento por sub ----------------
        $hasType = Schema::hasColumn('transactions', 'type');

        // [ym][category_id] = total gasto (positivo)
        $spentByMonthCat = [];
        // [ym] = total geral do mês
        $totalSpentByMonth = [];
        // [ym][category_id] = [ [id, name, spent], ... ]  (subcategorias daquele root)
        $spentSubsByMonthCat = [];

        foreach ($months as $m) {
            $ym = $m->format('Y-m');
            $start = $m->copy()->startOfMonth()->toDateString();
            $end = $m->copy()->endOfMonth()->toDateString();

            $base = DB::table('transactions as t')
                ->join('transaction_categories as tc', 'tc.transaction_id', '=', 't.id')
                ->where('t.client_id', $clientId)
                ->whereBetween('t.date', [$start, $end]);

            if ($hasType) {
                $base->where('t.type', 'expense');
            } else {
                $base->where('t.amount', '<', 0);
            }

            $rows = $base->select([
                'tc.category_id',
                'tc.subcategory_id',
                DB::raw('SUM(ABS(t.amount)) as total_amount'),
            ])
                ->groupBy('tc.category_id', 'tc.subcategory_id')
                ->get();

            $byRoot = [];
            $bySub = [];
            $monthTotal = 0.0;

            foreach ($rows as $r) {
                $catId = (int) ($r->category_id ?? 0);
                $subId = (int) ($r->subcategory_id ?? 0);
                $sum = (float) $r->total_amount;

                // Descobre o root (categoria de despesas)
                $rootId = $catId;
                if ($subId && isset($subsInfo[$subId])) {
                    $rootId = (int) $subsInfo[$subId]['category_id'];
                }

                // Mantém apenas categorias do grupo Despesas
                if (! $rootId || ! in_array($rootId, $expenseRootIds, true)) {
                    continue;
                }

                $byRoot[$rootId] = ($byRoot[$rootId] ?? 0.0) + $sum;
                $monthTotal += $sum;

                // Detalhamento por sub
                $subKey = $subId ?: 0; // 0 = “Sem subcategoria”
                if (! isset($bySub[$rootId][$subKey])) {
                    $bySub[$rootId][$subKey] = [
                        'id' => $subKey,
                        'name' => $subId ? $subsInfo[$subId]['name'] : '(Sem subcategoria)',
                        'spent' => 0.0,
                    ];
                }
                $bySub[$rootId][$subKey]['spent'] += $sum;
            }

            // Se tiver filtro por categoria, restringe e recalcula total
            if ($filterCategoryId) {
                $byRoot = array_key_exists($filterCategoryId, $byRoot)
                    ? [$filterCategoryId => $byRoot[$filterCategoryId]]
                    : [];
                $bySub = array_key_exists($filterCategoryId, $bySub)
                    ? [$filterCategoryId => $bySub[$filterCategoryId]]
                    : [];
                $monthTotal = array_sum($byRoot);
            }

            // Ordena subcategorias por gasto desc
            foreach ($bySub as $rid => $items) {
                usort($items, fn ($a, $b) => $b['spent'] <=> $a['spent']);
                $bySub[$rid] = array_values($items);
            }

            $spentByMonthCat[$ym] = $byRoot;
            $totalSpentByMonth[$ym] = $monthTotal;
            $spentSubsByMonthCat[$ym] = $bySub;
        }

        // ---------------- Monta payload p/ view ----------------
        $cards = [];

        foreach ($months as $m) {
            $ym = $m->format('Y-m');
            $lbl = $m->locale('pt_BR')->translatedFormat('F/Y');

            $catsWithGoals = array_keys($goalsByMonthCat[$ym] ?? []);
            $catsWithSpent = array_keys($spentByMonthCat[$ym] ?? []);
            $allCatIdsForMonth = array_values(array_unique(array_merge($catsWithGoals, $catsWithSpent)));

            if ($filterCategoryId) {
                $allCatIdsForMonth = array_values(array_intersect($allCatIdsForMonth, [$filterCategoryId]));
            }

            $list = [];
            foreach ($allCatIdsForMonth as $cid) {
                $limit = isset($goalsByMonthCat[$ym][$cid]) ? (float) $goalsByMonthCat[$ym][$cid]['limit_amount'] : null;
                $name = $goalsByMonthCat[$ym][$cid]['category_name'] ?? ($expenseRootMap[$cid] ?? 'Categoria');
                $spent = (float) ($spentByMonthCat[$ym][$cid] ?? 0.0);

                if ($limit === null) {
                    $balance = null;
                    $percent = null;
                } else {
                    $balance = $limit - $spent;
                    $percent = $limit > 0 ? min(100, (int) round(($spent / $limit) * 100)) : 0;
                }

                $list[] = [
                    'category_id' => (int) $cid,
                    'category_name' => (string) $name,
                    'limit' => $limit,
                    'spent' => $spent,
                    'balance' => $balance,
                    'percent' => $percent,
                ];
            }

            usort($list, function ($a, $b) {
                if (! is_null($a['percent']) && ! is_null($b['percent'])) {
                    return $b['percent'] <=> $a['percent'];
                }

                return $b['spent'] <=> $a['spent'];
            });

            $cards[] = [
                'ym' => $ym,
                'label' => mb_convert_case($lbl, MB_CASE_TITLE, 'UTF-8'),
                'items' => $list,
                'total_spent' => (float) ($totalSpentByMonth[$ym] ?? 0.0),
                // envia o detalhamento daquele mês (usado no modal)
                'subs_breakdown' => $spentSubsByMonthCat[$ym] ?? [],
            ];
        }

        // Dropdown (apenas despesas)
        $categoryOptions = Category::query()
            ->whereIn('id', array_map('intval', array_keys($expenseRootMap->toArray())))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('consultants.clients.goals.index', [
            'consultantId' => $consultant,
            'cards' => $cards,
            'monthsCount' => $monthsCount,
            'endYm' => $endMonth->format('Y-m'),
            'categoryOptions' => $categoryOptions,
            'filterCategoryId' => $filterCategoryId,
        ]);
    }
}
