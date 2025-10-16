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

        // ---------------- Metas (category_goals) ----------------
        $goalsRows = DB::table('category_goals as cg')
            ->join('categories as c', 'c.id', '=', 'cg.category_id')
            ->where('cg.client_id', $clientId)
            ->whereBetween('cg.month', [$startMonth->toDateString(), $endMonth->toDateString()])
            ->when($filterCategoryId, fn ($q) => $q->where('cg.category_id', $filterCategoryId))
            ->orderBy('cg.month', 'desc')
            ->orderBy('c.name')
            ->get([
                'cg.category_id',
                'cg.month',
                'cg.limit_amount',
                'c.name as category_name',
            ]);

        // Agrupa metas por (month->Y-m, category_id)
        $goalsByMonthCat = [];
        foreach ($goalsRows as $r) {
            $ym = Carbon::parse($r->month, $tz)->format('Y-m');
            $goalsByMonthCat[$ym][(int) $r->category_id] = [
                'category_id' => (int) $r->category_id,
                'category_name' => (string) $r->category_name,
                'limit_amount' => (float) $r->limit_amount,
            ];
        }

        // ---------------- Subcategorias por categoria ----------------
        // (usa tabela subcategories)
        $allGoalCategoryIds = array_unique(
            array_map(
                'intval',
                array_merge(
                    [],
                    ...array_map(fn ($arr) => array_keys($arr ?? []), array_values($goalsByMonthCat ?: []))
                )
            )
        );

        $subByParent = [];
        if (! empty($allGoalCategoryIds)) {
            $subs = DB::table('subcategories')
                ->whereIn('category_id', $allGoalCategoryIds)
                ->where('is_active', true)
                ->get(['id', 'category_id']);

            foreach ($allGoalCategoryIds as $cid) {
                $subByParent[(int) $cid] = [];
            }
            foreach ($subs as $s) {
                $subByParent[(int) $s->category_id][] = (int) $s->id;
            }
        }

        // ---------------- Gastos por mês/categoria ----------------
        // Agora com JOIN em transaction_categories (tc)
        $hasType = Schema::hasColumn('transactions', 'type');

        // [ym][category_id] = total gasto (positivo)
        $spentByMonthCat = [];

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

            // Indexa por categoria "raiz"
            $byRoot = []; // root_category_id => soma positiva
            foreach ($rows as $r) {
                $catId = (int) ($r->category_id ?? 0);
                $subId = (int) ($r->subcategory_id ?? 0);
                $sum = (float) $r->total_amount;

                // Se a subcategoria pertence a alguma raiz, usa a raiz correspondente
                $rootId = $catId;
                foreach ($subByParent as $root => $subs) {
                    if ($subId && in_array($subId, $subs, true)) {
                        $rootId = (int) $root;
                        break;
                    }
                }

                if (! $rootId) {
                    // Transação sem categoria definida: ignora no comparativo de metas
                    continue;
                }

                $byRoot[$rootId] = ($byRoot[$rootId] ?? 0.0) + $sum;
            }

            $spentByMonthCat[$ym] = $byRoot;
        }

        // ---------------- Monta payload para a view ----------------
        $cards = []; // cada mês com suas categorias (meta/gasto/saldo)

        foreach ($months as $m) {
            $ym = $m->format('Y-m');
            $lbl = $m->locale('pt_BR')->translatedFormat('F/Y'); // ex.: outubro/2025
            $list = [];

            $cats = $goalsByMonthCat[$ym] ?? [];

            foreach ($cats as $cid => $meta) {
                $limit = (float) $meta['limit_amount'];
                $spent = (float) ($spentByMonthCat[$ym][$cid] ?? 0.0);
                $saldo = $limit - $spent;
                $pct = $limit > 0 ? min(100, (int) round(($spent / $limit) * 100)) : 0;

                $list[] = [
                    'category_id' => (int) $cid,
                    'category_name' => (string) $meta['category_name'],
                    'limit' => $limit,
                    'spent' => $spent,
                    'balance' => $saldo,
                    'percent' => $pct,
                ];
            }

            // Ordena por maior % gasto
            usort($list, fn ($a, $b) => $b['percent'] <=> $a['percent']);

            $cards[] = [
                'ym' => $ym,
                'label' => mb_convert_case($lbl, MB_CASE_TITLE, 'UTF-8'),
                'items' => $list,
            ];
        }

        // Dropdown de categorias (somente as que têm meta em algum mês do período)
        $categoryOptions = [];
        if (! empty($allGoalCategoryIds)) {
            $categoryOptions = Category::query()
                ->whereIn('id', $allGoalCategoryIds)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

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
