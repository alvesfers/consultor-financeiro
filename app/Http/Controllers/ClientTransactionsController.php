<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientTransactionsController extends Controller
{
    /** Base + joins necessários */
    protected function baseQuery(int $clientId)
    {
        return DB::table('transactions as t')
            ->leftJoin('transaction_categories as tc', 'tc.transaction_id', '=', 't.id')
            ->leftJoin('accounts as a', 'a.id', '=', 't.account_id')
            ->leftJoin('cards as c', 'c.id', '=', 't.card_id')
            ->leftJoin('accounts as pa', 'pa.id', '=', 'c.payment_account_id')
            ->where('t.client_id', $clientId);
    }

    /** Filtros usados em index/export */
    protected function applyFilters($q, array $filters)
    {
        if ($filters['group_id']) {
            $q->join('categories as cf', 'cf.id', '=', 'tc.category_id')
                ->where('cf.group_id', $filters['group_id']);
        }
        if ($filters['category_id']) {
            $q->where('tc.category_id', $filters['category_id']);
        }
        if ($filters['subcategory_id']) {
            $q->where('tc.subcategory_id', $filters['subcategory_id']);
        }
        if ($filters['account_id']) {
            $q->where('t.account_id', $filters['account_id']);
        }
        if ($filters['card_id']) {
            $q->where('t.card_id', $filters['card_id']);
        }
        if ($filters['date_start']) {
            $q->whereDate('t.date', '>=', $filters['date_start']);
        }
        if ($filters['date_end']) {
            $q->whereDate('t.date', '<=', $filters['date_end']);
        }
        if ($filters['q'] !== '') {
            $term = '%'.$filters['q'].'%';
            $q->where(function ($qq) use ($term) {
                $qq->where('t.notes', 'like', $term)
                    ->orWhere('t.method', 'like', $term)
                    ->orWhere('t.status', 'like', $term);
            });
        }

        return $q;
    }

    /**
     * Query AGRUPADA sem ANY_VALUE (compatível com MySQL 5.7/MariaDB):
     * 1) base: aplica filtros e calcula grp + bank_id
     * 2) agg:  sum_amount, items_count, max(created_at)
     * 3) rep2: encontra o "representante" do grupo (id com maior created_at; se empatar, maior id)
     * 4) final: junta agg + representante para pegar rótulos (notes, method, status, repr_date, category_id, subcategory_id, bank_id)
     */
    protected function groupedQueryLegacy(int $clientId, array $filters)
    {
        // (1) base
        $base = $this->baseQuery($clientId);
        $this->applyFilters($base, $filters);
        $base->selectRaw('
            t.*,
            tc.category_id, tc.subcategory_id,
            COALESCE(t.parent_transaction_id, t.id) as grp,
            CASE WHEN t.account_id IS NOT NULL THEN a.bank_id ELSE pa.bank_id END as bank_id
        ');

        // (2) agg: por grupo
        $agg = DB::query()->fromSub($base, 'b')
            ->selectRaw('
                b.grp,
                MAX(b.created_at) as grp_created_at,
                SUM(b.amount)     as sum_amount,
                COUNT(*)          as items_count
            ')
            ->groupBy('b.grp');

        // (3) rep1: linhas com created_at = max do grupo
        $rep1 = DB::query()->fromSub($base, 'b1')
            ->join(
                DB::raw('(
                    SELECT grp, MAX(created_at) AS max_created
                    FROM ('.$base->toSql().') as bx
                    GROUP BY grp
                ) m'),
                function ($j) {
                    // ajustar bindings do subselect
                }
            )
            ->mergeBindings($base)
            ->whereColumn('b1.grp', 'm.grp')
            ->whereColumn('b1.created_at', 'm.max_created')
            ->select('b1.grp', 'b1.id');

        // (3b) rep2: se houver mais de um id com mesmo created_at, pega o maior id
        $rep2 = DB::query()->fromSub($rep1, 'r1')
            ->selectRaw('grp, MAX(id) as rep_id')
            ->groupBy('grp');

        // (4) final: junta agg + representante (linha completa da base)
        $final = DB::query()->fromSub($agg, 'agg')
            ->joinSub($rep2, 'r2', 'r2.grp', '=', 'agg.grp')
            ->joinSub($base, 'rep', 'rep.id', '=', 'r2.rep_id')
            ->selectRaw('
                agg.grp,
                agg.grp_created_at,
                agg.sum_amount,
                agg.items_count,
                rep.installment_count,
                rep.notes,
                rep.method,
                rep.status,
                rep.date        as repr_date,
                rep.category_id,
                rep.subcategory_id,
                rep.bank_id
            ');

        return $final;
    }

    /** Busca os itens de cada grupo para o expand (mesma de antes) */
    protected function fetchGroupItems(int $clientId, array $grpIds)
    {
        if (empty($grpIds)) {
            return collect();
        }

        $rows = DB::table('transactions as t')
            ->leftJoin('transaction_categories as tc', 'tc.transaction_id', '=', 't.id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'tc.category_id')
            ->leftJoin('subcategories as sub', 'sub.id', '=', 'tc.subcategory_id')
            ->leftJoin('accounts as a', 'a.id', '=', 't.account_id')
            ->leftJoin('cards as c', 'c.id', '=', 't.card_id')
            ->where('t.client_id', $clientId)
            ->whereIn(DB::raw('COALESCE(t.parent_transaction_id, t.id)'), $grpIds)
            ->selectRaw('
                COALESCE(t.parent_transaction_id, t.id) as grp,
                t.id, t.amount, t.date, t.method, t.status,
                t.installment_count, t.installment_index,
                a.name as account_name,
                c.name as card_name,
                cat.name as category_name,
                sub.name as subcategory_name,
                t.notes
            ')
            ->orderBy('grp')
            ->orderBy('t.installment_index')
            ->orderBy('t.date')
            ->get();

        return $rows->groupBy('grp');
    }

    /** Ordenação */
    protected function applySort($q, string $sort, string $dir)
    {
        $allowed = [
            'grp_created_at' => 'grp_created_at',
            'sum_amount' => 'sum_amount',
            'repr_date' => 'repr_date',
            'notes' => 'notes',
        ];
        $col = $allowed[$sort] ?? 'grp_created_at';
        $direction = strtolower($dir) === 'asc' ? 'asc' : 'desc';

        return $q->orderBy($col, $direction);
    }

    public function index(Request $request, $consultant)
    {
        $user = $request->user();
        $clientId = DB::table('clients')
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->value('id');
        abort_if(! $clientId, 404);

        $filters = [
            'group_id' => $request->integer('group_id') ?: null,
            'category_id' => $request->integer('category_id') ?: null,
            'subcategory_id' => $request->integer('subcategory_id') ?: null,
            'account_id' => $request->integer('account_id') ?: null,
            'card_id' => $request->integer('card_id') ?: null,
            'date_start' => $request->input('date_start'),
            'date_end' => $request->input('date_end'),
            'q' => trim((string) $request->input('q')),
        ];
        $perPage = (int) ($request->integer('per_page') ?: 20);
        $sort = $request->input('sort', 'grp_created_at');
        $dir = $request->input('dir', 'desc');

        // datasets para selects
        $categoryGroups = DB::table('category_groups')
            ->where(function ($w) use ($clientId) {
                $w->whereNull('client_id')->orWhere('client_id', $clientId);
            })
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $categories = DB::table('categories')
            ->where(function ($w) use ($clientId) {
                $w->whereNull('client_id')->orWhere('client_id', $clientId);
            })
            ->where('is_active', true)->orderBy('name')->get(['id', 'group_id', 'name']);

        $subcategories = DB::table('subcategories')
            ->where(function ($w) use ($clientId) {
                $w->whereNull('client_id')->orWhere('client_id', $clientId);
            })
            ->where('is_active', true)->orderBy('name')->get(['id', 'category_id', 'name']);

        $banks = DB::table('banks')->get(['id', 'name', 'logo_svg'])->keyBy('id');

        // paginação por grupo (LEGACY)
        $qGrouped = $this->groupedQueryLegacy($clientId, $filters);
        $paginator = $this->applySort($qGrouped, $sort, $dir)
            ->paginate($perPage)
            ->withQueryString();

        // anexar logos e itens do expand
        $grpIds = $paginator->pluck('grp')->all();
        $itemsByGrp = $this->fetchGroupItems($clientId, $grpIds);

        foreach ($paginator as $g) {
            $bank = $g->bank_id ? ($banks[$g->bank_id] ?? null) : null;
            $g->bank_logo = null;
            if ($bank && ! empty($bank->logo_svg)) {
                $path = str_starts_with($bank->logo_svg, 'storage/')
                    ? $bank->logo_svg
                    : 'storage/'.ltrim($bank->logo_svg, '/');
                $g->bank_logo = asset($path);
            }
            $g->items = $itemsByGrp->get($g->grp, collect());
        }

        // sumário sem ANY_VALUE: soma sobre a agregação
        $agg = $this->groupedQueryLegacy($clientId, $filters); // mesma query
        $sum = DB::query()->fromSub($agg, 'x')
            ->selectRaw('
                SUM(CASE WHEN sum_amount>0 THEN sum_amount ELSE 0 END) as total_in,
                SUM(CASE WHEN sum_amount<0 THEN sum_amount ELSE 0 END) as total_out,
                COUNT(*) as qty
            ')->first();

        $summary = [
            'in' => (float) ($sum->total_in ?? 0),
            'out' => (float) ($sum->total_out ?? 0),
            'count' => (int) ($sum->qty ?? 0),
        ];

        return view('consultants.clients.transactions.index', [
            'consultantId' => (int) $consultant,
            'filters' => $filters,
            'groups' => $paginator,
            'summary' => $summary,
            'categoryGroups' => $categoryGroups,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'perPage' => $perPage,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    /** Export CSV (LEGACY) */
    public function export(Request $request, $consultant): StreamedResponse
    {
        $user = $request->user();
        $clientId = DB::table('clients')
            ->where('user_id', $user->id)
            ->where('consultant_id', $consultant)
            ->value('id');
        abort_if(! $clientId, 404);

        $filters = [
            'group_id' => $request->integer('group_id') ?: null,
            'category_id' => $request->integer('category_id') ?: null,
            'subcategory_id' => $request->integer('subcategory_id') ?: null,
            'account_id' => $request->integer('account_id') ?: null,
            'card_id' => $request->integer('card_id') ?: null,
            'date_start' => $request->input('date_start'),
            'date_end' => $request->input('date_end'),
            'q' => trim((string) $request->input('q')),
        ];
        $sort = $request->input('sort', 'grp_created_at');
        $dir = $request->input('dir', 'desc');

        $rows = $this->applySort($this->groupedQueryLegacy($clientId, $filters), $sort, $dir)->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="transacoes.csv"',
        ];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Grupo', 'Criado em', 'Total', 'Qtd Itens', 'Parcelas', 'Data ref.', 'Notas', 'Método', 'Status']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->grp,
                    $r->grp_created_at,
                    number_format((float) $r->sum_amount, 2, ',', '.'),
                    (int) $r->items_count,
                    $r->installment_count ?: $r->items_count,
                    $r->repr_date,
                    $r->notes,
                    $r->method,
                    $r->status,
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }
}
