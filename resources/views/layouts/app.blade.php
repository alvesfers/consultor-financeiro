<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Consultor Financeiro') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen"
      x-data="{
        collapsed: JSON.parse(localStorage.getItem('sbCollapsed') ?? 'false'),
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sbCollapsed', JSON.stringify(this.collapsed));
        }
      }">

@php
    $user  = auth()->user();
    $role  = $user->role ?? null;
    $consultantId = $user?->consultant?->id ?? $user?->client?->consultant_id ?? null;
@endphp

<div class="drawer lg:drawer-open">
  <input id="app-drawer" type="checkbox" class="drawer-toggle" />
  <div class="drawer-content flex flex-col">

    <!-- Topbar -->
    <div class="navbar bg-base-100 border-b border-base-300 px-4 sticky top-0 z-20">
      <div class="flex-none lg:hidden">
        <label for="app-drawer" aria-label="open sidebar" class="btn btn-ghost btn-square">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </label>
      </div>

      <div class="flex-1">
        <a href="{{ route('home') }}" class="btn btn-ghost text-xl">{{ config('app.name', 'Consultor Financeiro') }}</a>
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
            <li><a href="{{ route('profile.edit') }}">Perfil</a></li>
            <li>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Sair</button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <main class="p-4 lg:p-6">
      @yield('content')
    </main>
  </div>

  <!-- Sidebar -->
  <div class="drawer-side">
    <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>

    <aside class="min-h-full border-r border-base-300 bg-base-200 transition-[width] duration-200 ease-in-out"
           :class="collapsed ? 'lg:w-24' : 'lg:w-72'">

      <!-- Header -->
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

        <button type="button"
                class="btn btn-ghost btn-sm hidden lg:flex"
                @click="toggle()"
                :title="collapsed ? 'Expandir' : 'Recolher'">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform"
               :class="collapsed ? '' : 'rotate-180'"
               viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m20 12-4 4m4-4-4-4M4 12h16"/>
          </svg>
        </button>
      </div>

      @php
        // helpers internos p/ ícones
        function ico($path){ return "<svg class='h-5 w-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='{$path}'/></svg>"; }
        $icons = [
            'dashboard' => ico("M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3M9 21h6"),
            'users'     => ico("M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M12 12a4 4 0 100-8 4 4 0 000 8z"),
            'clients'   => ico("M5.121 17.804A7 7 0 0112 15a7 7 0 016.879 2.804M15 11a3 3 0 10-6 0 3 3 0 006 0z"),
            'tasks'     => ico("m9 12 2 2 4-4M7 7h10M7 17h10"),
            'settings'  => ico("M10.325 4.317l.366-1.098A1 1 0 0111.65 2h.7a1 1 0 01.959.69l.366 1.098a1 1 0 00.95.69h1.154a1 1 0 01.95.69l.366 1.098a1 1 0 00.95.69l1.154.001a1 1 0 01.95.69l.366 1.098a1 1 0 00.95.69h.7M3 12h3m0 0a9 9 0 1018 0 9 9 0 10-18 0zM3 12a9 9 0 0018 0"),
        ];
      @endphp

      <!-- menus expandidos -->
      <ul class="menu px-3 gap-2" x-show="!collapsed" x-transition>
        @if ($role === 'admin')
          <li><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">{!! $icons['dashboard'] !!}<span>Dashboard</span></a></li>
          <li><a href="{{ route('admin.consultants.index') }}" class="{{ request()->routeIs('admin.consultants.*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">{!! $icons['users'] !!}<span>Consultores</span></a></li>
          <li><a href="{{ route('settings') }}" class="{{ request()->routeIs('settings*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">{!! $icons['settings'] !!}<span>Configurações</span></a></li>

        @elseif ($role === 'consultant')
          <li><a href="{{ route('consultants.dashboard', $consultantId) }}" class="{{ request()->routeIs('consultant.dashboard') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">{!! $icons['dashboard'] !!}<span>Dashboard</span></a></li>
          <li><a href="{{ route('consultants.clients.index', $consultantId) }}" class="{{ request()->routeIs('consultants.clients.*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">{!! $icons['clients'] !!}<span>Clientes</span></a></li>
          <li><a href="{{ route('consultants.tasks.index', $consultantId) }}" class="{{ request()->routeIs('consultants.tasks.*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">{!! $icons['tasks'] !!}<span>Tarefas</span></a></li>
          <li><a href="{{ route('settings') }}" class="{{ request()->routeIs('settings*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">{!! $icons['settings'] !!}<span>Configurações</span></a></li>

        @elseif ($role === 'client')
          <li><a href="{{ route('client.dashboard') }}" class="{{ request()->routeIs('client.dashboard') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">{!! $icons['dashboard'] !!}<span>Dashboard</span></a></li>
          <li><a href="{{ route('settings') }}" class="{{ request()->routeIs('settings*') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">{!! $icons['settings'] !!}<span>Configurações</span></a></li>

        @else
          <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }} flex items-center gap-3 py-3 rounded-lg hover:bg-base-100">{!! $icons['dashboard'] !!}<span>Dashboard</span></a></li>
        @endif
      </ul>

      <!-- menus colapsados -->
      <ul class="menu px-2 gap-4" x-show="collapsed" x-transition>
        @foreach (['admin'=>'admin.dashboard','consultant'=>'consultants.dashboard','client'=>'client.dashboard'] as $r=>$route)
          @if($role===$r)
            @php $label = ucfirst($r); @endphp
            <li class="flex justify-center tooltip tooltip-right" data-tip="Dashboard">
              <a href="{{ route($route, $consultantId) }}" class="flex flex-col items-center py-3 rounded-lg hover:bg-base-100">
                {!! $icons['dashboard'] !!}
                <span class="text-[10px] mt-1 leading-none">Dashboard</span>
              </a>
            </li>
          @endif
        @endforeach
      </ul>

    </aside>
  </div>
</div>

</body>
</html>
