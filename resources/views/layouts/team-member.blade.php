<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="color-scheme" content="light" />
    <title>{{ $title ?? 'Comunicación' }} — {{ config('app.name', 'CRM Profesional') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-[var(--surface-page)]">

<div class="flex h-screen overflow-hidden bg-[var(--surface-page)]">
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Top bar --}}
        <header class="h-[60px] bg-[var(--surface-card)] border-b border-[var(--border-default)] flex items-center justify-between px-4 lg:px-6 flex-shrink-0">
            <div class="flex items-center gap-[10px]">
                <div class="w-8 h-8 rounded-[8px] bg-[var(--color-primary)] flex items-center justify-center flex-shrink-0 shadow-[var(--shadow-card)]">
                    <x-lucide-message-square class="w-[17px] h-[17px] text-white" />
                </div>
                <span class="text-[14px] font-semibold text-[var(--text-900)] tracking-tight">Comunicación — {{ \Illuminate\Support\Str::title(Auth::guard('team_member')->user()?->owner?->name ?? '') }}</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-[8px] bg-[var(--color-primary-light)] flex items-center justify-center text-[var(--color-primary)] text-[12px] font-semibold flex-shrink-0">
                    {{ strtoupper(substr(Auth::guard('team_member')->user()?->name ?? '', 0, 2)) }}
                </div>
                <span class="text-[13.5px] text-[var(--text-700)] hidden sm:inline">{{ \Illuminate\Support\Str::title(Auth::guard('team_member')->user()?->name ?? '') }}</span>
                <form method="POST" action="{{ route('team-member.logout') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-[6px] px-3 py-1.5 rounded-[var(--radius-control)] text-[13.5px] text-[var(--text-500)] hover:bg-[var(--surface-muted)] hover:text-[var(--color-danger)]">
                        <x-lucide-log-out class="w-4 h-4" />
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </header>

        {{-- Contenido --}}
        <main class="flex-1 overflow-hidden">
            {{ $slot }}
        </main>
    </div>
</div>

</body>
</html>
