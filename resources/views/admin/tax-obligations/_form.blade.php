@php $ob = $ob ?? null; @endphp

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Código interno <span class="text-red-400">*</span></label>
        <input type="text" name="code" value="{{ old('code', $ob?->code) }}" required
               placeholder="ej: IVA_BI, RENTA_NAT"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm mono font-medium text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
        <p class="text-[11px] text-gray-400 mt-1">Identificador único, sin espacios.</p>
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Nombre visible <span class="text-red-400">*</span></label>
        <input type="text" name="name" value="{{ old('name', $ob?->name) }}" required
               placeholder="ej: IVA Bimestral"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
    </div>
</div>

<div>
    <label class="block text-xs font-semibold text-gray-600 mb-2">Descripción</label>
    <textarea name="description" rows="2" placeholder="Descripción breve…"
              class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white resize-none focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">{{ old('description', $ob?->description) }}</textarea>
</div>

<div class="grid grid-cols-3 gap-4">
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Periodicidad <span class="text-red-400">*</span></label>
        <select name="periodicity" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
            @foreach (\App\Models\TaxObligationType::$periodicities as $key => $label)
                <option value="{{ $key }}" {{ old('periodicity', $ob?->periodicity) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Referencia NIT <span class="text-red-400">*</span></label>
        <select name="nit_reference" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
            @foreach (\App\Models\TaxObligationType::$nitReferences as $key => $label)
                <option value="{{ $key }}" {{ old('nit_reference', $ob?->nit_reference) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Régimen aplicable <span class="text-red-400">*</span></label>
        <select name="regime" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
            @foreach (\App\Models\TaxObligationType::$regimes as $key => $label)
                <option value="{{ $key }}" {{ old('regime', $ob?->regime) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Clave responsabilidad</label>
        <input type="text" name="responsibility_key" value="{{ old('responsibility_key', $ob?->responsibility_key) }}"
               placeholder="ej: iva, retefuente"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm mono text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Orden</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $ob?->sort_order ?? 0) }}" min="0"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
    </div>
</div>

<label class="flex items-center gap-2 cursor-pointer">
    <input type="checkbox" name="active" value="1" {{ old('active', $ob?->active ?? true) ? 'checked' : '' }}
           class="w-4 h-4 rounded border-gray-300 text-blue-700 focus:ring-blue-400/40">
    <span class="text-sm text-gray-700">Obligación activa</span>
</label>
