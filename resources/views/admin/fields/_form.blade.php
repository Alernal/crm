@php $ob = $ob ?? null; @endphp

{{-- Módulo + Tipo --}}
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Módulo <span class="text-red-400">*</span></label>
        <select name="module_key" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
            <option value="">Selecciona módulo…</option>
            @foreach ($modules as $mod)
                <option value="{{ $mod->key }}" {{ old('module_key', $ob?->module_key) === $mod->key ? 'selected' : '' }}>{{ $mod->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Tipo de dato <span class="text-red-400">*</span></label>
        <select name="type" x-model="type" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" {{ old('type', $ob?->type) === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- Key + Etiqueta --}}
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">
            Nombre clave <span class="text-red-400">*</span>
            <span class="text-gray-400 font-normal ml-1">snake_case</span>
        </label>
        <input type="text" name="name" value="{{ old('name', $ob?->name) }}" required
               pattern="[a-zA-Z0-9_\-]+" placeholder="ej: tipo_contrato"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm mono text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Etiqueta visible <span class="text-red-400">*</span></label>
        <input type="text" name="label" value="{{ old('label', $ob?->label) }}" required
               placeholder="ej: Tipo de contrato"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
    </div>
</div>

{{-- Orden + Defecto + Flags --}}
<div class="grid grid-cols-3 gap-4">
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Orden</label>
        <input type="number" name="order" value="{{ old('order', $ob?->order ?? 0) }}" min="0"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Valor por defecto</label>
        <input type="text" name="default_value" value="{{ old('default_value', $ob?->default_value) }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
    </div>
    <div class="flex flex-col justify-end gap-2.5 pb-0.5">
        <label class="flex items-center gap-2 cursor-pointer group">
            <input type="checkbox" name="required" value="1" {{ old('required', $ob?->required) ? 'checked' : '' }}
                   class="w-4 h-4 rounded border-gray-300 text-blue-700 focus:ring-blue-400/40">
            <span class="text-sm text-gray-700 group-hover:text-gray-900">Obligatorio</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer group">
            <input type="checkbox" name="visible" value="1" {{ old('visible', $ob?->visible ?? true) ? 'checked' : '' }}
                   class="w-4 h-4 rounded border-gray-300 text-blue-700 focus:ring-blue-400/40">
            <span class="text-sm text-gray-700 group-hover:text-gray-900">Visible</span>
        </label>
    </div>
</div>

{{-- Validaciones --}}
<div class="border border-gray-200 bg-gray-50/60 rounded-xl p-4">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Reglas de validación (opcional)</p>

    <div class="grid grid-cols-2 gap-3" x-show="['number','text','textarea'].includes(type)">
        @php $rules = $ob?->validation_rules ?? []; @endphp
        <div>
            <label class="block text-xs text-gray-400 mb-1.5">Mínimo</label>
            <input type="number" name="validation_rules[min]" value="{{ old('validation_rules.min', $rules['min'] ?? '') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1.5">Máximo</label>
            <input type="number" name="validation_rules[max]" value="{{ old('validation_rules.max', $rules['max'] ?? '') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
        </div>
    </div>

    <div class="mt-3" x-show="type === 'text'">
        <label class="block text-xs text-gray-400 mb-1.5">Regex</label>
        <input type="text" name="validation_rules[regex]" value="{{ old('validation_rules.regex', $rules['regex'] ?? '') }}"
               placeholder="^[A-Z]{2}\d{4}$"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mono bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
    </div>

    <div class="mt-3" x-show="type === 'select'">
        <label class="block text-xs text-gray-400 mb-1.5">Opciones (una por línea)</label>
        <textarea name="validation_rules[options]" rows="4" placeholder="Opción 1&#10;Opción 2"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">{{ old('validation_rules.options', isset($rules['options']) ? implode("\n", (array)$rules['options']) : '') }}</textarea>
    </div>

    <p x-show="!['number','text','textarea','select'].includes(type)" class="text-xs text-gray-400 py-1">
        No hay reglas aplicables para este tipo de campo.
    </p>
</div>
