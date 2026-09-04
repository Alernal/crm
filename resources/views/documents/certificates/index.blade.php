<x-app-layout>
<x-slot name="title">Certificados</x-slot>

@php
    $statusLabels = \App\Models\GeneratedDocument::STATUSES;
@endphp

<div class="flex items-start justify-between mb-6 gap-4">
    <div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">Certificados</p>
        <p class="text-[13px] text-[var(--text-500)] mt-0.5">Motor Documental — genera y administra certificados a partir de plantillas.</p>
    </div>
    <a href="{{ route('documents.certificates.wizard') }}"
       class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium flex-shrink-0">
        <x-lucide-plus class="w-4 h-4" />
        Nuevo Certificado de Ingresos
    </a>
</div>

<div class="bg-[var(--color-primary-light)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-4 py-3 mb-6 text-[13px] text-[var(--text-700)] flex items-start gap-2.5">
    <x-lucide-info class="w-4 h-4 text-[var(--color-primary)] flex-shrink-0 mt-0.5" />
    <span>Certificado de Ingresos, solo para clientes persona natural. <strong>Certificado de Accionistas</strong> sigue en desarrollo.</span>
</div>

@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- Filtros --}}
<form method="GET" action="{{ route('documents.certificates.index') }}" class="mb-5">
    <div class="flex flex-col sm:flex-row gap-3 flex-wrap">
        <div class="flex-1 relative min-w-[200px]">
            <x-lucide-search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-400)]" />
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Buscar por número o cliente..."
                   class="pl-10 w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-subtle)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none" />
        </div>
        <select name="client_id" class="h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-subtle)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none min-w-[160px]">
            <option value="">Todos los clientes</option>
            @foreach($clients as $c)
            <option value="{{ $c->id }}" {{ (string) request('client_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
            Buscar
        </button>
        @if(request()->hasAny(['q','client_id']))
        <a href="{{ route('documents.certificates.index') }}" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
            Limpiar
        </a>
        @endif
    </div>
</form>

<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
@if($documents->isEmpty())
<div class="text-center py-16">
    <div class="w-14 h-14 rounded-[var(--radius-card)] bg-[var(--color-primary-light)] flex items-center justify-center mx-auto mb-4">
        <x-lucide-user-check class="w-7 h-7 text-[var(--color-primary)]" />
    </div>
    @if(request()->hasAny(['q','client_id']))
    <p class="text-[14px] font-semibold text-[var(--text-700)]">No se encontraron certificados con estos filtros</p>
    <a href="{{ route('documents.certificates.index') }}" class="mt-3 inline-block text-[13px] text-[var(--color-primary)] font-medium">Limpiar filtros</a>
    @else
    <p class="text-[14px] font-semibold text-[var(--text-700)]">Aún no has generado ningún certificado</p>
    <p class="text-[12px] text-[var(--text-400)] mt-1">Genera el primero seleccionando un cliente persona natural</p>
    <a href="{{ route('documents.certificates.wizard') }}"
       class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium rounded-[var(--radius-control)]">
        <x-lucide-plus class="w-4 h-4" />
        Nuevo Certificado de Ingresos
    </a>
    @endif
</div>
@else

<div class="overflow-x-auto p-3">
<div class="overflow-y-auto max-h-[65vh]">
    <table class="w-full">
        <thead>
            <tr>
                @php
                    $thClass = 'sticky top-0 z-[1] bg-[var(--surface-card)] border-b border-[var(--border-default)] text-[13px] font-bold text-[var(--text-900)] px-6 py-3.5';
                @endphp
                <th class="{{ $thClass }} text-left">Número</th>
                <th class="{{ $thClass }} text-left">Cliente</th>
                <th class="{{ $thClass }} text-left hidden sm:table-cell">Creado</th>
                <th class="{{ $thClass }} text-center">Estado</th>
                <th class="{{ $thClass }} text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $document)
            @php $st = $statusLabels[$document->status] ?? ['label' => $document->status, 'variant' => 'neutral']; @endphp
            <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                <td class="px-6 py-[14px]">
                    <a href="{{ route('documents.certificates.show', $document) }}"
                       class="text-[14px] text-[var(--text-900)] hover:text-[var(--color-primary)]">
                        {{ $document->documentType->default_prefix }} {{ $document->full_number }}
                    </a>
                </td>
                <td class="px-6 py-[14px] text-[14px] text-[var(--text-500)]">{{ $document->client->name }}</td>
                <td class="px-6 py-[14px] hidden sm:table-cell text-[14px] text-[var(--text-500)]">
                    {{ $document->created_at->locale('es')->isoFormat('D MMM YYYY') }}
                </td>
                <td class="px-6 py-[14px] text-center">
                    <x-status-badge :variant="$st['variant']">{{ $st['label'] }}</x-status-badge>
                </td>
                <td class="px-6 py-[14px] text-right">
                    <div class="flex items-center justify-end gap-[10px]">
                        <a href="{{ route('documents.certificates.show', $document) }}"
                           class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Abrir">
                            <x-lucide-eye class="w-4 h-4" />
                        </a>
                        <a href="{{ route('documents.certificates.pdf', $document) }}"
                           class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Descargar PDF">
                            <x-lucide-download class="w-4 h-4" />
                        </a>
                        <form method="POST" action="{{ route('documents.certificates.destroy', $document) }}"
                              x-data=""
                              x-on:submit.prevent="if(confirm('¿Eliminar «{{ addslashes($document->full_number) }}»?')) $el.submit()">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Eliminar">
                                <x-lucide-trash-2 class="w-4 h-4" />
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
</div>

@if($documents->hasPages())
<div class="px-6 py-4 border-t border-[var(--border-default)]">
    {{ $documents->links() }}
</div>
@endif
@endif
</div>

</x-app-layout>
