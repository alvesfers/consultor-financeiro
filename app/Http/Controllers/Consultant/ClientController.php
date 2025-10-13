<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Client;
use App\Models\Consultant;
use App\Models\Goal;
use App\Models\Task;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);

        // Garante que só CONSULTOR acessa
        $this->middleware(function ($request, $next) {
            abort_if($request->user()->role !== 'consultant', 403);

            return $next($request);
        });
    }

    /**
     * Lista clientes do consultor passado na rota.
     */
    public function index(Request $request, int $consultant)
    {
        $consultantModel = Consultant::findOrFail($consultant);
        abort_if($request->user()->consultant?->id !== $consultantModel->id, 403);
        $this->authorize('viewAny', Client::class);

        $clients = Client::with('user:id,name,email')
            ->where('consultant_id', $consultantModel->id)
            ->orderByDesc('id')
            ->paginate(10);

        return view('consultants.clients.index', compact('clients', 'consultantModel'));
    }

    /**
     * Detalhe do cliente.
     */
    public function show(Request $request, int $consultant, Client $client)
    {
        // Segurança de escopo
        abort_if($request->user()->consultant?->id !== $consultant, 403);
        abort_if($client->consultant_id !== $consultant, 404);

        $this->authorize('view', $client);

        // Montagem dos "cards" e listas básicas
        $accounts = Account::where('client_id', $client->id)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'on_budget', 'opening_balance']);

        // Saldo atual = aberturas + soma de transações (visão simplificada)
        $balance = (float) $accounts->sum('opening_balance')
                 + (float) Transaction::where('client_id', $client->id)->sum('amount');

        $tasksPendingCount = Task::where('client_id', $client->id)->pending()->count();
        $goalsPendingCount = Goal::where('client_id', $client->id)->pending()->count();

        // Últimas 10 transações (com conta e categoria)
        $recentTransactions = Transaction::with(['account:id,name', 'category:id,name'])
            ->where('client_id', $client->id)
            ->orderByDesc('date')
            ->take(10)
            ->get(['id', 'client_id', 'account_id', 'date', 'amount', 'notes']);

        // (Se amanhã quiser adicionar charts aqui, é só preencher $chartCategories/$chartSubcategories)
        $chartCategories = ['labels' => [], 'values' => []];
        $chartSubcategories = ['labels' => [], 'values' => []];

        return view('consultants.clients.show', [
            'client' => $client,
            'consultantId' => $consultant,
            'balance' => $balance,
            'tasksPendingCount' => $tasksPendingCount,
            'goalsPendingCount' => $goalsPendingCount,
            'accounts' => $accounts,
            'recentTransactions' => $recentTransactions,
            'chartCategories' => $chartCategories,
            'chartSubcategories' => $chartSubcategories,
        ]);
    }

    /**
     * Form de criação.
     */
    public function create(Request $request, int $consultant)
    {
        abort_if($request->user()->consultant?->id !== $consultant, 403);

        return view('consultants.clients.create', ['consultantId' => $consultant]);
    }

    /**
     * Cria usuário + vínculo Client para o consultor da rota.
     */
    public function store(Request $request, int $consultant)
    {
        $consultantModel = Consultant::findOrFail($consultant);
        abort_if($request->user()->consultant?->id !== $consultantModel->id, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => 'client',
        ]);

        Client::create([
            'user_id' => $user->id,
            'consultant_id' => $consultantModel->id,
            'status' => Client::STATUS_ATIVO,
        ]);

        return redirect()
            ->route('consultants.clients.index', ['consultant' => $consultantModel->id])
            ->with('status', 'Cliente criado.');
    }

    /**
     * Form de edição.
     */
    public function edit(Request $request, int $consultant, Client $client)
    {
        abort_if($request->user()->consultant?->id !== $consultant, 403);
        abort_if($client->consultant_id !== $consultant, 404);

        $this->authorize('update', $client);

        // geralmente editamos os dados no User
        $user = $client->user()->first(['id', 'name', 'email']);

        return view('consultants.clients.edit', compact('client', 'user'));
    }

    /**
     * Atualiza dados (nome/email do User).
     */
    public function update(Request $request, int $consultant, Client $client)
    {
        abort_if($request->user()->consultant?->id !== $consultant, 403);
        abort_if($client->consultant_id !== $consultant, 404);

        $this->authorize('update', $client);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$client->user_id],
        ]);

        $client->user->update($data);

        return redirect()
            ->route('consultants.clients.index', ['consultant' => $consultant])
            ->with('status', 'Cliente atualizado.');
    }

    /**
     * Remove o cliente (User + Client).
     */
    public function destroy(Request $request, int $consultant, Client $client)
    {
        abort_if($request->user()->consultant?->id !== $consultant, 403);
        abort_if($client->consultant_id !== $consultant, 404);

        $this->authorize('delete', $client);

        // apaga o User; o Client deve ter FK com cascadeOnDelete()
        $client->user->delete();

        return back()->with('status', 'Cliente removido.');
    }

    // app/Http/Controllers/Consultant/ClientController.php

    public function dashboard(Request $request, int $consultant, \App\Models\Client $client)
    {

        abort_if($request->user()->consultant?->id !== $consultant, 403);
        abort_if($client->consultant_id !== $consultant, 404);

        $this->authorize('view', $client);

        $tz = $request->user()->timezone ?? 'America/Sao_Paulo';

        $accounts = \App\Models\Account::where('client_id', $client->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $recentTransactions = \App\Models\Transaction::with(['account:id,name'])
            ->where('client_id', $client->id)
            ->orderByDesc('date')
            ->take(10)
            ->get();

        $tasksExpiring = \App\Models\Task::with(['client.user:id,name'])
            ->where('client_id', $client->id)
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now($tz), now($tz)->addDays(7)])
            ->orderBy('due_at')
            ->take(6)
            ->get();

        $stats = [
            'accounts' => $accounts->count(),
            'tasks_due' => $tasksExpiring->count(),
            'tx_last10' => $recentTransactions->count(),
        ];

        return view('client.dashboard', [
            'client' => $client,
            'stats' => $stats,
            'accounts' => $accounts,
            'recentTransactions' => $recentTransactions,
            'tasksExpiring' => $tasksExpiring,
            'asConsultant' => true,
            'consultantId' => $consultant,
        ]);
    }
}
