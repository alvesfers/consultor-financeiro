<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\Task;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalConsultants = Consultant::count();
        $totalClients = Client::count();

        // Top 5 consultores por nº de clientes
        $topConsultants = Consultant::with(['user:id,name'])
            ->withCount('clients')
            ->orderByDesc('clients_count')
            ->take(5)
            ->get();

        // Tarefas vencendo em 7 dias (de qualquer cliente)
        $tasksExpiring = Task::with(['client.user:id,name'])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now(), now()->addDays(7)])
            ->orderBy('due_at')
            ->take(6)
            ->get();

        return view('dashboards.admin', compact(
            'totalConsultants',
            'totalClients',
            'topConsultants',
            'tasksExpiring'
        ));
    }
}
