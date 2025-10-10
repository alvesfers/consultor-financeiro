@extends('layouts.app')

@section('content')
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
        <h1 class="tw-text-2xl tw-font-bold">Tarefas</h1>
        <a href="{{ route('consultants.tasks.create') }}" class="tw-btn tw-btn-primary">Nova tarefa</a>
    </div>

    <x-alert-status />

    <div class="tw-card tw-bg-base-100 tw-shadow tw-overflow-x-auto">
        <table class="tw-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th class="tw-w-40">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $t)
                    <tr>
                        <td>{{ $t->title }}</td>
                        <td>{{ optional($t->due_date)->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if ($t->done)
                                <span class="tw-badge tw-badge-success">Concluída</span>
                            @else
                                <span class="tw-badge">Pendente</span>
                            @endif
                        </td>
                        <td class="tw-flex tw-gap-2">
                            <a class="tw-btn tw-btn-xs tw-btn-outline"
                                href="{{ route('consultants.tasks.edit', $t) }}">Editar</a>
                            <form method="POST" action="{{ route('consultants.tasks.destroy', $t) }}"
                                onsubmit="return confirm('Excluir tarefa?')">
                                @csrf @method('DELETE')
                                <button class="tw-btn tw-btn-xs tw-btn-error">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="tw-text-center tw-text-sm tw-opacity-70">Nenhuma tarefa.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tw-mt-4">{{ $tasks->links() }}</div>
@endsection
