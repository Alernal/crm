<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="color-scheme" content="light" />
    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name', 'CRM Profesional') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
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
            <div class="w-8 h-8 rounded-[8px] bg-[var(--color-primary)] flex items-center justify-center flex-shrink-0 shadow-[var(--shadow-card)]">
                <svg class="w-[17px] h-[17px] text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
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
                            ['route' => 'contract-settlements.index', 'label' => 'Liquidación de Contratos', 'icon' => 'file-minus'],
                            ['route' => 'vacation-control.index', 'active' => ['vacation-control.*'], 'label' => 'Control de Vacaciones', 'icon' => 'calendar-days'],
                        ],
                    ],
                    ['route' => 'tax-events.index', 'label' => 'Calendario Tributario', 'icon' => 'calendar-check'],
                    [
                        'group'    => 'Financiero',
                        'icon'     => 'bar-chart-2',
                        'children' => [
                            // `active`: rutas propias de cada submenú (financial.show/edit/pdf... son
                            // compartidas entre los dos tipos, así que no se pueden distinguir solo
                            // por nombre de ruta — quedan sin resaltar en ninguno de los dos).
                            ['route' => 'financial.index',            'active' => ['financial.index', 'financial.client', 'financial.create'], 'label' => 'Presupuestos',        'icon' => 'trending-up'],
                            ['route' => 'financial.statements.index', 'active' => ['financial.statements.*'],                                   'label' => 'Estados Financieros', 'icon' => 'scale'],
                        ],
                    ],
                    [
                        'group'    => 'Documentos',
                        'icon'     => 'folder-open',
                        'children' => [
                            // `active` explícito en ambos: el nombre de ruta 'documents.index' generaría
                            // por defecto el patrón comodín 'documents.*', que también matchea
                            // 'documents.contracts.*' (mismo prefijo) y encendería los dos enlaces a la vez.
                            ['route' => 'documents.contracts.index', 'active' => ['documents.contracts.*', 'documents.proposals.*'], 'label' => 'Contratos y Propuestas', 'icon' => 'file-signature'],
                            ['route' => 'documents.certificates.index', 'active' => ['documents.certificates.*'], 'label' => 'Certificados', 'icon' => 'award'],
                            ['route' => 'documents.index',           'active' => ['documents.index', 'documents.store', 'documents.show', 'documents.destroy'], 'label' => 'Documentos Personales', 'icon' => 'shield-check'],
                        ],
                    ],
                    ['route' => 'communications.index', 'active' => ['communications.*'], 'label' => 'Comunicación', 'icon' => 'message-square'],
                ];

                if (Auth::user()?->is_admin) {
                    $navItems[] = ['route' => 'admin.dashboard', 'label' => 'Panel Admin', 'icon' => 'settings'];
                }

                $routeActive = function ($routeOrPatterns) {
                    $patterns = is_array($routeOrPatterns) ? $routeOrPatterns : [preg_replace('/\.\w+$/', '.*', $routeOrPatterns)];
                    return request()->routeIs(...$patterns);
                };

                // Estilo único para TODO ítem que es una página real (sueltos y los hijos de un
                // grupo) — misma escala tipográfica y misma mecánica de estado activo (barra de
                // acento a la izquierda + fondo) en los dos casos, para que no se perciban como
                // dos sistemas de navegación distintos.
                $navLinkClass = function (bool $isActive): string {
                    $base = 'flex items-center gap-[8px] rounded-[var(--radius-control)] text-[13.5px] border-l-[3px] pl-[9px] pr-3 py-[8px] transition-colors';
                    return $isActive
                        ? $base . ' border-[var(--color-primary)] bg-[var(--color-primary-light)] text-[var(--color-primary)] font-semibold'
                        : $base . ' border-transparent font-normal text-[var(--text-500)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)]';
                };
                $navIconClass = fn (bool $isActive) => 'w-4 h-4 flex-shrink-0 ' . ($isActive ? 'text-[var(--color-primary)]' : 'text-[var(--text-400)]');
            @endphp

            @foreach($navItems as $item)
                @if(isset($item['group']))
                    @php $groupActive = collect($item['children'])->contains(fn($c) => $routeActive($c['active'] ?? $c['route'])); @endphp
                    <div x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }" class="mt-3 first:mt-0">
                        {{-- Encabezado de sección: rótulo plano en negrita, sin fondo ni barra de
                             acento — se lee como categoría, nunca como un destino más de la lista --}}
                        <button type="button" @click="open = !open"
                                class="w-full flex items-center gap-[8px] mx-2 mb-1 px-3 py-1.5 rounded-[var(--radius-control)] text-[13.5px] font-normal transition-colors
                                       {{ $groupActive ? 'text-[var(--color-primary)]' : 'text-[var(--text-500)] hover:text-[var(--text-900)]' }}">
                            @svg('lucide-' . $item['icon'], 'w-[15px] h-[15px] flex-shrink-0 ' . ($groupActive ? 'text-[var(--color-primary)]' : 'text-[var(--text-400)]'))
                            <span class="flex-1 text-left">{{ $item['group'] }}</span>
                            <span :class="open ? 'rotate-180' : ''" class="transition-transform duration-150">
                                <x-lucide-chevron-down class="w-3 h-3 flex-shrink-0 {{ $groupActive ? 'text-[var(--color-primary)]' : 'text-[var(--text-400)]' }}" />
                            </span>
                        </button>
                        {{-- Hijos: misma clase de enlace que un ítem suelto, solo que indentados y
                             sujetos por una línea conectora a su categoría --}}
                        <div x-show="open"
                             @if(!$groupActive) x-cloak @endif
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="ml-[27px] mr-2 mb-1 pl-3 border-l border-[var(--border-default)] space-y-0.5">
                            @foreach($item['children'] as $child)
                                @php $isActive = $routeActive($child['active'] ?? $child['route']); @endphp
                                <a href="{{ Route::has($child['route']) ? route($child['route']) : '#' }}"
                                   class="{{ $navLinkClass($isActive) }}">
                                    @svg('lucide-' . $child['icon'], $navIconClass($isActive))
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    @php $isActive = $routeActive($item['route']); @endphp
                    <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                       class="mx-2 my-0.5 {{ $navLinkClass($isActive) }}">
                        @svg('lucide-' . $item['icon'], $navIconClass($isActive))
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
                        class="w-full flex items-center gap-[8px] px-3 py-2 rounded-[var(--radius-control)] text-[13.5px] text-[var(--text-500)] hover:bg-[var(--surface-muted)] hover:text-[var(--color-danger)]">
                    <x-lucide-log-out class="w-4 h-4 flex-shrink-0" />
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

                {{-- Notificaciones: vencimientos tributarios --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="relative w-9 h-9 flex items-center justify-center rounded-full text-[var(--text-500)] hover:text-[var(--text-900)] hover:bg-[var(--surface-muted)]"
                            title="Vencimientos tributarios">
                        <x-lucide-bell class="w-5 h-5" />
                        @if($taxAlertsTotalClientCount > 0)
                        <span class="absolute top-0.5 right-0.5 min-w-[16px] h-4 px-1 rounded-full text-white text-[10px] font-semibold flex items-center justify-center leading-none"
                              style="background: {{ $taxAlertsOverdueClientCount > 0 ? 'var(--color-danger)' : 'var(--color-warning)' }};">
                            {{ $taxAlertsTotalClientCount > 9 ? '9+' : $taxAlertsTotalClientCount }}
                        </span>
                        @endif
                    </button>

                    <div x-show="open" x-cloak @click.away="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-[22rem] bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] z-50 overflow-hidden">

                        <div class="px-4 py-3 border-b border-[var(--border-default)] flex items-center justify-between">
                            <h3 class="text-[14px] font-bold text-[var(--text-900)]">Vencimientos tributarios</h3>
                            <a href="{{ route('tax-events.index') }}" class="text-[12.5px] text-[var(--color-primary)] hover:underline font-medium">
                                Ver calendario
                            </a>
                        </div>

                        <div class="max-h-[26rem] overflow-y-auto">
                            @forelse($taxAlerts as $alert)
                            <a href="{{ route('tax-events.client-calendar', $alert['client']) }}"
                               class="flex items-start gap-3 px-4 py-3 border-b border-[var(--border-default)] last:border-b-0 hover:bg-[var(--surface-subtle)]">
                                <div class="w-2 h-2 rounded-full mt-[7px] flex-shrink-0"
                                     style="background: {{ $alert['overdueCount'] > 0 ? 'var(--color-danger)' : 'var(--color-warning)' }};"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[13.5px] font-semibold text-[var(--text-900)] truncate">{{ $alert['client']->name }}</p>
                                    <p class="text-[12px] text-[var(--text-500)] mt-0.5 truncate">{{ $alert['nextEvent']->title }}</p>
                                    <p class="text-[11.5px] mt-1 {{ $alert['nextIsOverdue'] ? 'text-[var(--color-danger)] font-medium' : 'text-[var(--text-400)]' }}">
                                        @if($alert['overdueCount'] > 0)
                                            {{ $alert['overdueCount'] }} vencido{{ $alert['overdueCount'] === 1 ? '' : 's' }}
                                            @if($alert['upcomingCount'] > 0) · {{ $alert['upcomingCount'] }} próximo{{ $alert['upcomingCount'] === 1 ? '' : 's' }} @endif
                                        @else
                                            Vence {{ $alert['nextEvent']->due_date->locale('es')->isoFormat('D MMM') }}
                                            @if($alert['upcomingCount'] > 1) · {{ $alert['upcomingCount'] - 1 }} más @endif
                                        @endif
                                    </p>
                                </div>
                            </a>
                            @empty
                            <div class="px-4 py-10 text-center">
                                <x-lucide-calendar-check class="w-8 h-8 text-[var(--text-400)] mx-auto mb-2" />
                                <p class="text-[13px] text-[var(--text-500)]">Sin vencimientos próximos</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

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
