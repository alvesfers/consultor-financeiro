@extends('layouts.app')

@section('content')
    <div class="tw-max-w-4xl tw-mx-auto tw-space-y-8">
        <x-alert-status />

        <div class="tw-card tw-bg-base-100 tw-shadow">
            <div class="tw-card-body">
                <h2 class="tw-card-title">Configurações de Perfil</h2>
                <form method="POST" action="{{ route('settings.profile.update') }}" class="tw-space-y-4">
                    @csrf @method('PUT')
                    <div class="tw-form-control">
                        <label class="tw-label"><span class="tw-label-text">Nome</span></label>
                        <input name="name" class="tw-input tw-input-bordered" value="{{ old('name', $user->name) }}"
                            required>
                        @error('name')
                            <span class="tw-text-error tw-text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="tw-form-control">
                        <label class="tw-label"><span class="tw-label-text">E-mail</span></label>
                        <input type="email" name="email" class="tw-input tw-input-bordered"
                            value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <span class="tw-text-error tw-text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <button class="tw-btn tw-btn-primary">Salvar</button>
                </form>
            </div>
        </div>

        <div class="tw-card tw-bg-base-100 tw-shadow">
            <div class="tw-card-body">
                <h2 class="tw-card-title">Alterar Senha</h2>
                <form method="POST" action="{{ route('settings.password.update') }}" class="tw-space-y-4">
                    @csrf @method('PUT')
                    <div class="tw-form-control">
                        <label class="tw-label"><span class="tw-label-text">Nova senha</span></label>
                        <input type="password" name="password" class="tw-input tw-input-bordered" required>
                        @error('password')
                            <span class="tw-text-error tw-text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="tw-form-control">
                        <label class="tw-label"><span class="tw-label-text">Confirmar senha</span></label>
                        <input type="password" name="password_confirmation" class="tw-input tw-input-bordered" required>
                    </div>
                    <button class="tw-btn tw-btn-secondary">Atualizar senha</button>
                </form>
            </div>
        </div>
    </div>
@endsection
