<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name', 'CRM Profesional') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-[var(--surface-page)]">

<div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden bg-[var(--surface-page)]">

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
        class="fixed inset-y-0 left-0 z-30 w-[220px] bg-[var(--surface-card)] border-r border-[var(--border-default)] flex flex-col transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-0"
    >
        {{-- Logo --}}
        <div class="h-[60px] flex items-center gap-[10px] px-4 border-b border-[var(--border-default)] flex-shrink-0">
            <div class="w-7 h-7 rounded-[6px] bg-[var(--color-primary)] flex items-center justify-center flex-shrink-0">
                <svg class="w-[16px] h-[16px] text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
            </div>
            <span class="text-[14px] font-semibold text-[var(--text-900)] tracking-tight">CRM Profesional</span>
        </div>

        {{-- Navegación --}}
        <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">

            @php
                $navItems = [
                    ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
                    [
                        'group'    => 'Ventas',
                        'icon'     => 'shopping-cart',
                        'children' => [
                            ['route' => 'clients.index',  'label' => 'Clientes',         'icon' => 'users'],
                            ['route' => 'services.index', 'label' => 'Servicios',        'icon' => 'briefcase'],
                            ['route' => 'invoices.index', 'label' => 'Cuentas de Cobro', 'icon' => 'file-text'],
                            ['route' => 'cartera.index',  'label' => 'Cartera',          'icon' => 'wallet'],
                        ],
                    ],
                    [
                        'group'    => 'Nómina',
                        'icon'     => 'banknote',
                        'children' => [
                            ['route' => 'employees.index',       'label' => 'Empleados',          'icon' => 'users-round'],
                            ['route' => 'payroll-periods.index', 'label' => 'Períodos de Nómina',  'icon' => 'calendar-clock'],
                            ['route' => 'prima-settlements.index',    'label' => 'Primas',      'icon' => 'gift'],
                            ['route' => 'cesantia-settlements.index', 'label' => 'Cesantías',   'icon' => 'piggy-bank'],
                        ],
                    ],
                    ['route' => 'tax-events.index', 'label' => 'Calendario Tributario', 'icon' => 'calendar-check'],
                    ['route' => 'financial.index',  'label' => 'Financiero',            'icon' => 'bar-chart-2'],
                    ['route' => 'documents.index',  'label' => 'Documentos',            'icon' => 'shield-check'],
                ];

                if (Auth::user()?->is_admin) {
                    $navItems[] = ['route' => 'admin.dashboard', 'label' => 'Panel Admin', 'icon' => 'settings'];
                }

                $routeActive = fn($route) => request()->routeIs(preg_replace('/\.\w+$/', '.*', $route));
            @endphp

            @foreach($navItems as $item)
                @if(isset($item['group']))
                    @php $groupActive = collect($item['children'])->contains(fn($c) => $routeActive($c['route'])); @endphp
                    <div x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open"
                                class="w-full flex items-center gap-[10px] mx-2 my-0.5 px-3 py-[9px] rounded-[var(--radius-control)] text-[14px]
                                       {{ $groupActive ? 'bg-[var(--color-primary-light)] text-[var(--color-primary)] font-medium' : 'font-normal text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)]' }}">
                            @svg('lucide-' . $item['icon'], 'w-[18px] h-[18px] flex-shrink-0 ' . ($groupActive ? 'text-[var(--color-primary)]' : ''))
                            <span class="flex-1 text-left">{{ $item['group'] }}</span>
                            <span :class="open ? 'rotate-180' : ''" class="transition-transform duration-150">
                                <x-lucide-chevron-down class="w-3.5 h-3.5 flex-shrink-0 {{ $groupActive ? 'text-[var(--color-primary)]' : 'text-[var(--text-400)]' }}" />
                            </span>
                        </button>
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0">
                            @foreach($item['children'] as $child)
                                @php $isActive = $routeActive($child['route']); @endphp
                                <a href="{{ Route::has($child['route']) ? route($child['route']) : '#' }}"
                                   class="flex items-center gap-[10px] mx-2 my-0.5 ml-6 px-3 py-[8px] rounded-[var(--radius-control)] text-[13px]
                                          {{ $isActive ? 'bg-[var(--color-primary-light)] text-[var(--color-primary)] font-medium' : 'font-normal text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)]' }}">
                                    @svg('lucide-' . $child['icon'], 'w-4 h-4 flex-shrink-0 ' . ($isActive ? 'text-[var(--color-primary)]' : ''))
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    @php $isActive = $routeActive($item['route']); @endphp
                    <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                       class="flex items-center gap-[10px] mx-2 my-0.5 px-3 py-[9px] rounded-[var(--radius-control)] text-[14px]
                              {{ $isActive ? 'bg-[var(--color-primary-light)] text-[var(--color-primary)] font-medium' : 'font-normal text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)]' }}">
                        @svg('lucide-' . $item['icon'], 'w-[18px] h-[18px] flex-shrink-0 ' . ($isActive ? 'text-[var(--color-primary)]' : ''))
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach

        </nav>

        {{-- Logout --}}
        <div class="border-t border-[var(--border-default)] p-3 flex-shrink-0">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-[10px] px-3 py-2 rounded-[var(--radius-control)] text-[14px] text-[var(--text-500)] hover:bg-[var(--surface-muted)] hover:text-[var(--color-danger)]">
                    <x-lucide-log-out class="w-[18px] h-[18px] flex-shrink-0" />
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    {{-- ===== CONTENIDO PRINCIPAL ===== --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Top bar --}}
        <header class="h-[60px] bg-[var(--surface-card)] border-b border-[var(--border-default)] flex items-center justify-between px-4 lg:px-6 flex-shrink-0">
            <div class="flex items-center gap-4">
                {{-- Hamburger móvil --}}
                <button @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden p-2 rounded-[var(--radius-control)] text-[var(--text-500)] hover:text-[var(--text-700)] hover:bg-[var(--surface-muted)]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                {{-- Título de página --}}
                <h1 class="text-[15px] font-medium text-[var(--text-900)]">
                    {{ $title ?? 'Dashboard' }}
                </h1>
            </div>

            <div class="flex items-center gap-2">

                {{-- Avatar --}}
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-2 px-2 py-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] group">
                    <div class="w-9 h-9 rounded-full bg-[var(--color-primary)] flex items-center justify-center text-white text-[13px] font-semibold flex-shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div class="hidden sm:block min-w-0">
                        <p class="text-[14px] font-medium text-[var(--text-900)] truncate max-w-[120px] leading-tight">{{ Auth::user()->name }}</p>
                        <p class="text-[12px] text-[var(--text-400)] leading-tight">Mi perfil</p>
                    </div>
                </a>
            </div>
        </header>

        {{-- Contenido --}}
        <main class="flex-1 overflow-y-auto p-4 lg:p-6">
            <div class="max-w-7xl mx-auto">
                {{ $slot }}
            </div>
        </main>
    </div>

</div>

</body>
</html>
