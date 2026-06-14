<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name', 'CRM Profesional') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">

<div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">

    {{-- Overlay móvil --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
    ></div>

    {{-- ===== SIDEBAR ===== --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-30 w-64 bg-blue-900 flex flex-col transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-0"
    >
        {{-- Logo --}}
        <div class="h-16 flex items-center gap-3 px-5 border-b border-blue-800 flex-shrink-0">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-blue-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="text-white font-bold text-base tracking-tight">CRM Profesional</span>
        </div>

        {{-- Navegación --}}
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">

            @php
                $navItems = [
                    ['route' => 'dashboard',  'label' => 'Dashboard',           'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['route' => 'clients.index',  'label' => 'Clientes',        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['route' => 'services.index', 'label' => 'Servicios',       'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                    ['route' => 'invoices.index', 'label' => 'Cuentas de Cobro','icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['route' => 'payments.index', 'label' => 'Cartera',         'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                    ['route' => 'tax-events.index','label' => 'Calendario Tributario','icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ['route' => 'documents.index','label' => 'Documentos',      'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                ];
            @endphp

            @foreach($navItems as $item)
                @php $isActive = request()->routeIs($item['route']); @endphp
                <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ $isActive ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['icon'] }}"/>
                    </svg>
                    {{ $item['label'] }}
                </a>
            @endforeach

        </nav>

        {{-- Usuario y logout --}}
        <div class="border-t border-blue-800 p-3 flex-shrink-0">
            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-200 hover:bg-blue-800 hover:text-white transition-colors {{ request()->routeIs('profile.*') ? 'bg-blue-800 text-white' : '' }}">
                <div class="w-8 h-8 rounded-full bg-blue-700 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate text-white">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-blue-300 truncate">Mi perfil</p>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-blue-300 hover:bg-blue-800 hover:text-white transition-colors">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    {{-- ===== CONTENIDO PRINCIPAL ===== --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Top bar --}}
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-6 flex-shrink-0">
            <div class="flex items-center gap-4">
                {{-- Hamburger móvil --}}
                <button @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                {{-- Título de página --}}
                <h1 class="text-gray-900 font-semibold text-base">
                    {{ $title ?? 'Dashboard' }}
                </h1>
            </div>

            <div class="flex items-center gap-3">
                {{-- Alertas de vencimientos (badge) --}}
                @php
                    $alertCount = Auth::user()->taxEvents()
                        ->where('status', 'pending')
                        ->whereBetween('due_date', [now(), now()->addDays(15)])
                        ->count();
                @endphp
                @if($alertCount > 0)
                <a href="{{ Route::has('tax-events.index') ? route('tax-events.index') : '#' }}"
                   class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                        {{ $alertCount > 9 ? '9+' : $alertCount }}
                    </span>
                </a>
                @endif

                {{-- Avatar --}}
                <a href="{{ route('profile.edit') }}"
                   class="w-8 h-8 rounded-full bg-blue-700 flex items-center justify-center text-white text-xs font-bold hover:bg-blue-800 transition-colors">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </a>
            </div>
        </header>

        {{-- Contenido --}}
        <main class="flex-1 overflow-y-auto p-4 lg:p-6">
            {{ $slot }}
        </main>
    </div>

</div>

</body>
</html>
