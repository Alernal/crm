@php
    $units = [
        'mes'         => 'Mes (mensual)',
        'hora'        => 'Hora',
        'declaracion' => 'Declaración',
        'informe'     => 'Informe',
        'visita'      => 'Visita',
        'proyecto'    => 'Proyecto',
        'año'         => 'Año (anual)',
        'otro'        => 'Otro',
    ];
    $inputClass = 'w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] text-[var(--text-700)] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
    $labelClass = 'block text-[13px] font-medium text-[var(--text-700)] mb-1.5';
@endphp

@if($errors->any())
<div class="mb-5 bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 text-[var(--color-danger-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <p class="font-semibold mb-1">Corrige los siguientes errores:</p>
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">

    {{-- Información básica --}}
    <div class="px-6 py-5 border-b border-[var(--border-default)]">
        <h2 class="text-[16px] font-semibold text-[var(--text-900)] mb-4">Información del servicio</h2>
        <div class="grid grid-cols-1 gap-5">

            <div>
                <label for="name" class="{{ $labelClass }}">
                    Nombre del servicio <span class="text-[var(--color-danger)]">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $service->name ?? '') }}"
                       required maxlength="255"
                       placeholder="Ej: Declaración de Renta Persona Natural"
                       class="{{ $inputClass }}" />
                @error('name')
                <p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="{{ $labelClass }}">
                    Descripción
                </label>
                <textarea id="description" name="description" rows="3" maxlength="1000"
                          placeholder="Describe brevemente en qué consiste el servicio..."
                          class="w-full border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 py-2.5 text-[14px] text-[var(--text-700)] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">{{ old('description', $service->description ?? '') }}</textarea>
                @error('description')
                <p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>

    {{-- Precio y unidad --}}
    <div class="px-6 py-5 border-b border-[var(--border-default)]">
        <h2 class="text-[16px] font-semibold text-[var(--text-900)] mb-4">Precio y facturación</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

            <div>
                <label for="base_price" class="{{ $labelClass }}">
                    Precio base (COP) <span class="text-[var(--color-danger)]">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[var(--text-400)] text-[14px] font-medium">$</span>
                    <input type="number" id="base_price" name="base_price"
                           value="{{ old('base_price', isset($service) ? number_format($service->base_price, 2, '.', '') : '') }}"
                           required min="0" max="999999999.99" step="0.01"
                           placeholder="0.00"
                           class="{{ $inputClass }} pl-8" />
                </div>
                @error('base_price')
                <p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="unit" class="{{ $labelClass }}">
                    Unidad de cobro <span class="text-[var(--color-danger)]">*</span>
                </label>
                <select id="unit" name="unit" class="{{ $inputClass }}">
                    @foreach($units as $value => $label)
                    <option value="{{ $value }}" {{ old('unit', $service->unit ?? 'mes') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                @error('unit')
                <p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="{ applyVat: {{ old('applies_vat', ($service->applies_vat ?? false) ? '1' : '0') == '1' ? 'true' : 'false' }} }" class="sm:col-span-2">
                <div class="flex items-start gap-3 p-4 bg-[var(--color-warning-bg)] border border-[#FCD34D] rounded-[var(--radius-control)]">
                    <div class="flex items-center h-5">
                        <input type="hidden" name="applies_vat" value="0" />
                        <input type="checkbox" id="applies_vat" name="applies_vat" value="1"
                               x-model="applyVat"
                               {{ old('applies_vat', $service->applies_vat ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-[var(--border-strong)] text-[var(--color-primary)] focus:ring-[var(--color-primary-light)]" />
                    </div>
                    <div>
                        <label for="applies_vat" class="block text-[14px] font-semibold text-[var(--color-warning-text)] cursor-pointer">
                            Aplica IVA (19%)
                        </label>
                        <p class="text-[12px] text-[var(--color-warning-text)]/80 mt-0.5">Marca esta opción si el servicio está gravado con IVA en Colombia.</p>
                        <p x-show="applyVat" x-cloak class="text-[12px] text-[var(--color-warning-text)] font-medium mt-1">
                            El IVA se calculará automáticamente al generar cuentas de cobro.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Estado --}}
    <div class="px-6 py-5">
        <h2 class="text-[16px] font-semibold text-[var(--text-900)] mb-4">Estado</h2>
        <div class="flex gap-5">
            <label class="flex items-center gap-2.5 cursor-pointer">
                <input type="radio" name="status" value="active"
                       {{ old('status', $service->status ?? 'active') === 'active' ? 'checked' : '' }}
                       class="h-4 w-4 text-[var(--color-primary)] border-[var(--border-strong)] focus:ring-[var(--color-primary-light)]" />
                <span class="text-[14px] text-[var(--text-700)] font-medium">Activo</span>
                <span class="text-[12px] text-[var(--text-400)]">— disponible para cuentas de cobro</span>
            </label>
            <label class="flex items-center gap-2.5 cursor-pointer">
                <input type="radio" name="status" value="inactive"
                       {{ old('status', $service->status ?? 'active') === 'inactive' ? 'checked' : '' }}
                       class="h-4 w-4 text-[var(--color-primary)] border-[var(--border-strong)] focus:ring-[var(--color-primary-light)]" />
                <span class="text-[14px] text-[var(--text-700)] font-medium">Inactivo</span>
                <span class="text-[12px] text-[var(--text-400)]">— no aparece en nuevas cuentas</span>
            </label>
        </div>
        @error('status')
        <p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>
        @enderror
    </div>

</div>
