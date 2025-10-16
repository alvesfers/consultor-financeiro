<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Consultor Financeiro') }}</title>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

        // helper de rota com parâmetro do consultor
        $rConsultant = fn($name) => $consultantId ? route($name, ['consultant' => $consultantId]) : route('dashboard');

        // helper p/ FA ícones (evita redeclarar em includes)
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

            // ===== novos itens do menu do cliente =====
            'accounts' => fa('fa-solid fa-wallet'),
            'invoices' => fa('fa-solid fa-file-invoice-dollar'),
            'monthly_goals' => fa('fa-solid fa-bullseye'),
            'transactions' => fa('fa-solid fa-right-left'),
            'objectives' => fa('fa-solid fa-flag-checkered'),
            'investments' => fa('fa-solid fa-chart-line'),
            'reports' => fa('fa-solid fa-file-lines'),
            'default' => fa('fa-regular fa-circle'),
        ];

        // href seguro: usa $rConsultant se a rota existe, senão '#'
        $cHref = function (string $name) use ($rConsultant) {
            return \Illuminate\Support\Facades\Route::has($name) ? $rConsultant($name) : '#';
        };

        $icon = function (string $key) use ($icons) {
            return $icons[$key] ?? ($icons['default'] ?? '<i class="fa-regular fa-circle"></i>');
        };

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
    @endphp

    <div class="drawer lg:drawer-open">
        <input id="app-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">

            {{-- Topbar --}}
            <div class="navbar bg-base-100 border-b border-base-300 px-4 sticky top-0 z-20">
                <div class="flex-none lg:hidden">
                    <label for="app-drawer" aria-label="open sidebar" class="btn btn-ghost btn-square">
                        <i class="fa-solid fa-bars"></i>
                    </label>
                </div>

                <div class="flex-1">
                    <a href="{{ route('home') }}" class="btn btn-ghost text-xl">
                        {{ config('app.name', 'Consultor Financeiro') }}
                    </a>
                </div>

                <div class="flex-none gap-2">
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost">
                            <div class="flex items-center gap-2">
                                <div class="avatar placeholder">
                                    <div class="bg-neutral text-neutral-content rounded-full w-8">
                                        <span class="text-xs">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                                    </div>
                                </div>
                                <span class="hidden sm:block">{{ $user->name ?? 'Usuário' }}</span>
                            </div>
                        </div>
                        <ul tabindex="0"
                            class="menu menu-sm dropdown-content bg-base-200 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                            <li><a href="{{ route('profile.edit') }}"><i class="fa-regular fa-user me-2"></i>Perfil</a>
                            </li>
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
            </div>

            {{-- Main --}}
            <main class="p-4 lg:p-6">
                @yield('content')
                @include('partials.flash')
            </main>
        </div>

        {{-- Sidebar --}}
        <div class="drawer-side">
            <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>

            <aside class="min-h-full border-r border-base-300 bg-base-200 transition-[width] duration-200 ease-in-out"
                :class="collapsed ? 'lg:w-24' : 'lg:w-72'">

                {{-- Header --}}
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

                {{-- Menus expandidos --}}
                <ul class="menu px-3 gap-2" x-show="!collapsed" x-transition>
                    @if ($role === 'admin')
                        <li>
                            <a href="{{ route('admin.dashboard') }}"
                                class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['dashboard'] !!}<span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.consultants.index') }}"
                                class="{{ request()->routeIs('admin.consultants.*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['users'] !!}<span>Consultores</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('settings') }}"
                                class="{{ request()->routeIs('settings*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['settings'] !!}<span>Configurações</span>
                            </a>
                        </li>
                    @elseif ($role === 'consultant')
                        <li>
                            <a href="{{ $rConsultant('consultant.dashboard') }}"
                                class="{{ request()->routeIs('consultant.dashboard') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['dashboard'] !!}<span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $rConsultant('consultant.clients.index') }}"
                                class="{{ request()->routeIs('consultant.clients.*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['clients'] !!}<span>Clientes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $rConsultant('consultant.tasks.index') }}"
                                class="{{ request()->routeIs('consultant.tasks.*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['tasks'] !!}<span>Tarefas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $rConsultant('consultant.categories.index') }}"
                                class="{{ request()->routeIs('consultant.categories.*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['categories'] !!}<span>Categorias</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('settings') }}"
                                class="{{ request()->routeIs('settings*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['settings'] !!}<span>Configurações</span>
                            </a>
                        </li>
                    @elseif ($role === 'client')
                        <li>
                            <a href="{{ $cHref('client.dashboard') }}"
                                class="{{ request()->routeIs('client.dashboard') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icon('dashboard') !!}<span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.accounts.index') }}"
                                class="{{ request()->routeIs('client.accounts*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icon('accounts') !!}<span>Contas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.invoices.index') }}"
                                class="{{ request()->routeIs('client.invoices*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icon('invoices') !!}<span>Faturas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.goals.index') }}"
                                class="{{ request()->routeIs('client.goals') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icon('monthly_goals') !!}<span>Metas mensais</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.transactions.index') }}"
                                class="{{ request()->routeIs('client.transactions*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icon('transactions') !!}<span>Transações</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.objectives.index') }}"
                                class="{{ request()->routeIs('client.objectives*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icon('objectives') !!}<span>Objetivos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.tasks.index') }}"
                                class="{{ request()->routeIs('client.tasks*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icon('tasks') !!}<span>Tarefas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.investments.index') }}"
                                class="{{ request()->routeIs('client.investments*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icon('investments') !!}<span>Investimentos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $cHref('client.accountability.index') }}"
                                class="{{ request()->routeIs('client.accountability*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icon('reports') !!}<span>Prestação de contas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('settings') }}"
                                class="{{ request()->routeIs('settings*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icon('settings') !!}<span>Configurações</span>
                            </a>
                        </li>
                    @else
                        <li>
                            <a href="{{ route('dashboard') }}"
                                class="{{ request()->routeIs('dashboard') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['dashboard'] !!}<span>Dashboard</span>
                            </a>
                        </li>
                    @endif
                </ul>

                {{-- Menus colapsados --}}
                <ul class="menu px-2 gap-4" x-show="collapsed" x-transition>
                    @if ($role === 'admin')
                        @php($c = $collapsedLink('admin.dashboard'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ route('admin.dashboard') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icons['dashboard'] !!}</span>
                                <span class="{{ $c['label'] }}">Dashboard</span>
                            </a>
                        </li>

                        @php($c = $collapsedLink('admin.consultants.*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Consultores">
                            <a href="{{ route('admin.consultants.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icons['users'] !!}</span>
                                <span class="{{ $c['label'] }}">Consultores</span>
                            </a>
                        </li>

                        @php($c = $collapsedLink('settings*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Configurações">
                            <a href="{{ route('settings') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icons['settings'] !!}</span>
                                <span class="{{ $c['label'] }}">Configurações</span>
                            </a>
                        </li>
                    @elseif ($role === 'consultant')
                        @php($c = $collapsedLink('consultant.dashboard'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ $rConsultant('consultant.dashboard') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icons['dashboard'] !!}</span>
                                <span class="{{ $c['label'] }}">Dashboard</span>
                            </a>
                        </li>

                        @php($c = $collapsedLink('consultant.clients.*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Clientes">
                            <a href="{{ $rConsultant('consultant.clients.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icons['clients'] !!}</span>
                                <span class="{{ $c['label'] }}">Clientes</span>
                            </a>
                        </li>

                        @php($c = $collapsedLink('consultant.tasks.*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Tarefas">
                            <a href="{{ $rConsultant('consultant.tasks.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icons['tasks'] !!}</span>
                                <span class="{{ $c['label'] }}">Tarefas</span>
                            </a>
                        </li>

                        @php($c = $collapsedLink('consultant.categories.*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Categorias">
                            <a href="{{ $rConsultant('consultant.categories.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icons['categories'] !!}</span>
                                <span class="{{ $c['label'] }}">Categorias</span>
                            </a>
                        </li>

                        @php($c = $collapsedLink('settings*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Configurações">
                            <a href="{{ route('settings') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icons['settings'] !!}</span>
                                <span class="{{ $c['label'] }}">Configurações</span>
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

                        @php($c = $collapsedLink('client.goals'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Metas mensais">
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

                        @php($c = $collapsedLink('client.objectives*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Objetivos">
                            <a href="{{ $cHref('client.objectives.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('objectives') !!}</span>
                                <span class="{{ $c['label'] }}">Objetivos</span>
                            </a>
                        </li>

                        @php($c = $collapsedLink('client.tasks*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Tarefas">
                            <a href="{{ $cHref('client.tasks.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('tasks') !!}</span>
                                <span class="{{ $c['label'] }}">Tarefas</span>
                            </a>
                        </li>

                        @php($c = $collapsedLink('client.investments*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Investimentos">
                            <a href="{{ $cHref('client.investments.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('investments') !!}</span>
                                <span class="{{ $c['label'] }}">Investimentos</span>
                            </a>
                        </li>

                        @php($c = $collapsedLink('client.accountability*'))
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Prestação de contas">
                            <a href="{{ $cHref('client.accountability.index') }}" class="{{ $c['a'] }}"
                                aria-current="{{ $c['aria'] }}">
                                <span class="{{ $c['icon'] }}">{!! $icon('reports') !!}</span>
                                <span class="{{ $c['label'] }}">Contas</span>
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
                                <span class="{{ $c['icon'] }}">{!! $icons['dashboard'] !!}</span>
                                <span class="{{ $c['label'] }}">Dashboard</span>
                            </a>
                        </li>
                    @endif
                </ul>

            </aside>
        </div>
    </div>
</body>

</html>
