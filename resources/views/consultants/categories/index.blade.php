@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <i class="fa-solid fa-tags"></i> Categorias
        </h1>

        <a href="{{ route('consultant.categories.index', ['consultant' => $consultant]) }}" class="btn btn-ghost"><i
                class="fa-solid fa-rotate-right me-2"></i>Atualizar</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-4"><i class="fa-regular fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error mb-4">
            <i class="fa-regular fa-circle-xmark me-2"></i>
            <ul class="list-disc ms-5">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Criar --}}
        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title"><i class="fa-solid fa-plus me-2"></i>Nova categoria</h2>

                <form method="POST" action="{{ route('consultant.categories.store', ['consultant' => $consultant]) }}"
                    class="space-y-3">
                    @csrf
                    <div class="form-control">
                        <label class="label"><span class="label-text">Nome</span></label>
                        <input type="text" name="name" class="input input-bordered" required maxlength="120" />
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text">Categoria pai (opcional)</span></label>
                        <select name="parent_id" class="select select-bordered">
                            <option value="">— Sem pai —</option>
                            @foreach ($parents as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="checkbox" name="is_active" class="toggle toggle-primary" checked />
                        <span class="label-text">Ativa</span>
                    </label>

                    <div class="card-actions justify-end">
                        <button class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Lista --}}
        <div class="lg:col-span-2 card bg-base-100 border border-base-300">
            <div class="card-body">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="card-title"><i class="fa-solid fa-list me-2"></i>Listagem</h2>
                    <form method="GET" class="join">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nome..."
                            class="input input-bordered join-item" />
                        <button class="btn btn-ghost join-item"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Pai</th>
                                <th>Status</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $c)
                                <tr>
                                    <td class="font-medium">{{ $c->name }}</td>
                                    <td>{{ $c->parent?->name ?? '—' }}</td>
                                    <td>
                                        @if ($c->is_active)
                                            <span class="badge badge-success gap-1"><i
                                                    class="fa-solid fa-circle-dot text-xs"></i> ativa</span>
                                        @else
                                            <span class="badge badge-ghost gap-1"><i
                                                    class="fa-regular fa-circle text-xs"></i> inativa</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="join">
                                            <a href="{{ route('consultant.categories.edit', ['consultant' => $consultant, 'category' => $c->id]) }}"
                                                class="btn btn-sm btn-ghost join-item"><i
                                                    class="fa-regular fa-pen-to-square"></i></a>

                                            @if (Route::has('consultant.categories.toggle'))
                                                <form method="POST"
                                                    action="{{ route('consultant.categories.toggle', ['consultant' => $consultant, 'category' => $c->id]) }}"
                                                    class="join-item">
                                                    @csrf @method('PATCH')
                                                    <button class="btn btn-sm btn-ghost" title="Ativar/Desativar">
                                                        <i class="fa-solid fa-power-off"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST"
                                                action="{{ route('consultant.categories.destroy', ['consultant' => $consultant, 'category' => $c->id]) }}"
                                                class="join-item" onsubmit="return confirm('Excluir esta categoria?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost text-error"><i
                                                        class="fa-regular fa-trash-can"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center opacity-70">Sem categorias</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection
