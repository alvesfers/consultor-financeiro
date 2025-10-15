@extends('layouts.app')

@section('content')
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Clientes do consultor</h1>
            <p class="text-sm opacity-70">Gerencie os clientes vinculados ao seu perfil.</p>
        </div>

        <a href="{{ route('consultant.clients.create', ['consultant' => $consultantModel->id]) }}"
            class="btn btn-primary btn-sm sm:btn-md">
            <i class="fa fa-user-plus mr-2"></i> Novo cliente
        </a>
    </div>

    <x-alert-status />

    {{-- BARRA DE FERRAMENTAS --}}
    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 mb-4">
        {{-- Busca rápida --}}
        <form method="GET" class="join w-full md:w-auto">
            <input name="q" value="{{ request('q') }}" type="text" placeholder="Buscar por nome ou e-mail"
                class="input input-bordered join-item w-full md:w-80" />
            <button class="btn btn-ghost join-item" type="submit">
                <i class="fa fa-search"></i>
            </button>
            @if (request()->filled('q'))
                <a href="{{ route('consultant.clients.index', ['consultant' => $consultantModel->id]) }}"
                    class="btn btn-ghost join-item" title="Limpar">
                    <i class="fa fa-times"></i>
                </a>
            @endif
        </form>

        {{-- Filtro de status (opcional) --}}
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="q" value="{{ request('q') }}">
            <select name="status" class="select select-bordered select-sm md:select-md">
                <option value="">Todos os status</option>
                @foreach (['ativo' => 'Ativo', 'inativo' => 'Inativo', 'pendente' => 'Pendente'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost btn-sm md:btn-md" type="submit">
                Filtrar
            </button>
        </form>
    </div>

    {{-- TABELA --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr class="bg-base-200">
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th class="whitespace-nowrap">Status</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($clients as $client)
                            @php
                                $name = $client->user?->name ?? '—';
                                $email = $client->user?->email ?? '—';
                                $status = $client->status ?? 'pendente';
                                $badge = match ($status) {
                                    'ativo' => 'badge-success',
                                    'inativo' => 'badge-neutral',
                                    'pendente' => 'badge-warning',
                                    default => 'badge-ghost',
                                };
                                $initials = collect(explode(' ', $name))
                                    ->filter()
                                    ->map(fn($p) => mb_substr($p, 0, 1))
                                    ->take(2)
                                    ->implode('');
                            @endphp

                            <tr class="hover">
                                <td class="max-w-64">
                                    <div class="flex items-center gap-3">
                                        <div class="avatar placeholder">
                                            <div class="w-8 rounded-full bg-base-300">
                                                <span class="text-xs">{{ $initials ?: '■' }}</span>
                                            </div>
                                        </div>
                                        <div class="truncate" title="{{ $name }}">{{ $name }}</div>
                                    </div>
                                </td>

                                <td class="truncate max-w-72" title="{{ $email }}">{{ $email }}</td>

                                <td>
                                    <span class="badge badge-sm {{ $badge }}">{{ $status }}</span>
                                </td>

                                <td class="text-right">
                                    {{-- Desktop: grupo de botões --}}
                                    <div class="hidden sm:inline-flex join">
                                        <a class="btn btn-xs join-item"
                                            href="{{ route('consultant.clients.show', ['consultant' => $consultantModel->id, 'client' => $client->id]) }}">
                                            Ver
                                        </a>

                                        <a class="btn btn-xs btn-outline join-item"
                                            href="{{ route('consultant.clients.edit', ['consultant' => $consultantModel->id, 'client' => $client->id]) }}">
                                            Editar
                                        </a>

                                        <form method="POST"
                                            action="{{ route('consultant.clients.destroy', ['consultant' => $consultantModel->id, 'client' => $client->id]) }}"
                                            onsubmit="return confirm('Remover este cliente?')" class="join-item">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-xs btn-error">Remover</button>
                                        </form>
                                    </div>

                                    {{-- Mobile: dropdown compacto --}}
                                    <div class="dropdown dropdown-end sm:hidden">
                                        <div tabindex="0" role="button" class="btn btn-ghost btn-xs">
                                            <i class="fa fa-ellipsis-v"></i>
                                        </div>
                                        <ul tabindex="0"
                                            class="menu menu-sm dropdown-content z-[1] p-2 shadow bg-base-100 rounded-box w-40">
                                            <li>
                                                <a
                                                    href="{{ route('consultant.clients.show', ['consultant' => $consultantModel->id, 'client' => $client->id]) }}">Ver</a>
                                            </li>
                                            <li>
                                                <a
                                                    href="{{ route('consultant.clients.edit', ['consultant' => $consultantModel->id, 'client' => $client->id]) }}">Editar</a>
                                            </li>
                                            <li>
                                                <form method="POST"
                                                    action="{{ route('consultant.clients.destroy', ['consultant' => $consultantModel->id, 'client' => $client->id]) }}"
                                                    onsubmit="return confirm('Remover este cliente?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-error">Remover</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="alert justify-center my-4">
                                        <i class="fa fa-users"></i>
                                        <span class="text-sm">Nenhum cliente encontrado.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- PAGINAÇÃO --}}
    <div class="mt-4 flex justify-end">
        {{ $clients->withQueryString()->links() }}
    </div>
@endsection
