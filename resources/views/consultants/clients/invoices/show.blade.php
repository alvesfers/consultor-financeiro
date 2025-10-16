@extends('layouts.app')

@section('content')
    <div class="tw-p-4">
        <h1 class="tw-text-2xl tw-font-bold mb-4">
            <i class="fa-solid fa-receipt mr-2"></i>
            Detalhes da Fatura ({{ $monthKey }})
        </h1>

        <div class="tw-bg-base-100 tw-rounded-xl tw-overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($tx->date)->format('d/m/Y') }}</td>
                            <td>{{ $tx->type }}</td>
                            <td class="tw-text-right">
                                {{ $tx->amount < 0 ? 'R$ ' . number_format(-$tx->amount, 2, ',', '.') : 'R$ ' . number_format($tx->amount, 2, ',', '.') }}
                            </td>
                            <td>{{ $tx->status }}</td>
                            <td>{{ $tx->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="tw-text-center">Nenhuma transação nesta fatura.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <a href="{{ url()->previous() }}" class="btn">← Voltar</a>
        </div>
    </div>
@endsection
