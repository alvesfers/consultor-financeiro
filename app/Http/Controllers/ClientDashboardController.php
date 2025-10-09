<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientDashboardController extends Controller
{
    public function index()
    {
        $client = Auth::user()->client;
        abort_if(! $client, 403);

        // Contas do cliente
        $accounts = Account::where('client_id', $client->id)->get();

        // Saldo atual simples (opening_balance + soma das transações)
        $opening = (float) $accounts->sum('opening_balance');
        $txSum = (float) Transaction::where('client_id', $client->id)->sum('amount');
        $balance = $opening + $txSum;

        // Últimas transações
        $recentTransactions = Transaction::with(['account', 'client'])
            ->where('client_id', $client->id)
            ->orderByDesc('date')
            ->take(10)
            ->get();

        // Categoria com mais gastos nos últimos 30 dias
        $since = now()->subDays(30);

        $topCategory = TransactionCategory::query()
            ->select([
                'category_id',
                DB::raw('SUM(CASE WHEN transactions.amount < 0 THEN -transactions.amount ELSE 0 END) as spent'),
            ])
            ->join('transactions', 'transactions.id', '=', 'transaction_categories.transaction_id')
            ->where('transactions.client_id', $client->id)
            ->where('transactions.date', '>=', $since)
            ->groupBy('category_id')
            ->orderByDesc('spent')
            ->with('category')
            ->first();

        return view('dashboards.client', compact(
            'accounts',
            'balance',
            'recentTransactions',
            'topCategory'
        ));
    }
}
