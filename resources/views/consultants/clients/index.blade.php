@extends('layouts.app')

@section('content')
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
        <h1 class="tw-text-2xl tw-font-bold">Consultores</h1>
        <a href="{{ route('admin.consultants.create') }}" class="tw-btn tw-btn-primary">Novo consultor</a>
    </div>

    <x-alert-status />

    <div class="tw-overflow-x-auto tw-card tw-bg-base-100 tw-shadow">
        <table class="tw-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th class="tw-w-40">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($consultants as $c)
                    <tr>
                        <td>{{ $c->name }}</td>
                        <td>{{ $c->email }}</td>
                        <td class="tw-flex tw-gap-2">
                            <a class="tw-btn tw-btn-xs" href="{{ route('admin.consultants.show', $c) }}">Ver</a>
                            <a class="tw-btn tw-btn-xs tw-btn-outline"
                                href="{{ route('admin.consultants.edit', $c) }}">Editar</a>
                            <form method="POST" action="{{ route('admin.consultants.destroy', $c) }}"
                                onsubmit="return confirm('Remover este consultor?')">
                                @csrf @method('DELETE')
                                <button class="tw-btn tw-btn-xs tw-btn-error">Remover</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="tw-text-center tw-text-sm tw-opacity-70">Nenhum consultor.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tw-mt-4">{{ $consultants->links() }}</div>
@endsection
