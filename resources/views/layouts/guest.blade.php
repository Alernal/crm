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
<body class="font-sans antialiased bg-gray-50">

<div class="min-h-screen flex">

    {{-- Panel izquierdo: branding --}}
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 flex-col justify-between p-12 relative overflow-hidden">

        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-700 opacity-20 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-indigo-700 opacity-20 rounded-full translate-y-1/2 -translate-x-1/2"></div>

        <div class="relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-blue-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-white font-bold text-xl tracking-tight">CRM Profesional</span>
            </div>
        </div>

        <div class="relative z-10 space-y-8">
            <div>
                <h1 class="text-4xl font-bold text-white leading-tight">
                    Gestiona tu práctica contable desde un solo lugar.
                </h1>
                <p class="mt-4 text-blue-200 text-lg">
                    Diseñado para contadores públicos en Colombia.
                </p>
            </div>

            <ul class="space-y-4">
                @foreach([
                    ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'text' => 'Base de datos de clientes con datos tributarios'],
                    ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'text' => 'Cuentas de cobro y gestión de cartera'],
                    ['icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'text' => 'Calendario tributario con alertas'],
                    ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'text' => 'Documentos con marca de agua personalizable'],
                ] as $item)
                <li class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-700 bg-opacity-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                        </svg>
                    </div>
                    <span class="text-blue-100 text-sm">{{ $item['text'] }}</span>
                </li>
                @endforeach
            </ul>
        </div>

        <div class="relative z-10">
            <p class="text-blue-400 text-xs">
                &copy; {{ date('Y') }} CRM Profesional &mdash; Para contadores públicos de Colombia
            </p>
        </div>
    </div>

    {{-- Panel derecho: formulario --}}
    <div class="w-full lg:w-1/2 flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16">

        <div class="lg:hidden flex items-center gap-2 mb-10">
            <div class="w-8 h-8 bg-blue-800 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="text-blue-900 font-bold text-lg">CRM Profesional</span>
        </div>

        <div class="w-full max-w-md mx-auto">
            {{ $slot }}
        </div>
    </div>

</div>

</body>
</html>
