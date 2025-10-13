<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name', 'Consultoria Financeira') }} — Login</title>

    {{-- Fonts (opcional) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    {{-- Font Awesome (pedido) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    {{-- Vite assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
        }

        html,
        body {
            font-family: var(--font-sans);
        }
    </style>
</head>

<body class="min-h-screen bg-base-100 text-base-content">
    {{-- NAVBAR (opcional, replica estilo do welcome) --}}
    <header class="border-b border-base-200">
        <div class="navbar max-w-7xl mx-auto px-4">
            <div class="navbar-start">
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    <div class="avatar placeholder">
                        <div class="bg-primary text-primary-content w-10 rounded-lg">
                            <span class="text-lg font-bold">CF</span>
                        </div>
                    </div>
                    <span class="text-lg font-semibold">{{ config('app.name', 'Consultoria Financeira') }}</span>
                </a>
            </div>
            <div class="navbar-end gap-2">
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-user-plus mr-2"></i> Registrar
                    </a>
                @endif
            </div>
        </div>
    </header>

    {{-- CONTAINER --}}
    <main class="max-w-7xl mx-auto px-4 py-10">
        {{-- Status de sessão --}}
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <div class="grid lg:grid-cols-2 gap-8 items-center">
            {{-- Lado esquerdo: copy/benefícios (opcional) --}}
            <section class="hidden lg:block">
                <div class="badge badge-primary badge-lg mb-4">Bem-vindo de volta</div>
                <h1 class="text-4xl font-extrabold leading-tight">
                    Acompanhe seus clientes com <span class="text-primary">clareza</span>.
                </h1>
                <p class="mt-4 opacity-80">
                    Faça login para acessar tarefas, objetivos, faturas e relatórios — tudo em um só lugar.
                </p>

                <ul class="mt-6 space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-primary"></i>
                        Fluxo guiado de tasks e playbooks.
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fa-solid fa-chart-line text-primary"></i>
                        Relatórios e indicadores em tempo real.
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-primary"></i>
                        Segurança e controle de acesso.
                    </li>
                </ul>
            </section>

            {{-- Card de login (mantém ids/names/rotas) --}}
            <section>
                <form method="POST" action="{{ route('login') }}"
                    class="card bg-base-100 border border-base-200 shadow">
                    @csrf
                    <div class="card-body p-6 sm:p-8 space-y-4">
                        <div class="text-center mb-2">
                            <h2 class="text-2xl font-bold">Acessar sua conta</h2>
                            <p class="text-sm opacity-70">Entre com suas credenciais abaixo</p>
                        </div>

                        {{-- Email --}}
                        <div class="form-control">
                            <x-input-label for="email" :value="__('Email')" class="label text-sm font-medium" />
                            <x-text-input id="email" name="email" type="email" :value="old('email')" required
                                autofocus autocomplete="username" class="input input-bordered w-full" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2 text-error text-sm" />
                        </div>

                        {{-- Password --}}
                        <div class="form-control">
                            <x-input-label for="password" :value="__('Password')" class="label text-sm font-medium" />
                            <x-text-input id="password" name="password" type="password" required
                                autocomplete="current-password" class="input input-bordered w-full" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2 text-error text-sm" />
                        </div>

                        {{-- Remember / Forgot --}}
                        <div class="flex items-center justify-between pt-1">
                            <label for="remember_me" class="label cursor-pointer gap-2">
                                <input id="remember_me" name="remember" type="checkbox"
                                    class="checkbox checkbox-primary">
                                <span class="label-text text-sm">{{ __('Remember me') }}</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="link link-hover text-sm">
                                    {{ __('Forgot your password?') }}
                                </a>
                            @endif
                        </div>

                        {{-- Submit --}}
                        <div class="pt-2">
                            <x-primary-button class="btn btn-primary w-full">
                                <i class="fa-solid fa-right-to-bracket mr-2"></i>
                                {{ __('Log in') }}
                            </x-primary-button>
                        </div>

                        {{-- Divider opcional --}}
                        @if (Route::has('register'))
                            <div class="divider my-2">ou</div>
                            <a href="{{ route('register') }}" class="btn btn-ghost w-full">
                                <i class="fa-solid fa-user-plus mr-2"></i>
                                Criar conta
                            </a>
                        @endif
                    </div>
                </form>
            </section>
        </div>
    </main>

    {{-- FOOTER simples --}}
    <footer class="border-t border-base-200">
        <div class="max-w-7xl mx-auto px-4 py-6 text-sm opacity-70 text-center">
            © {{ date('Y') }} {{ config('app.name', 'Consultoria Financeira') }} — Todos os direitos reservados.
        </div>
    </footer>
</body>

</html>
