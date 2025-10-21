<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="corporate">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Consultor Financeiro') }}</title>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* iOS safe-area para Dock/FAB */
        :root {
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }

        .safe-bottom {
            padding-bottom: calc(1rem + var(--safe-bottom));
        }

        .dock-safe {
            padding-bottom: var(--safe-bottom);
        }

        .fab-safe {
            bottom: calc(1rem + var(--safe-bottom));
        }

        /* Logo swap: por padrão (tema claro) mostra versão escura */
        .logo--dark {
            display: inline-block;
        }

        .logo--light {
            display: none;
        }

        html[data-theme="business"] .logo--dark {
            display: none;
        }

        html[data-theme="business"] .logo--light {
            display: inline-block;
        }

        /* Estado ativo mais evidente (sidebar/dock) */
        .is-active {
            background-color: hsl(var(--p) / 0.10);
            color: hsl(var(--p));
            border-radius: 0.5rem;
            box-shadow: 0 0 0 1px hsl(var(--b3));
        }

        /* Wordmark centralizado no mobile */
        .brand-center {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        @media (min-width: 1024px) {
            .brand-center {
                position: static;
                transform: none;
            }
        }

        /* Efeito quando o drawer (menu) estiver aberto no mobile */
        .drawer-toggle:checked~.drawer-content .content-dim {
            display: block;
        }

        .drawer-toggle:checked~.drawer-content .content-wrap {
            filter: blur(1px);
            transform: scale(0.992);
        }

        .content-wrap {
            transition: filter .15s ease, transform .15s ease;
        }

        .content-dim {
            display: none;
        }
    </style>

    {{-- Inicializa tema (localStorage ou esquema do sistema) --}}
    <script>
        (() => {
            const THEMES = {
                light: 'corporate',
                dark: 'business'
            };
            const saved = localStorage.getItem('theme');
            const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
            const initial = saved || (prefersDark ? THEMES.dark : THEMES.light);
            document.documentElement.setAttribute('data-theme', initial);
        })();
    </script>
</head>

<body class="min-h-screen" x-data="{
    collapsed: JSON.parse(localStorage.getItem('sbCollapsed') ?? 'false'),
    toggle() {
        this.collapsed = !this.collapsed;
        localStorage.setItem('sbCollapsed', JSON.stringify(this.collapsed));
    }
}">

    @php
        $user = auth()->user();
        $role = $user->role ?? null;
        $consultantId = $user?->consultant?->id ?? ($user?->client?->consultant_id ?? null);

        // (extra) clientId para URLs tipo /{clientId}/transactions/new
        $clientId = $user?->client?->id ?? null;
        $clientUrl = fn(string $path) => $clientId ? url("/{$clientId}{$path}") : '#';

        // helper de rota com parâmetro do consultor
        $rConsultant = fn($name) => $consultantId ? route($name, ['consultant' => $consultantId]) : route('dashboard');

        // helper p/ FA ícones
        if (!function_exists('fa')) {
            function fa($classes)
            {
                return "<i class='{$classes} text-base'></i>";
            }
        }

        $icons = [
            'dashboard' => fa('fa-solid fa-gauge-high'),
            'users' => fa('fa-solid fa-user-group'),
            'clients' => fa('fa-solid fa-address-book'),
            'tasks' => fa('fa-solid fa-list-check'),
            'categories' => fa('fa-solid fa-tags'),
            'settings' => fa('fa-solid fa-gear'),
            'accounts' => fa('fa-solid fa-wallet'),
            'invoices' => fa('fa-solid fa-file-invoice-dollar'),
            'monthly_goals' => fa('fa-solid fa-bullseye'),
            'transactions' => fa('fa-solid fa-right-left'),
            'objectives' => fa('fa-solid fa-flag-checkered'),
            'investments' => fa('fa-solid fa-chart-line'),
            'reports' => fa('fa-solid fa-file-lines'),
            'plus' => fa('fa-solid fa-plus'),
            'transfer' => fa('fa-solid fa-arrow-right-arrow-left'),
            'goal' => fa('fa-solid fa-bullseye'),
            'clientAdd' => fa('fa-solid fa-user-plus'),
            'taskAdd' => fa('fa-solid fa-square-plus'),
            'budget' => fa('fa-solid fa-envelope-open-dollar'), // Orçamentos
            'forecast' => fa('fa-solid fa-wand-magic-sparkles'), // Previsões
            'newTxn' => fa('fa-solid fa-circle-plus'), // Nova transação (CTA)
            'default' => fa('fa-regular fa-circle'),
        ];

        $cHref = function (string $name) use ($rConsultant) {
            return \Illuminate\Support\Facades\Route::has($name) ? $rConsultant($name) : '#';
        };

        $icon = fn(string $key) => $icons[$key] ?? $icons['default'];

        $collapsedLink = function (string $pattern) {
            $isActive = request()->routeIs($pattern);
            return [
                'isActive' => $isActive,
                'a' =>
                    ($isActive ? 'bg-base-200 ring-1 ring-base-300' : 'hover:bg-base-100') .
                    ' flex flex-col items-center py-3 rounded-lg transition-all duration-150 ease-out active:scale-[.98]',
                'icon' => $isActive ? 'text-primary text-lg' : '',
                'label' => 'text-[10px] mt-1 leading-none ' . ($isActive ? 'font-semibold' : ''),
                'aria' => $isActive ? 'page' : 'false',
            ];
        };

        // helper pro estado ativo do Dock
        $dockActive = fn(string $pattern) => request()->routeIs($pattern) ? 'dock-active' : '';
    @endphp

    <div class="drawer lg:drawer-open">
        <input id="app-drawer" type="checkbox" class="drawer-toggle" />

        {{-- CONTENT --}}
        <div class="drawer-content flex flex-col relative">
            {{-- overlay/efeito quando menu aberto no mobile --}}
            <div class="content-dim fixed inset-0 bg-base-300/40 lg:hidden"></div>

            <div class="content-wrap">
                {{-- Topbar --}}
                <header class="navbar bg-base-100 border-b border-base-300 px-4 sticky top-0 z-30">
                    {{-- Hamburguer (abre sidebar) --}}
                    <div class="flex-none lg:hidden">
                        <label for="app-drawer" aria-label="Abrir menu lateral" class="btn btn-ghost btn-square">
                            <i class="fa-solid fa-bars"></i>
                        </label>
                    </div>

                    {{-- Wordmark centralizado em telas pequenas (swap claro/escuro) --}}
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('home') }}" class="btn btn-ghost brand-center">
                            <img src="{{ asset(env('APP_LOGO_DARK_WM', '/storage/logo/escuros.png')) }}"
                                alt="{{ config('app.name') }}" class="h-13 logo--dark" loading="eager" height="32">
                            <img src="{{ asset(env('APP_LOGO_LIGHT_WM', '/storage/logo/claros.png')) }}"
                                alt="{{ config('app.name') }}" class="h-13 logo--light" loading="eager" height="32">
                        </a>
                    </div>

                    {{-- Ações à direita --}}
                    <div class="flex-none gap-2">
                        {{-- Toggle de tema --}}
                        <label class="swap swap-rotate btn btn-ghost btn-square" title="Alternar tema">
                            <input id="theme-toggle" type="checkbox" aria-label="Alternar tema" />
                            <i class="fa-solid fa-sun swap-off text-lg"></i>
                            <i class="fa-solid fa-moon swap-on text-lg"></i>
                        </label>

                        {{-- Menu do usuário (ícone) --}}
                        <div class="dropdown dropdown-end">
                            <div tabindex="0" role="button" class="btn btn-ghost btn-square"
                                aria-label="Menu do usuário">
                                <i class="fa-regular fa-user text-lg"></i>
                            </div>
                            <ul tabindex="0"
                                class="menu menu-sm dropdown-content bg-base-200 rounded-box z-[60] mt-3 w-56 p-2 shadow">
                                <li class="menu-title px-2">{{ $user->name ?? 'Usuário' }}</li>

                                <li>
                                    <a href="{{ route('profile.edit') }}">
                                        <i class="fa-regular fa-user me-2"></i>Perfil
                                    </a>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit">
                                            <i class="fa-solid fa-right-from-bracket me-2"></i>Sair
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </header>

                {{-- Main (padding extra no mobile por causa do Dock) --}}
                <main class="p-4 lg:p-6 pb-24 sm:pb-10 safe-bottom">
                    @yield('content')
                    @include('partials.flash')
                </main>

                {{-- DOCK: Bottom navigation (mobile) --}}
                <nav class="sm:hidden fixed inset-x-0 bottom-0 z-40">
                    <div class="dock dock-md bg-base-100/95 backdrop-blur border-t border-base-300 dock-safe">
                        @if ($role === 'client')
                            <a href="{{ $cHref('client.dashboard') }}" class="{{ $dockActive('client.dashboard') }}">
                                {!! $icon('dashboard') !!}<span class="dock-label">Início</span>
                            </a>
                            <a href="{{ $clientUrl('/transactions/new') }}">
                                {!! $icon('newTxn') !!}<span class="dock-label">Nova</span>
                            </a>
                            <a href="{{ $cHref('client.transactions.index') }}"
                                class="{{ $dockActive('client.transactions*') }}">
                                {!! $icon('transactions') !!}<span class="dock-label">Transações</span>
                            </a>
                            <a href="{{ $cHref('client.budgets.index') }}"
                                class="{{ $dockActive('client.budgets*') }}">
                                {!! $icon('budget') !!}<span class="dock-label">Orçamentos</span>
                            </a>
                            <a href="{{ $cHref('client.forecasts.index') }}"
                                class="{{ $dockActive('client.forecasts*') }}">
                                {!! $icon('forecast') !!}<span class="dock-label">Previsões</span>
                            </a>
                        @elseif ($role === 'consultant')
                            <a href="{{ $rConsultant('consultant.dashboard') }}"
                                class="{{ $dockActive('consultant.dashboard') }}">
                                {!! $icon('dashboard') !!}<span class="dock-label">Dashboard</span>
                            </a>
                            <a href="{{ $rConsultant('consultant.clients.index') }}"
                                class="{{ $dockActive('consultant.clients.*') }}">
                                {!! $icon('clients') !!}<span class="dock-label">Clientes</span>
                            </a>
                            <a href="{{ $rConsultant('consultant.tasks.index') }}"
                                class="{{ $dockActive('consultant.tasks.*') }}">
                                {!! $icon('tasks') !!}<span class="dock-label">Tarefas</span>
                            </a>
                            <a href="{{ $rConsultant('consultant.categories.index') }}"
                                class="{{ $dockActive('consultant.categories.*') }}">
                                {!! $icon('categories') !!}<span class="dock-label">Categorias</span>
                            </a>
                            <a href="{{ route('settings') }}" class="{{ $dockActive('settings*') }}">
                                {!! $icon('settings') !!}<span class="dock-label">Config</span>
                            </a>
                        @elseif ($role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="{{ $dockActive('admin.dashboard') }}">
                                {!! $icon('dashboard') !!}<span class="dock-label">Dashboard</span>
                            </a>
                            <a href="{{ route('admin.consultants.index') }}"
                                class="{{ $dockActive('admin.consultants.*') }}">
                                {!! $icon('users') !!}<span class="dock-label">Consultores</span>
                            </a>
                            <a href="{{ route('settings') }}" class="{{ $dockActive('settings*') }}">
                                {!! $icon('settings') !!}<span class="dock-label">Config</span>
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="{{ $dockActive('dashboard') }}">
                                {!! $icon('dashboard') !!}<span class="dock-label">Dashboard</span>
                            </a>
                        @endif
                    </div>
                </nav>
            </div>
        </div>

        {{-- SIDEBAR --}}
        <div class="drawer-side z-40">
            <label for="app-drawer" aria-label="Fechar menu lateral" class="drawer-overlay"></label>

            <aside class="min-h-full border-r border-base-300 bg-base-200 transition-[width] duration-200 ease-in-out"
                :class="collapsed ? 'lg:w-24' : 'lg:w-72'">

                {{-- Header Sidebar --}}
                <div class="px-4 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2" :class="collapsed ? 'mx-auto' : ''">
                        <div class="hidden lg:block" x-show="!collapsed">
                            <div class="font-bold leading-tight">Painel</div>
                            <div class="text-xs opacity-70 capitalize">{{ $role ?? 'user' }}</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm hidden lg:flex" @click="toggle()"
                        :title="collapsed ? 'Expandir' : 'Recolher'">
                        <i class="fa-solid fa-angles-left transition" :class="collapsed ? '' : 'rotate-180'"></i>
                    </button>
                </div>

                {{-- Menu expandido --}}
                <ul class="menu px-3 gap-2" x-show="!collapsed" x-transition>
                    @if ($role === 'admin')
                        <li>
                            <a href="{{ route('admin.dashboard') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                                {!! $icon('dashboard') !!}<span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.consultants.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('admin.consultants.*') ? 'is-active' : '' }}">
                                {!! $icon('users') !!}<span>Consultores</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('settings') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('settings*') ? 'is-active' : '' }}">
                                {!! $icon('settings') !!}<span>Configurações</span>
                            </a>
                        </li>
                    @elseif ($role === 'consultant')
                        <li>
                            <a href="{{ $rConsultant('consultant.dashboard') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('consultant.dashboard') ? 'is-active' : '' }}">
                                {!! $icon('dashboard') !!}<span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $rConsultant('consultant.clients.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('consultant.clients.*') ? 'is-active' : '' }}">
                                {!! $icon('clients') !!}<span>Clientes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $rConsultant('consultant.tasks.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('consultant.tasks.*') ? 'is-active' : '' }}">
                                {!! $icon('tasks') !!}<span>Tarefas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $rConsultant('consultant.categories.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('consultant.categories.*') ? 'is-active' : '' }}">
                                {!! $icon('categories') !!}<span>Categorias</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('settings') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('settings*') ? 'is-active' : '' }}">
                                {!! $icon('settings') !!}<span>Configurações</span>
                            </a>
                        </li>
                    @elseif ($role === 'client')
                        <li>
                            <a href="{{ $cHref('client.dashboard') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.dashboard') ? 'is-active' : '' }}">
                                {!! $icon('dashboard') !!}<span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.accounts.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.accounts*') ? 'is-active' : '' }}">
                                {!! $icon('accounts') !!}<span>Contas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.invoices.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.invoices*') ? 'is-active' : '' }}">
                                {!! $icon('invoices') !!}<span>Faturas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.budgets.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.budgets*') ? 'is-active' : '' }}">
                                {!! $icon('budget') !!}<span>Orçamentos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.forecasts.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.forecasts*') ? 'is-active' : '' }}">
                                {!! $icon('forecast') !!}<span>Previsões</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.goals.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.goals*') ? 'is-active' : '' }}">
                                {!! $icon('monthly_goals') !!}<span>Metas mensais</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.transactions.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.transactions*') ? 'is-active' : '' }}">
                                {!! $icon('transactions') !!}<span>Transações</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $clientId ? url("/{$clientId}/transactions/new") : '#' }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icon('newTxn') !!}<span>Nova transação</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.objectives.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.objectives*') ? 'is-active' : '' }}">
                                {!! $icon('objectives') !!}<span>Objetivos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.investments.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.investments*') ? 'is-active' : '' }}">
                                {!! $icon('investments') !!}<span>Investimentos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.accountability.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.accountability*') ? 'is-active' : '' }}">
                                {!! $icon('reports') !!}<span>Prestação de contas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('settings') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('settings*') ? 'is-active' : '' }}">
                                {!! $icon('settings') !!}<span>Configurações</span>
                            </a>
                        </li>
                    @else
                        <li>
                            <a href="{{ route('dashboard') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                                {!! $icon('dashboard') !!}<span>Dashboard</span>
                            </a>
                        </li>
                    @endif
                </ul>

                {{-- Menu colapsado --}}
                <ul class="menu px-2 gap-4" x-show="collapsed" x-transition>
                    @if ($role === 'admin')
                        @php($c = $collapsedLink('admin.dashboard'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ route('admin.dashboard') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('dashboard') !!}</span>
                                <span class="{{ $c['label'] }}">Dashboard</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('admin.consultants.*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Consultores">
                            <a href="{{ route('admin.consultants.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('users') !!}</span>
                                <span class="{{ $c['label'] }}">Consultores</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('settings*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Configurações">
                            <a href="{{ route('settings') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('settings') !!}</span>
                                <span class="{{ $c['label'] }}">Config</span>
                            </a>
                        </li>
                    @elseif ($role === 'consultant')
                        @php($c = $collapsedLink('consultant.dashboard'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ $rConsultant('consultant.dashboard') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('dashboard') !!}</span>
                                <span class="{{ $c['label'] }}">Dashboard</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('consultant.clients.*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Clientes">
                            <a href="{{ $rConsultant('consultant.clients.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('clients') !!}</span>
                                <span class="{{ $c['label'] }}">Clientes</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('consultant.tasks.*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Tarefas">
                            <a href="{{ $rConsultant('consultant.tasks.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('tasks') !!}</span>
                                <span class="{{ $c['label'] }}">Tarefas</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('consultant.categories.*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Categorias">
                            <a href="{{ $rConsultant('consultant.categories.index') }}"
                                class="{{ $c['a'] }}" aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('categories') !!}</span>
                                <span class="{{ $c['label'] }}">Categorias</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('settings*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Configurações">
                            <a href="{{ route('settings') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('settings') !!}</span>
                                <span class="{{ $c['label'] }}">Config</span>
                            </a>
                        </li>
                    @elseif ($role === 'client')
                        @php($c = $collapsedLink('client.dashboard'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ $cHref('client.dashboard') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('dashboard') !!}</span>
                                <span class="{{ $c['label'] }}">Dashboard</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('client.accounts*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Contas">
                            <a href="{{ $cHref('client.accounts.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('accounts') !!}</span>
                                <span class="{{ $c['label'] }}">Contas</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('client.invoices*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Faturas">
                            <a href="{{ $cHref('client.invoices.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('invoices') !!}</span>
                                <span class="{{ $c['label'] }}">Faturas</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('client.budgets*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Orçamentos">
                            <a href="{{ $cHref('client.budgets.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('budget') !!}</span>
                                <span class="{{ $c['label'] }}">Orçamentos</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('client.forecasts*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Previsões">
                            <a href="{{ $cHref('client.forecasts.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('forecast') !!}</span>
                                <span class="{{ $c['label'] }}">Previsões</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('client.goals*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Metas">
                            <a href="{{ $cHref('client.goals.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('monthly_goals') !!}</span>
                                <span class="{{ $c['label'] }}">Metas</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('client.transactions*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Transações">
                            <a href="{{ $cHref('client.transactions.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('transactions') !!}</span>
                                <span class="{{ $c['label'] }}">Transações</span>
                            </a>
                        </li>
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Nova transação">
                            <a href="{{ $clientId ? url("/{$clientId}/transactions/new") : '#' }}"
                                class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                                {!! $icon('newTxn') !!}<span class="text-[10px] mt-1 leading-none">Nova</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('settings*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Configurações">
                            <a href="{{ route('settings') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('settings') !!}</span>
                                <span class="{{ $c['label'] }}">Config</span>
                            </a>
                        </li>
                    @else
                        @php($c = $collapsedLink('dashboard'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ route('dashboard') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('dashboard') !!}</span>
                                <span class="{{ $c['label'] }}">Dashboard</span>
                            </a>
                        </li>
                    @endif
                </ul>

            </aside>
        </div>
    </div>

    {{-- Alternância de tema com persistência --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const THEMES = {
                light: 'corporate',
                dark: 'business'
            };
            const root = document.documentElement;
            const toggle = document.getElementById('theme-toggle');
            toggle.checked = root.getAttribute('data-theme') === THEMES.dark;
            toggle.addEventListener('change', (e) => {
                const next = e.target.checked ? THEMES.dark : THEMES.light;
                root.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
            });
        });
    </script>
</body>

</html>
