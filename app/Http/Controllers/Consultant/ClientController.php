<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
        $this->middleware(function ($request, $next) {
            abort_if($request->user()->role !== 'consultant', 403);

            return $next($request);
        });
    }

public function index(Request $request, $consultant)
{
    $consultantModel = Consultant::findOrFail($consultant);

    $this->authorize('viewAny', [Client::class, $consultantModel]);

    $clients = Client::where('consultant_id', $consultantModel->id)->paginate(10);

    return view('consultants.clients.index', compact('clients', 'consultantModel'));
}

public function show($consultant, Client $client)
{
    $this->authorize('view', $client);

    return view('consultants.clients.show', compact('client'));
}


    public function create()
    {
        return view('consultants.clients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
        ]);

        $data['role'] = 'client';
        $data['password'] = bcrypt($data['password']);

        User::create($data);

        return redirect()->route('consultants.clients.index')->with('status', 'Cliente criado.');
    }
    public function edit(User $client)
    {
        abort_if($client->role !== 'client', 404);

        return view('consultants.clients.edit', compact('client'));
    }

    public function update(Request $request, User $client)
    {
        abort_if($client->role !== 'client', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$client->id],
        ]);

        $client->update($data);

        return redirect()->route('consultants.clients.index')->with('status', 'Cliente atualizado.');
    }

    public function destroy(User $client)
    {
        abort_if($client->role !== 'client', 404);
        $client->delete();

        return back()->with('status', 'Cliente removido.');
    }
}
