<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Consultor Financeiro') }}</title>

    {{-- Tema inicial (corporate=light, business=dark) --}}
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

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* iOS safe-area */
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

        /* Estados ativos */
        .is-active {
            background-color: hsl(var(--p) / 0.10);
            color: hsl(var(--p));
            border-radius: .5rem;
        }

        .item-active {
            border-left: 4px solid hsl(var(--p));
            background-color: hsl(var(--b1));
        }

        /* Swap logo (claro/escuro) */
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

        /* Efeito quando drawer aberto no mobile */
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

        /* Dock */
        .dock {
            display: flex;
            justify-content: space-around;
            align-items: center;
            height: 64px;
        }

        .dock a {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px 10px;
            border-radius: .75rem;
        }

        .dock .dock-active {
            color: hsl(var(--p));
            background-color: hsl(var(--p) / 0.10);
        }

        /* Mini logo no sidebar colapsado */
        .mini-logo {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }
    </style>
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
        $clientId = $user?->client?->id ?? null;

        $clientUrl = fn(string $path) => $clientId ? url("/{$clientId}{$path}") : '#';
        $rConsultant = fn($name) => $consultantId ? route($name, ['consultant' => $consultantId]) : route('dashboard');

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
            'newTxn' => fa('fa-solid fa-circle-plus'),
            'bolt' => fa('fa-solid fa-bolt'),
            'profile' => fa('fa-regular fa-user'),
            'default' => fa('fa-regular fa-circle'),
        ];
        $icon = fn(string $k) => $icons[$k] ?? $icons['default'];

        $cHref = function (string $name) use ($rConsultant) {
            return \Illuminate\Support\Facades\Route::has($name) ? $rConsultant($name) : '#';
        };
        $dockActive = fn(string $pattern) => request()->routeIs($pattern) ? 'dock-active' : '';
    @endphp

    <div class="drawer lg:drawer-open">
        <input id="app-drawer" type="checkbox" class="drawer-toggle" />

        {{-- CONTENT --}}
        <div class="drawer-content flex flex-col relative">
            {{-- overlay --}}
            <div class="content-dim fixed inset-0 bg-base-300/40 lg:hidden"></div>

            <div class="content-wrap">
                <header class="navbar h-16 bg-primary border-b border-base-300 sticky top-0 z-30">
                    <div class="navbar-start">
                        <label for="app-drawer" aria-label="Abrir menu lateral"
                            class="btn btn-ghost btn-square lg:hidden">
                            <i class="fa-solid fa-bars"></i>
                        </label>
                    </div>

                    <div class="navbar-center">
                        <a href="{{ route('home') }}" class="btn btn-ghost px-2">
                            <img src="{{ asset(env('APP_LOGO_DARK_WM', '/storage/logo/escuros.png')) }}"
                                alt="{{ config('app.name') }}" class="h-13 logo--dark" height="32" loading="eager">
                            <img src="{{ asset(env('APP_LOGO_LIGHT_WM', '/storage/logo/claros.png')) }}"
                                alt="{{ config('app.name') }}" class="h-13 logo--light" height="32" loading="eager">
                        </a>
                    </div>

                    <div class="navbar-end gap-1">
                        {{-- Toggle de tema --}}
                        <label class="swap swap-rotate btn btn-ghost btn-square" title="Alternar tema">
                            <input id="theme-toggle" type="checkbox" aria-label="Alternar tema" />
                            <i class="fa-solid fa-sun swap-off text-lg"></i>
                            <i class="fa-solid fa-moon swap-on text-lg"></i>
                        </label>

                        {{-- Menu do usuário --}}
                        <div class="dropdown dropdown-end">
                            <div tabindex="0" role="button" class="btn btn-ghost btn-square"
                                aria-label="Menu do usuário">
                                <i class="fa-regular fa-user text-lg"></i>
                            </div>
                            <ul tabindex="0"
                                class="menu menu-sm dropdown-content bg-base-200 rounded-box z-[60] mt-3 w-56 p-2 shadow">
                                <li class="menu-title px-2">{{ $user->name ?? 'Usuário' }}</li>
                                <li><a href="{{ route('profile.edit') }}"><i
                                            class="fa-regular fa-user me-2"></i>Perfil</a></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"><i
                                                class="fa-solid fa-right-from-bracket me-2"></i>Sair</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </header>

                {{-- MAIN --}}
                <main class="p-4 lg:p-6 pb-24 sm:pb-10 safe-bottom">
                    @yield('content')
                    @include('partials.flash')
                </main>

                {{-- DOCK (mobile) --}}
                <nav class="sm:hidden fixed inset-x-0 bottom-0 z-40">
                    <div class="dock bg-base-100/95 backdrop-blur border-t border-base-300 dock-safe">
                        {{-- Nova transação (cliente) --}}
                        @if ($role === 'client')
                            <a href="{{ $clientId ? url("/{$clientId}/transactions/new") : '#' }}">
                                <i class="fa-solid fa-circle-plus text-base"></i><span class="text-[10px]">Nova</span>
                            </a>
                        @endif

                        {{-- Home --}}
                        @if ($role === 'client')
                            <a href="{{ $cHref('client.dashboard') }}" class="{{ $dockActive('client.dashboard') }}">
                                <i class="fa-solid fa-house text-base"></i><span class="text-[10px]">Início</span>
                            </a>
                        @elseif ($role === 'consultant')
                            <a href="{{ $rConsultant('consultant.dashboard') }}"
                                class="{{ $dockActive('consultant.dashboard') }}">
                                <i class="fa-solid fa-house text-base"></i><span class="text-[10px]">Início</span>
                            </a>
                        @elseif ($role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="{{ $dockActive('admin.dashboard') }}">
                                <i class="fa-solid fa-house text-base"></i><span class="text-[10px]">Início</span>
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="{{ $dockActive('dashboard') }}">
                                <i class="fa-solid fa-house text-base"></i><span class="text-[10px]">Início</span>
                            </a>
                        @endif

                        {{-- Ações (modal) --}}
                        <label for="actions-modal" class="cursor-pointer">
                            <i class="fa-solid fa-bolt text-base"></i><span class="text-[10px]">Ações</span>
                        </label>
                    </div>
                </nav>

                {{-- MODAL: Ações (lê window.__pageActions ou fallback) --}}
                <input type="checkbox" id="actions-modal" class="modal-toggle" />
                <div class="modal modal-bottom sm:modal-middle">
                    <div class="modal-box">
                        <h3 class="font-bold text-lg mb-3">Ações</h3>
                        <div x-data="{ actions: window.__pageActions || [] }" class="space-y-3">
                            <template x-if="actions.length">
                                <div class="grid grid-cols-2 gap-2">
                                    <template x-for="a in actions" :key="a.label">
                                        <a :href="a.href || '#'" class="btn btn-outline justify-start gap-2">
                                            <i :class="'fa-solid ' + (a.icon || 'fa-bolt')"></i>
                                            <span x-text="a.label"></span>
                                        </a>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!actions.length">
                                <div>
                                    <a href="{{ $role === 'client' ? $cHref('client.tasks.index') : ($role === 'consultant' ? $rConsultant('consultant.tasks.index') : '#') }}"
                                        class="btn btn-outline w-full justify-start gap-2">
                                        <i class="fa-solid fa-list-check"></i> Tarefas
                                    </a>
                                    <p class="text-xs opacity-70 mt-2">Sem ações específicas nesta tela.</p>
                                </div>
                            </template>
                        </div>
                        <div class="modal-action">
                            <label for="actions-modal" class="btn">Fechar</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SIDEBAR --}}
        <div class="drawer-side z-40">
            <label for="app-drawer" aria-label="Fechar menu lateral" class="drawer-overlay"></label>

            <aside class="min-h-full border-r border-base-300 bg-base-200 transition-[width] duration-200 ease-in-out"
                :class="collapsed ? 'lg:w-25' : 'lg:w-72'">

                {{-- Header Sidebar --}}
                <div class="px-4 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3" :class="collapsed ? 'mx-auto' : ''">
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
                        <li><a href="{{ route('admin.dashboard') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('admin.dashboard') ? 'item-active' : '' }}">{!! $icon('dashboard') !!}<span>Dashboard</span></a>
                        </li>
                        <li><a href="{{ route('admin.consultants.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('admin.consultants.*') ? 'item-active' : '' }}">{!! $icon('users') !!}<span>Consultores</span></a>
                        </li>
                        <li><a href="{{ route('settings') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('settings*') ? 'item-active' : '' }}">{!! $icon('settings') !!}<span>Configurações</span></a>
                        </li>
                    @elseif ($role === 'consultant')
                        <li><a href="{{ $rConsultant('consultant.dashboard') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('consultant.dashboard') ? 'item-active' : '' }}">{!! $icon('dashboard') !!}<span>Dashboard</span></a>
                        </li>
                        <li><a href="{{ $rConsultant('consultant.clients.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('consultant.clients.*') ? 'item-active' : '' }}">{!! $icon('clients') !!}<span>Clientes</span></a>
                        </li>
                        <li><a href="{{ $rConsultant('consultant.tasks.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('consultant.tasks.*') ? 'item-active' : '' }}">{!! $icon('tasks') !!}<span>Tarefas</span></a>
                        </li>
                        <li><a href="{{ $rConsultant('consultant.categories.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('consultant.categories.*') ? 'item-active' : '' }}">{!! $icon('categories') !!}<span>Categorias</span></a>
                        </li>
                        <li><a href="{{ route('settings') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('settings*') ? 'item-active' : '' }}">{!! $icon('settings') !!}<span>Configurações</span></a>
                        </li>
                    @elseif ($role === 'client')
                        <li><a href="{{ $cHref('client.dashboard') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.dashboard') ? 'item-active' : '' }}">{!! $icon('dashboard') !!}<span>Dashboard</span></a>
                        </li>
                        <li><a href="{{ $cHref('client.accounts.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.accounts*') ? 'item-active' : '' }}">{!! $icon('accounts') !!}<span>Contas</span></a>
                        </li>
                        <li><a href="{{ $cHref('client.invoices.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.invoices*') ? 'item-active' : '' }}">{!! $icon('invoices') !!}<span>Faturas</span></a>
                        </li>
                        <li><a href="{{ $cHref('client.budgets.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.budgets*') ? 'item-active' : '' }}">{!! $icon('monthly_goals') !!}<span>Orçamentos</span></a>
                        </li>
                        <li><a href="{{ $cHref('client.forecasts.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.forecasts*') ? 'item-active' : '' }}">{!! $icon('reports') !!}<span>Previsões</span></a>
                        </li>
                        <li><a href="{{ $cHref('client.goals.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.goals*') ? 'item-active' : '' }}">{!! $icon('monthly_goals') !!}<span>Metas
                                    mensais</span></a></li>
                        <li><a href="{{ $cHref('client.transactions.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.transactions*') ? 'item-active' : '' }}">{!! $icon('transactions') !!}<span>Transações</span></a>
                        </li>
                        <li><a href="{{ $clientId ? url("/{$clientId}/transactions/new") : '#' }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">{!! $icon('newTxn') !!}<span>Nova
                                    transação</span></a></li>
                        <li><a href="{{ $cHref('client.objectives.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.objectives*') ? 'item-active' : '' }}">{!! $icon('objectives') !!}<span>Objetivos</span></a>
                        </li>
                        <li><a href="{{ $cHref('client.investments.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.investments*') ? 'item-active' : '' }}">{!! $icon('investments') !!}<span>Investimentos</span></a>
                        </li>
                        <li><a href="{{ $cHref('client.accountability.index') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('client.accountability*') ? 'item-active' : '' }}">{!! $icon('reports') !!}<span>Prestação
                                    de contas</span></a></li>
                        <li><a href="{{ route('settings') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('settings*') ? 'item-active' : '' }}">{!! $icon('settings') !!}<span>Configurações</span></a>
                        </li>
                    @else
                        <li><a href="{{ route('dashboard') }}"
                                class="flex items-center gap-3 py-3 rounded-lg hover:bg-base-100 {{ request()->routeIs('dashboard') ? 'item-active' : '' }}">{!! $icon('dashboard') !!}<span>Dashboard</span></a>
                        </li>
                    @endif
                </ul>

                {{-- Menu colapsado --}}
                <ul class="menu px-2 gap-4" x-show="collapsed" x-transition>
                    @php
                        $collapsedLink = function (string $pattern) {
                            $active = request()->routeIs($pattern);
                            return [
                                'a' =>
                                    ($active ? 'bg-base-200 ring-1 ring-base-300' : 'hover:bg-base-100') .
                                    ' flex flex-col items-center py-3 rounded-lg transition-all duration-150 active:scale-[.98]',
                                'icon' => $active ? 'text-primary text-lg' : '',
                                'label' => 'text-[10px] mt-1 leading-none ' . ($active ? 'font-semibold' : ''),
                                'aria' => $active ? 'page' : 'false',
                            ];
                        };
                    @endphp

                    @if ($role === 'client')
                        @php($c = $collapsedLink('client.dashboard'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ $cHref('client.dashboard') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('dashboard') !!}</span><span
                                    class="{{ $c['label'] }}">Dashboard</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('client.accounts*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Contas">
                            <a href="{{ $cHref('client.accounts.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('accounts') !!}</span><span
                                    class="{{ $c['label'] }}">Contas</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('client.transactions*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Transações">
                            <a href="{{ $cHref('client.transactions.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('transactions') !!}</span><span
                                    class="{{ $c['label'] }}">Transações</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('settings*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Configurações">
                            <a href="{{ route('settings') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('settings') !!}</span><span
                                    class="{{ $c['label'] }}">Config</span>
                            </a>
                        </li>
                    @elseif ($role === 'consultant')
                        @php($c = $collapsedLink('consultant.dashboard'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ $rConsultant('consultant.dashboard') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('dashboard') !!}</span><span
                                    class="{{ $c['label'] }}">Dashboard</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('consultant.clients.*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Clientes">
                            <a href="{{ $rConsultant('consultant.clients.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('clients') !!}</span><span
                                    class="{{ $c['label'] }}">Clientes</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('consultant.tasks.*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Tarefas">
                            <a href="{{ $rConsultant('consultant.tasks.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('tasks') !!}</span><span
                                    class="{{ $c['label'] }}">Tarefas</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('settings*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Configurações">
                            <a href="{{ route('settings') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('settings') !!}</span><span
                                    class="{{ $c['label'] }}">Config</span>
                            </a>
                        </li>
                    @elseif ($role === 'admin')
                        @php($c = $collapsedLink('admin.dashboard'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ route('admin.dashboard') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('dashboard') !!}</span><span
                                    class="{{ $c['label'] }}">Dashboard</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('admin.consultants.*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Consultores">
                            <a href="{{ route('admin.consultants.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('users') !!}</span><span
                                    class="{{ $c['label'] }}">Consultores</span>
                            </a>
                        </li>
                        @php($c = $collapsedLink('settings*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Configurações">
                            <a href="{{ route('settings') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('settings') !!}</span><span
                                    class="{{ $c['label'] }}">Config</span>
                            </a>
                        </li>
                    @else
                        @php($c = $collapsedLink('dashboard'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ route('dashboard') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('dashboard') !!}</span><span
                                    class="{{ $c['label'] }}">Dashboard</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </aside>
        </div>
    </div>

    {{-- Alternância de tema persistente --}}
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

            // Suporte a "stack('actions')" como fonte de ações (fallback ao window.__pageActions)
            try {
                const inline = document.getElementById('inline-actions-json')?.textContent;
                const parsed = inline ? JSON.parse(inline) : null;
                if (parsed && Array.isArray(parsed)) window.__pageActions = parsed;
            } catch (e) {}
        });
    </script>

    {{-- Opcional: página pode preencher ações via @push --}}
    @stack('scripts')
</body>

</html>
