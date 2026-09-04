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
        <h2 class="text-[16px] font-bold text-[var(--text-900)] mb-4">Información del servicio</h2>
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

            <div x-data="serviceCategoryPicker(
                    @js($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()),
                    @json(old('category_id', $service->category_id ?? null)),
                    '{{ csrf_token() }}',
                    '{{ route('services.categories.store') }}'
                 )">
                <label for="category_id" class="{{ $labelClass }}">
                    Categoría
                </label>
                <div class="flex items-center gap-2">
                    <select id="category_id" name="category_id" x-model="selected" class="{{ $inputClass }}">
                        <option value="">Sin categoría</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.id" x-text="cat.name"></option>
                        </template>
                    </select>
                    <button type="button" @click="adding = !adding"
                            class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-500)] hover:bg-[var(--surface-subtle)] hover:text-[var(--text-900)]"
                            title="Nueva categoría">
                        <x-lucide-plus class="w-4 h-4" />
                    </button>
                </div>

                <div x-show="adding" x-cloak class="mt-2 flex items-center gap-2">
                    <input type="text" id="new_category_name" x-model="newName" @keydown.enter.prevent="createCategory()"
                           placeholder="Ej: Finanzas" maxlength="100"
                           class="{{ $inputClass }} h-9" />
                    <button type="button" @click="createCategory()" :disabled="creating"
                            class="flex-shrink-0 h-9 px-3 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[13px] font-medium disabled:opacity-60">
                        Guardar
                    </button>
                    <button type="button" @click="adding = false; newName = ''; error = ''"
                            class="flex-shrink-0 h-9 px-3 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-500)] hover:bg-[var(--surface-subtle)] text-[13px] font-medium">
                        Cancelar
                    </button>
                </div>
                <p x-show="error" x-cloak x-text="error" class="mt-1 text-[12px] text-[var(--color-danger)]"></p>
                @error('category_id')
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
        <h2 class="text-[16px] font-bold text-[var(--text-900)] mb-4">Precio y facturación</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

            <div x-data="{ basePrice: {{ old('base_price', isset($service) ? $service->base_price : 'null') }} }">
                <label for="base_price" class="{{ $labelClass }}">
                    Precio base (COP) <span class="text-[var(--color-danger)]">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[var(--text-400)] text-[14px] font-medium">$</span>
                    <input type="text" id="base_price" name="base_price" x-money="basePrice"
                           required
                           placeholder="0"
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
        <h2 class="text-[16px] font-bold text-[var(--text-900)] mb-4">Estado</h2>
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

<script>
function serviceCategoryPicker(categories, selected, csrfToken, storeUrl) {
    return {
        categories: categories,
        selected: selected,
        adding: false,
        creating: false,
        newName: '',
        error: '',
        createCategory() {
            const name = this.newName.trim();
            if (!name) return;
            this.creating = true;
            this.error = '';
            fetch(storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: JSON.stringify({ name }),
            }).then(async (res) => {
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.errors?.name?.[0] || 'No se pudo crear la categoría.');
                }
                return res.json();
            }).then((cat) => {
                this.categories.push(cat);
                this.selected = cat.id;
                this.newName = '';
                this.adding = false;
            }).catch((err) => {
                this.error = err.message;
            }).finally(() => {
                this.creating = false;
            });
        },
    };
}
</script>
