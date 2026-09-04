<x-communications-layout title="Comunicación">

    <div class="h-[calc(100vh-92px)] lg:h-[calc(100vh-108px)] min-h-[420px] flex border border-[var(--border-default)] rounded-[var(--radius-card)] overflow-hidden shadow-[var(--shadow-card)] bg-[var(--surface-card)]">
        @include('communications._channel-sidebar')

        <div class="flex-1 flex items-center justify-center relative overflow-hidden bg-[var(--surface-card)]">
            {{-- Resplandor suave detrás del ícono: le da vida sin igualar el fondo gris de la
                 página, que haría que el borde de la tarjeta se pierda visualmente --}}
            <div class="absolute inset-0 [background:radial-gradient(circle_at_50%_42%,var(--color-primary-light)_0%,transparent_55%)]"></div>
            <div class="relative text-center px-6 py-10 max-w-xs">
                <div class="w-16 h-16 bg-[var(--surface-card)] shadow-[var(--shadow-card-hover)] rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-lucide-message-square class="w-7 h-7 text-[var(--color-primary)]" />
                </div>
                <p class="text-[15px] font-semibold text-[var(--text-900)]">Selecciona un canal</p>
                <p class="text-[13px] text-[var(--text-500)] mt-1.5">Elige un canal de la izquierda o crea uno nuevo para empezar a conversar con tu equipo.</p>
                <button type="button" @click="$dispatch('open-modal', 'nuevo-canal')"
                        class="mt-5 inline-flex items-center gap-[6px] h-9 px-4 rounded-full bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[13px] font-medium shadow-[var(--shadow-card)]">
                    <x-lucide-plus class="w-3.5 h-3.5" />
                    Nuevo canal
                </button>
            </div>
        </div>
    </div>

    @include('communications._new-channel-modal')

</x-communications-layout>
