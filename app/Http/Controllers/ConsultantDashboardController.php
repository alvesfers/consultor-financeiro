<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class ConsultantDashboardController extends Controller
{
    public function index()
    {
        $consultant = Auth::user()->consultant;
        abort_if(! $consultant, 403);

        $clients = Client::where('consultant_id', $consultant->id)->pluck('id');

        $clientsCount = $clients->count();

        // Últimas transações dos clientes do consultor
        $recentTransactions = Transaction::with(['client.user:id,name'])
            ->whereIn('client_id', $clients)
            ->orderByDesc('date')
            ->take(10)
            ->get();

        // Tarefas vencendo em 7 dias
        $tasksExpiring = Task::with(['client.user:id,name'])
            ->whereIn('client_id', $clients)
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now(), now()->addDays(7)])
            ->orderBy('due_at')
            ->take(6)
            ->get();

        // Resumo simples
        $stats = [
            'clients' => $clientsCount,
            'tasks_due' => $tasksExpiring->count(),
            'tx_last10' => $recentTransactions->count(),
        ];

        return view('dashboards.consultant', compact(
            'stats',
            'recentTransactions',
            'tasksExpiring'
        ));
    }
}
