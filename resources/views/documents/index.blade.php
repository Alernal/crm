<x-app-layout>
    <x-slot name="title">Documentos</x-slot>

    <div class="max-w-6xl mx-auto space-y-5">

        {{-- ===== CABECERA ===== --}}
        <p class="text-[13px] text-[var(--text-500)]">Gestiona tus documentos de identificación con marca de agua personalizada.</p>

        {{-- Flash --}}
        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)"
             class="flex items-center gap-3 p-4 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 rounded-[var(--radius-control)] text-[var(--color-success-text)] text-[14px]">
            <x-lucide-check-circle class="w-5 h-5 flex-shrink-0" />
            {{ session('success') }}
        </div>
        @endif

        {{-- Aviso de uso --}}
        <div class="flex items-start gap-3 p-4 bg-[var(--color-warning-bg)] border border-[#FCD34D] rounded-[10px]">
            <x-lucide-alert-triangle class="w-[18px] h-[18px] text-[var(--color-warning)] flex-shrink-0 mt-0.5" />
            <div class="text-[14px] text-[var(--color-warning-text)]">
                <p class="font-semibold">¿Para qué sirve la marca de agua?</p>
                <p class="mt-0.5">Al adjuntar tu cédula o tarjeta profesional a trámites de clientes, agrega una marca de agua
                con el nombre del destinatario para evitar que el documento sea usado en otros propósitos.</p>
            </div>
        </div>

        {{-- ===== TABS: Cédula | Tarjeta Profesional ===== --}}
        <div x-data="{ tab: '{{ $cedula ? 'cedula' : 'tarjeta' }}' }">

            <div class="flex gap-1 border-b border-[var(--border-default)] mb-6">
                <button @click="tab = 'cedula'"
                    :class="tab === 'cedula'
                        ? 'border-b-2 border-[var(--color-primary)] text-[var(--color-primary)] font-semibold'
                        : 'text-[var(--text-500)] hover:text-[var(--text-700)]'"
                    class="flex items-center gap-2 px-5 py-3 text-[14px] -mb-px">
                    <x-lucide-user class="w-4 h-4" />
                    Cédula de Ciudadanía
                    @if($cedula)
                    <span class="w-2 h-2 rounded-full bg-[var(--color-success)] ml-0.5"></span>
                    @endif
                </button>
                <button @click="tab = 'tarjeta'"
                    :class="tab === 'tarjeta'
                        ? 'border-b-2 border-[var(--color-primary)] text-[var(--color-primary)] font-semibold'
                        : 'text-[var(--text-500)] hover:text-[var(--text-700)]'"
                    class="flex items-center gap-2 px-5 py-3 text-[14px] -mb-px">
                    <x-lucide-shield-check class="w-4 h-4" />
                    Tarjeta Profesional
                    @if($tarjeta)
                    <span class="w-2 h-2 rounded-full bg-[var(--color-success)] ml-0.5"></span>
                    @endif
                </button>
            </div>

            {{-- ========== CÉDULA ========== --}}
            <div x-show="tab === 'cedula'"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0">
                @include('documents._panel', [
                    'document'  => $cedula,
                    'type'      => 'cedula',
                    'label'     => 'Cédula de Ciudadanía',
                    'panelId'   => 'cedula',
                ])
            </div>

            {{-- ========== TARJETA PROFESIONAL ========== --}}
            <div x-show="tab === 'tarjeta'"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0">
                @include('documents._panel', [
                    'document'  => $tarjeta,
                    'type'      => 'tarjeta_profesional',
                    'label'     => 'Tarjeta Profesional',
                    'panelId'   => 'tarjeta',
                ])
            </div>

        </div>
    </div>

</x-app-layout>
