@extends('layouts.app')

@section('content')
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
        <h1 class="tw-text-2xl tw-font-bold">Clientes</h1>
        <a href="{{ route('consultant.clients.create') }}" class="tw-btn tw-btn-primary">Novo cliente</a>
    </div>

    <x-alert-status />

    <div class="tw-card tw-bg-base-100 tw-shadow tw-overflow-x-auto">
        <table class="tw-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th class="tw-w-40">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr>
                        <td>{{ $client->name }}</td>
                        <td>{{ $client->email }}</td>
                        <td class="tw-flex tw-gap-2">
                            <a class="tw-btn tw-btn-xs" href="{{ route('consultant.clients.show', $client) }}">Ver</a>
                            <a class="tw-btn tw-btn-xs tw-btn-outline"
                                href="{{ route('consultant.clients.edit', $client) }}">Editar</a>
                            <form method="POST" action="{{ route('consultant.clients.destroy', $client) }}"
                                onsubmit="return confirm('Remover este cliente?')">
                                @csrf @method('DELETE')
                                <button class="tw-btn tw-btn-xs tw-btn-error">Remover</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="tw-text-center tw-text-sm tw-opacity-70">Nenhum cliente.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tw-mt-4">{{ $clients->links() }}</div>
@endsection
