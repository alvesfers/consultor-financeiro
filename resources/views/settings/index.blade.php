@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto space-y-10">

        {{-- ALERTAS GLOBAIS --}}
        @if (session('status'))
            <div class="alert alert-success shadow-sm">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error shadow-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <h3 class="font-semibold">Erro ao salvar</h3>
                    <ul class="list-disc list-inside text-sm mt-1">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- SEÇÃO: PERFIL --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title mb-3">
                    <i class="fa-solid fa-user-gear mr-2 text-primary"></i> Configurações de Perfil
                </h2>

                <form method="POST" action="{{ route('settings.profile.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Nome</span>
                        </label>
                        <input name="name" type="text" class="input input-bordered"
                            value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">E-mail</span>
                        </label>
                        <input name="email" type="email" class="input input-bordered"
                            value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Fuso horário</span>
                        </label>
                        <select name="timezone" class="select select-bordered">
                            @foreach (DateTimeZone::listIdentifiers() as $tz)
                                <option value="{{ $tz }}" @selected(old('timezone', $user->timezone ?? 'America/Sao_Paulo') === $tz)>
                                    {{ $tz }}
                                </option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Idioma</span>
                        </label>
                        <select name="locale" class="select select-bordered">
                            <option value="pt_BR" @selected(old('locale', $user->locale ?? 'pt_BR') === 'pt_BR')>Português (Brasil)</option>
                            <option value="en_US" @selected(old('locale', $user->locale ?? '') === 'en_US')>English (US)</option>
                        </select>
                    </div>

                    <div class="flex justify-end">
                        <button class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Salvar alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- SEÇÃO: SENHA --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title mb-3">
                    <i class="fa-solid fa-lock mr-2 text-secondary"></i> Alterar Senha
                </h2>

                <form method="POST" action="{{ route('settings.password.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Nova senha</span>
                        </label>
                        <input type="password" name="password" class="input input-bordered" required minlength="8">
                        @error('password')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Confirmar senha</span>
                        </label>
                        <input type="password" name="password_confirmation" class="input input-bordered" required>
                    </div>

                    <div class="flex justify-end">
                        <button class="btn btn-secondary">
                            <i class="fa-solid fa-rotate mr-2"></i> Atualizar senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- FontAwesome --}}
    @once
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
            crossorigin="anonymous" referrerpolicy="no-referrer" />
    @endonce
@endsection
