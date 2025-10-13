<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Consultor Financeiro') }}</title>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

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

        // helpers de rota com parâmetro do consultor
        $rConsultant = fn($name) => $consultantId ? route($name, ['consultant' => $consultantId]) : route('dashboard');

        // helper p/ FA ícones
        function fa($classes) { return "<i class='{$classes} text-base'></i>"; }

        $icons = [
            'dashboard'  => fa('fa-solid fa-gauge-high'),
            'users'      => fa('fa-solid fa-user-group'),
            'clients'    => fa('fa-solid fa-address-book'),
            'tasks'      => fa('fa-solid fa-list-check'),
            'categories' => fa('fa-solid fa-tags'),
            'settings'   => fa('fa-solid fa-gear'),
        ];
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
                        <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-200 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                            <li><a href="{{ route('profile.edit') }}"><i class="fa-regular fa-user me-2"></i>Perfil</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"><i class="fa-solid fa-right-from-bracket me-2"></i>Sair</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Main --}}
            <main class="p-4 lg:p-6">
                @yield('content')
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
                        <div class="avatar placeholder">
                            <div class="bg-primary text-primary-content rounded w-10">
                                <span class="text-sm font-bold">CF</span>
                            </div>
                        </div>
                        <div class="hidden lg:block" x-show="!collapsed">
                            <div class="font-bold leading-tight">Painel</div>
                            <div class="text-xs opacity-70 capitalize">{{ $role ?? 'user' }}</div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-ghost btn-sm hidden lg:flex" @click="toggle()"
                            :title="collapsed ? 'Expandir' : 'Recolher'">
                        <i class="fa-solid fa-angles-left transition"
                           :class="collapsed ? '' : 'rotate-180'"></i>
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
                            <a href="{{ $rConsultant('client.dashboard') }}"
                               class="{{ request()->routeIs('client.dashboard') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['dashboard'] !!}<span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('settings') }}"
                               class="{{ request()->routeIs('settings*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['settings'] !!}<span>Configurações</span>
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
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['dashboard'] !!}<span class="text-[10px] mt-1 leading-none">Dashboard</span>
                            </a>
                        </li>
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Consultores">
                            <a href="{{ route('admin.consultants.index') }}" class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['users'] !!}<span class="text-[10px] mt-1 leading-none">Consultores</span>
                            </a>
                        </li>
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Configurações">
                            <a href="{{ route('settings') }}" class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['settings'] !!}<span class="text-[10px] mt-1 leading-none">Configurações</span>
                            </a>
                        </li>

                    @elseif ($role === 'consultant')
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ $rConsultant('consultant.dashboard') }}" class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['dashboard'] !!}<span class="text-[10px] mt-1 leading-none">Dashboard</span>
                            </a>
                        </li>
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Clientes">
                            <a href="{{ $rConsultant('consultant.clients.index') }}" class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['clients'] !!}<span class="text-[10px] mt-1 leading-none">Clientes</span>
                            </a>
                        </li>
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Tarefas">
                            <a href="{{ $rConsultant('consultant.tasks.index') }}" class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['tasks'] !!}<span class="text-[10px] mt-1 leading-none">Tarefas</span>
                            </a>
                        </li>
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Categorias">
                            <a href="{{ $rConsultant('consultant.categories.index') }}" class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['categories'] !!}<span class="text-[10px] mt-1 leading-none">Categorias</span>
                            </a>
                        </li>
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Configurações">
                            <a href="{{ route('settings') }}" class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['settings'] !!}<span class="text-[10px] mt-1 leading-none">Configurações</span>
                            </a>
                        </li>

                    @elseif ($role === 'client')
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ $rConsultant('client.dashboard') }}" class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['dashboard'] !!}<span class="text-[10px] mt-1 leading-none">Dashboard</span>
                            </a>
                        </li>
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Configurações">
                            <a href="{{ route('settings') }}" class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['settings'] !!}<span class="text-[10px] mt-1 leading-none">Configurações</span>
                            </a>
                        </li>

                    @else
                        <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
                            <a href="{{ route('dashboard') }}" class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                                {!! $icons['dashboard'] !!}<span class="text-[10px] mt-1 leading-none">Dashboard</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </aside>
        </div>
    </div>
</body>
</html>
