<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name', 'CRM Profesional') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-[var(--surface-page)]">

<div class="min-h-screen flex">

    {{-- Panel izquierdo: branding --}}
    <div class="hidden lg:flex lg:w-1/2 flex-col justify-between p-12 relative overflow-hidden" style="background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 100%);">

        <div class="absolute top-0 right-0 w-96 h-96 bg-white opacity-10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white opacity-10 rounded-full translate-y-1/2 -translate-x-1/2"></div>

        <div class="relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white rounded-[6px] flex items-center justify-center">
                    <x-lucide-bar-chart-2 class="w-5 h-5 text-[var(--color-primary)]" />
                </div>
                <span class="text-white font-semibold text-[18px] tracking-tight">CRM Profesional</span>
            </div>
        </div>

        <div class="relative z-10 space-y-8">
            <div>
                <h1 class="text-[32px] font-bold text-white leading-tight">
                    Gestiona tu práctica contable desde un solo lugar.
                </h1>
                <p class="mt-4 text-white/75 text-[16px]">
                    Diseñado para contadores públicos en Colombia.
                </p>
            </div>

            <ul class="space-y-4">
                @foreach([
                    ['icon' => 'users', 'text' => 'Base de datos de clientes con datos tributarios'],
                    ['icon' => 'file-text', 'text' => 'Cuentas de cobro y gestión de cartera'],
                    ['icon' => 'calendar-check', 'text' => 'Calendario tributario con alertas'],
                    ['icon' => 'shield-check', 'text' => 'Documentos con marca de agua personalizable'],
                ] as $item)
                <li class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white/15 rounded-[8px] flex items-center justify-center flex-shrink-0">
                        @svg('lucide-' . $item['icon'], 'w-4 h-4 text-white')
                    </div>
                    <span class="text-white/90 text-[14px]">{{ $item['text'] }}</span>
                </li>
                @endforeach
            </ul>
        </div>

        <div class="relative z-10">
            <p class="text-white/60 text-[12px]">
                &copy; {{ date('Y') }} CRM Profesional &mdash; Para contadores públicos de Colombia
            </p>
        </div>
    </div>

    {{-- Panel derecho: formulario --}}
    <div class="w-full lg:w-1/2 flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16 bg-[var(--surface-page)]">

        <div class="lg:hidden flex items-center gap-2 mb-10">
            <div class="w-8 h-8 rounded-[6px] flex items-center justify-center" style="background: var(--color-primary);">
                <x-lucide-bar-chart-2 class="w-5 h-5 text-white" />
            </div>
            <span class="text-[var(--text-900)] font-semibold text-[16px]">CRM Profesional</span>
        </div>

        <div class="w-full max-w-md mx-auto">
            {{ $slot }}
        </div>
    </div>

</div>

</body>
</html>
