<x-app-layout>
<x-slot name="title">Servicios</x-slot>

<div class="max-w-3xl mx-auto">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-[14px] text-[var(--text-400)] mb-5">
        <a href="{{ route('services.index') }}" class="hover:text-[var(--color-primary)]">Servicios</a>
        <x-lucide-chevron-right class="w-3.5 h-3.5" />
        <span class="text-[var(--text-700)] font-medium truncate">{{ $service->name }}</span>
    </nav>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
        <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
        {{ session('success') }}
    </div>
    @endif

    {{-- Encabezado --}}
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6 mb-5">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
                    <x-lucide-briefcase class="w-6 h-6 text-[var(--color-primary)]" />
                </div>
                <div>
                    <h1 class="text-[22px] font-semibold text-[var(--text-900)]">{{ $service->name }}</h1>
                    @if($service->description)
                    <p class="text-[14px] text-[var(--text-500)] mt-1 max-w-lg">{{ $service->description }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('services.edit', $service) }}"
                   class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                    <x-lucide-edit-2 class="w-4 h-4" />
                    Editar
                </a>
                <form method="POST" action="{{ route('services.destroy', $service) }}"
                      x-data=""
                      x-on:submit.prevent="if(confirm('¿Eliminar «{{ addslashes($service->name) }}»? Esta acción no se puede deshacer.')) $el.submit()">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border border-[var(--color-danger)]/30 text-[var(--color-danger)] text-[14px] font-medium hover:bg-[var(--color-danger-bg)]">
                        <x-lucide-trash-2 class="w-4 h-4" />
                        Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Detalles --}}
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">

        <div class="px-6 py-5 border-b border-[var(--border-default)]">
            <h2 class="text-[16px] font-semibold text-[var(--text-900)] mb-4">Precio y condiciones</h2>
            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-5">
                <div>
                    <dt class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em] mb-0.5">Precio base</dt>
                    <dd class="text-[22px] font-bold text-[var(--text-900)]">
                        ${{ number_format($service->base_price, 0, ',', '.') }}
                        <span class="text-[14px] font-normal text-[var(--text-400)]">COP</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em] mb-0.5">Unidad</dt>
                    <dd class="text-[14px] font-semibold text-[var(--text-700)] capitalize">{{ $service->unit }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em] mb-0.5">IVA</dt>
                    <dd>
                        @if($service->applies_vat)
                        <x-status-badge variant="warning">Aplica IVA 19%</x-status-badge>
                        <p class="text-[12px] text-[var(--text-500)] mt-1">
                            + ${{ number_format($service->base_price * 0.19, 0, ',', '.') }} IVA
                            = ${{ number_format($service->base_price * 1.19, 0, ',', '.') }} total
                        </p>
                        @else
                        <x-status-badge variant="neutral">No aplica</x-status-badge>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="px-6 py-5 border-b border-[var(--border-default)]">
            <h2 class="text-[16px] font-semibold text-[var(--text-900)] mb-4">Estado del servicio</h2>
            <div class="flex items-center gap-3">
                @if($service->status === 'active')
                <x-status-badge variant="success">Activo</x-status-badge>
                <p class="text-[14px] text-[var(--text-500)]">Disponible para usar en nuevas cuentas de cobro.</p>
                @else
                <x-status-badge variant="neutral">Inactivo</x-status-badge>
                <p class="text-[14px] text-[var(--text-500)]">No aparece al crear cuentas de cobro.</p>
                @endif
            </div>
        </div>

        <div class="px-6 py-4">
            <p class="text-[12px] text-[var(--text-400)]">
                Creado {{ $service->created_at->diffForHumans() }}
                @if($service->updated_at->ne($service->created_at))
                · Actualizado {{ $service->updated_at->diffForHumans() }}
                @endif
            </p>
        </div>

    </div>

</div>
</x-app-layout>
