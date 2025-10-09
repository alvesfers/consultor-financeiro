<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ConsultantController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
        // simples gate por role
        $this->middleware(function ($request, $next) {
            abort_if($request->user()->role !== 'admin', 403);

            return $next($request);
        });
    }

    public function index()
    {
        $consultants = User::where('role', 'consultant')->paginate(12);

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

        $data['role'] = 'consultant';
        $data['password'] = bcrypt($data['password']);

        User::create($data);

        return redirect()->route('admin.consultants.index')->with('status', 'Consultor criado.');
    }

    public function show(User $consultant)
    {
        abort_if($consultant->role !== 'consultant', 404);

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

        return redirect()->route('admin.consultants.index')->with('status', 'Consultor atualizado.');
    }

    public function destroy(User $consultant)
    {
        abort_if($consultant->role !== 'consultant', 404);
        $consultant->delete();

        return back()->with('status', 'Consultor removido.');
    }
}
