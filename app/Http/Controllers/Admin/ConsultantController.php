<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ConsultantController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);

        // Gate simples por role
        $this->middleware(function ($request, $next) {
            abort_if($request->user()->role !== 'admin', 403);

            return $next($request);
        });
    }

    public function index()
    {
        // Lista usuários consultores + conta de clientes vinculados ao perfil Consultant
        $consultants = User::where('role', 'consultant')
            ->with('consultant:id,user_id')                 // carrega o perfil
            ->withCount('consultant.clients as clients_count') // conta clientes
            ->orderBy('name')
            ->paginate(12);

        return view('admin.consultants.index', compact('consultants'));
    }

    public function create()
    {
        return view('admin.consultants.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
        ]);

        DB::transaction(function () use ($data) {
            // cria o usuário
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'consultant',
            ]);

            // cria o perfil Consultant (necessário para /{consultant}/...)
            Consultant::create([
                'user_id' => $user->id,
            ]);
        });

        return redirect()
            ->route('admin.consultants.index')
            ->with('status', 'Consultor criado.');
    }

    public function show(User $consultant)
    {
        abort_if($consultant->role !== 'consultant', 404);

        $consultant->load([
            'consultant:id,user_id',
            'consultant.clients.user:id,name,email',
        ]);

        return view('admin.consultants.show', compact('consultant'));
    }

    public function edit(User $consultant)
    {
        abort_if($consultant->role !== 'consultant', 404);

        return view('admin.consultants.edit', compact('consultant'));
    }

    public function update(Request $request, User $consultant)
    {
        abort_if($consultant->role !== 'consultant', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$consultant->id],
        ]);

        $consultant->update($data);

        return redirect()
            ->route('admin.consultants.index')
            ->with('status', 'Consultor atualizado.');
    }

    public function destroy(User $consultant)
    {
        abort_if($consultant->role !== 'consultant', 404);

        DB::transaction(function () use ($consultant) {
            // apaga o perfil Consultant antes (ou garanta cascade na FK)
            $consultant->consultant()?->delete();
            $consultant->delete();
        });

        return back()->with('status', 'Consultor removido.');
    }
}
