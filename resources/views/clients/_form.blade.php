@php
    $client       ??= null;
    $responsible  = old('tax_responsibilities', $client?->tax_responsibilities ?? []);
@endphp

<div
    x-data="{
        docType: '{{ old('document_type', $client?->document_type ?? 'NIT') }}',
        docNumber: '{{ old('document_number', $client?->document_number ?? '') }}',
        dv: '{{ old('dv', $client?->dv ?? '') }}',
        calcDV() {
            if (this.docType !== 'NIT' || !this.docNumber) { this.dv = ''; return; }
            const primes = [3,7,13,17,19,23,29,37,41,43,47,53,59,67,71];
            const digits = this.docNumber.replace(/\D/g,'').split('').reverse();
            let sum = 0;
            digits.forEach((d, i) => { sum += parseInt(d) * (primes[i] ?? 0); });
            const r = sum % 11;
            this.dv = String(r < 2 ? r : 11 - r);
        }
    }"
    class="space-y-6"
>

{{-- ────────────────────────── SECCIÓN 1: Identificación ────────────────────────── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800 text-sm">Identificación</h3>
    </div>
    <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

        {{-- Tipo de persona --}}
        <div class="lg:col-span-3">
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de persona <span class="text-red-500">*</span></label>
            <div class="flex gap-4">
                @foreach(['juridica' => 'Persona Jurídica', 'natural' => 'Persona Natural'] as $val => $lbl)
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="person_type" value="{{ $val }}"
                           {{ old('person_type', $client?->person_type ?? 'juridica') === $val ? 'checked' : '' }}
                           class="text-blue-600 border-gray-300 focus:ring-blue-500" />
                    <span class="text-sm text-gray-700">{{ $lbl }}</span>
                </label>
                @endforeach
            </div>
            @error('person_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Nombre / Razón social --}}
        <div class="lg:col-span-3">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre / Razón social <span class="text-red-500">*</span></label>
            <input id="name" name="name" type="text"
                   value="{{ old('name', $client?->name) }}"
                   placeholder="Nombre completo o razón social"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" required />
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Tipo de documento --}}
        <div>
            <label for="document_type" class="block text-sm font-medium text-gray-700 mb-1">Tipo de documento <span class="text-red-500">*</span></label>
            <select id="document_type" name="document_type" x-model="docType" @change="calcDV()"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach(['NIT' => 'NIT', 'CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería', 'Pasaporte' => 'Pasaporte'] as $val => $lbl)
                <option value="{{ $val }}" {{ old('document_type', $client?->document_type ?? 'NIT') === $val ? 'selected' : '' }}>
                    {{ $lbl }}
                </option>
                @endforeach
            </select>
            @error('document_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Número de documento --}}
        <div>
            <label for="document_number" class="block text-sm font-medium text-gray-700 mb-1">Número de documento <span class="text-red-500">*</span></label>
            <input id="document_number" name="document_number" type="text"
                   x-model="docNumber" @input="calcDV()"
                   placeholder="Sin dígito de verificación"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" required />
            @error('document_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- DV (solo NIT) --}}
        <div x-show="docType === 'NIT'" x-transition>
            <label for="dv" class="block text-sm font-medium text-gray-700 mb-1">
                DV
                <span class="text-xs text-gray-400 font-normal">(se calcula automático)</span>
            </label>
            <input id="dv" name="dv" type="text"
                   x-model="dv"
                   maxlength="1"
                   placeholder="0-9"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-50" readonly />
            @error('dv')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

    </div>
</div>

{{-- ────────────────────────── SECCIÓN 2: Información tributaria ────────────────────────── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800 text-sm">Información tributaria</h3>
    </div>
    <div class="px-6 py-5 space-y-5">

        {{-- Responsabilidades tributarias --}}
        <div class="max-w-xs">
            <label for="tax_regime" class="block text-sm font-medium text-gray-700 mb-1">Responsabilidades tributarias <span class="text-red-500">*</span></label>
            <select id="tax_regime" name="tax_regime"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach(\App\Models\Client::TAX_RESPONSIBILITIES as $val => $lbl)
                <option value="{{ $val }}" {{ old('tax_regime', $client?->tax_regime ?? 'no_aplica') === $val ? 'selected' : '' }}>
                    {{ $lbl }}
                </option>
                @endforeach
            </select>
            @error('tax_regime')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Obligaciones tributarias --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Obligaciones tributarias</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach(\App\Models\Client::TAX_OBLIGATIONS as $key => $label)
                <label class="flex items-start gap-2.5 p-2.5 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 cursor-pointer transition-colors
                              {{ in_array($key, $responsible) ? 'border-blue-300 bg-blue-50' : '' }}">
                    <input type="checkbox" name="tax_responsibilities[]" value="{{ $key }}"
                           {{ in_array($key, $responsible) ? 'checked' : '' }}
                           class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 flex-shrink-0" />
                    <div>
                        <span class="text-sm font-medium text-gray-800">{{ explode(' – ', $label)[0] }}</span>
                        <span class="block text-xs text-gray-500">{{ explode(' – ', $label)[1] ?? '' }}</span>
                    </div>
                </label>
                @endforeach
            </div>
            @error('tax_responsibilities')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

    </div>
</div>

{{-- ────────────────────────── SECCIÓN 3: Contacto ────────────────────────── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800 text-sm">Contacto</h3>
    </div>
    <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-5">

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
            <input id="email" name="email" type="email"
                   value="{{ old('email', $client?->email) }}"
                   placeholder="cliente@empresa.com"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
            <input id="phone" name="phone" type="text"
                   value="{{ old('phone', $client?->phone) }}"
                   placeholder="300 123 4567"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
            <input id="city" name="city" type="text"
                   value="{{ old('city', $client?->city) }}"
                   placeholder="Bogotá D.C."
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
            @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
            <input id="department" name="department" type="text"
                   value="{{ old('department', $client?->department) }}"
                   placeholder="Cundinamarca"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
            @error('department')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
            <input id="address" name="address" type="text"
                   value="{{ old('address', $client?->address) }}"
                   placeholder="Calle, carrera, número..."
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
            @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">
                Persona de contacto
                <span class="text-xs text-gray-400 font-normal">(representante legal, tesorero, etc.)</span>
            </label>
            <input id="contact_person" name="contact_person" type="text"
                   value="{{ old('contact_person', $client?->contact_person) }}"
                   placeholder="Nombre de la persona de contacto"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
            @error('contact_person')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

    </div>
</div>

{{-- ────────────────────────── SECCIÓN 4: Otros ────────────────────────── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800 text-sm">Otros</h3>
    </div>
    <div class="px-6 py-5 space-y-4">

        <div class="max-w-xs">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select id="status" name="status"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="active"   {{ old('status', $client?->status ?? 'active')   === 'active'   ? 'selected' : '' }}>Activo</option>
                <option value="inactive" {{ old('status', $client?->status ?? 'active')   === 'inactive' ? 'selected' : '' }}>Inactivo</option>
            </select>
            @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notas internas</label>
            <textarea id="notes" name="notes" rows="3"
                      placeholder="Observaciones, condiciones especiales, recordatorios..."
                      class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $client?->notes) }}</textarea>
            @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

    </div>
</div>

</div>
