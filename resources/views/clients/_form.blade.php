@php
    $client        ??= null;
    $responsible     = old('tax_responsibilities', $client?->tax_responsibilities ?? []);
    $obligationGroups = \App\Models\TaxObligationType::where('active', true)
        ->orderBy('sort_order')->orderBy('name')
        ->get()
        ->groupBy(fn($ob) => $ob->group_label ?? 'Otros');
    $blockedBySimple = \App\Models\TaxObligationType::where('active', true)
        ->where('regime', 'ordinario')
        ->pluck('code');

    $inputClass = 'w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] text-[var(--text-700)] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
    $labelClass = 'block text-[13px] font-medium text-[var(--text-700)] mb-1';
@endphp

<div x-data="clientForm()" class="space-y-6">

{{-- SECCIÓN 1: Identificación --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)]">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Identificación</h2>
    </div>
    <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

        {{-- Tipo de persona --}}
        <div class="lg:col-span-3">
            <label class="{{ $labelClass }}">Tipo de persona <span class="text-[var(--color-danger)]">*</span></label>
            <div class="flex gap-4">
                @foreach(['juridica' => 'Persona Jurídica', 'natural' => 'Persona Natural'] as $val => $lbl)
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="person_type" value="{{ $val }}" x-model="personType"
                           {{ old('person_type', $client?->person_type ?? 'juridica') === $val ? 'checked' : '' }}
                           class="text-[var(--color-primary)] border-[var(--border-strong)] focus:ring-[var(--color-primary-light)]" />
                    <span class="text-[14px] text-[var(--text-700)]">{{ $lbl }}</span>
                </label>
                @endforeach
            </div>
            @error('person_type')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        {{-- Representante legal (solo persona jurídica) — quien firma los contratos generados por el Motor Documental --}}
        <div class="lg:col-span-3" x-show="personType === 'juridica'" x-transition>
            <div class="border-t border-[var(--border-default)] pt-5">
                <p class="text-[13px] font-semibold text-[var(--text-700)] mb-3">
                    Representante Legal
                    <span class="text-[12px] text-[var(--text-400)] font-normal">(quien firma los contratos en nombre de la empresa)</span>
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="sm:col-span-2 lg:col-span-2">
                        <label for="legal_representative_name" class="{{ $labelClass }}">Nombre completo</label>
                        <input id="legal_representative_name" name="legal_representative_name" type="text"
                               value="{{ old('legal_representative_name', $client?->legal_representative_name) }}"
                               placeholder="Nombre completo del representante legal"
                               class="{{ $inputClass }}" />
                        @error('legal_representative_name')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="legal_representative_document_type" class="{{ $labelClass }}">Tipo de documento</label>
                        <select id="legal_representative_document_type" name="legal_representative_document_type" class="{{ $inputClass }}">
                            @foreach(['CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería', 'Pasaporte' => 'Pasaporte'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('legal_representative_document_type', $client?->legal_representative_document_type ?? 'CC') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        @error('legal_representative_document_type')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="legal_representative_document_number" class="{{ $labelClass }}">Número de documento</label>
                        <input id="legal_representative_document_number" name="legal_representative_document_number" type="text"
                               value="{{ old('legal_representative_document_number', $client?->legal_representative_document_number) }}"
                               class="{{ $inputClass }}" />
                        @error('legal_representative_document_number')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2 lg:col-span-4">
                        <label for="chamber_of_commerce_city" class="{{ $labelClass }}">
                            Cámara de Comercio
                            <span class="text-[12px] text-[var(--text-400)] font-normal">(ciudad de registro mercantil — si se deja vacío, se usa la ciudad del cliente)</span>
                        </label>
                        <input id="chamber_of_commerce_city" name="chamber_of_commerce_city" type="text"
                               value="{{ old('chamber_of_commerce_city', $client?->chamber_of_commerce_city) }}"
                               placeholder="Bogotá"
                               class="{{ $inputClass }} max-w-xs" />
                        @error('chamber_of_commerce_city')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Nombre / Razón social --}}
        <div class="lg:col-span-3">
            <label for="name" class="{{ $labelClass }}">Nombre / Razón social <span class="text-[var(--color-danger)]">*</span></label>
            <input id="name" name="name" type="text"
                   value="{{ old('name', $client?->name) }}"
                   placeholder="Nombre completo o razón social"
                   class="{{ $inputClass }}" required />
            @error('name')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        {{-- Tipo de documento --}}
        <div>
            <label for="document_type" class="{{ $labelClass }}">Tipo de documento <span class="text-[var(--color-danger)]">*</span></label>
            <select id="document_type" name="document_type" x-model="docType" @change="calcDV()"
                    class="{{ $inputClass }}">
                @foreach(['NIT' => 'NIT', 'CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería', 'Pasaporte' => 'Pasaporte'] as $val => $lbl)
                <option value="{{ $val }}" {{ old('document_type', $client?->document_type ?? 'NIT') === $val ? 'selected' : '' }}>
                    {{ $lbl }}
                </option>
                @endforeach
            </select>
            @error('document_type')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        {{-- Número de documento --}}
        <div>
            <label for="document_number" class="{{ $labelClass }}">Número de documento <span class="text-[var(--color-danger)]">*</span></label>
            <input id="document_number" name="document_number" type="text"
                   x-model="docNumber" @input="calcDV()"
                   placeholder="Sin dígito de verificación"
                   class="{{ $inputClass }}" required />
            @error('document_number')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        {{-- DV (solo NIT) --}}
        <div x-show="docType === 'NIT'" x-transition>
            <label for="dv" class="{{ $labelClass }}">
                DV
                <span class="text-[12px] text-[var(--text-400)] font-normal">(se calcula automático)</span>
            </label>
            <input id="dv" name="dv" type="text"
                   x-model="dv"
                   maxlength="1"
                   placeholder="0-9"
                   class="{{ $inputClass }} bg-[var(--surface-muted)]" readonly />
            @error('dv')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

    </div>
</div>

{{-- SECCIÓN 2: Información tributaria --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)]">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Información tributaria</h2>
    </div>
    <div class="px-6 py-5 space-y-5">

        {{-- Responsabilidades tributarias --}}
        <div class="max-w-xs">
            <label for="tax_regime" class="{{ $labelClass }}">Responsabilidades tributarias <span class="text-[var(--color-danger)]">*</span></label>
            <select id="tax_regime" name="tax_regime" class="{{ $inputClass }}">
                @foreach(\App\Models\Client::TAX_RESPONSIBILITIES as $val => $lbl)
                <option value="{{ $val }}" {{ old('tax_regime', $client?->tax_regime ?? 'no_aplica') === $val ? 'selected' : '' }}>
                    {{ $lbl }}
                </option>
                @endforeach
            </select>
            @error('tax_regime')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        {{-- Obligaciones tributarias --}}
        <div>
            <label class="block text-[13px] font-medium text-[var(--text-700)] mb-2">
                Obligaciones tributarias
                <span class="text-[12px] text-[var(--text-400)] font-normal">(vencimientos administrados en el panel admin)</span>
            </label>
            <div class="space-y-4">
                @foreach($obligationGroups as $group => $obs)
                <div>
                    <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-1.5">{{ $group }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($obs as $ob)
                        <label
                            :class="isBlocked('{{ $ob->code }}')
                                ? 'opacity-40 cursor-not-allowed'
                                : 'cursor-pointer hover:border-[var(--color-primary)] hover:bg-[var(--color-primary-light)] {{ in_array($ob->code, $responsible) ? 'border-[var(--color-primary)] bg-[var(--color-primary-light)]' : '' }}'"
                            class="flex items-start gap-2.5 p-2.5 rounded-[var(--radius-control)] border border-[var(--border-default)]">
                            <input type="checkbox" name="tax_responsibilities[]" value="{{ $ob->code }}"
                                   {{ in_array($ob->code, $responsible) ? 'checked' : '' }}
                                   :disabled="isBlocked('{{ $ob->code }}')"
                                   @if(str_starts_with($ob->code, 'SIMPLE_')) @change="toggleSimple()" @endif
                                   class="mt-0.5 rounded border-[var(--border-strong)] text-[var(--color-primary)] focus:ring-[var(--color-primary-light)] flex-shrink-0" />
                            <div class="min-w-0">
                                <span class="text-[14px] font-medium text-[var(--text-700)]">{{ $ob->name }}</span>
                                @if($ob->description)
                                <span class="block text-[12px] text-[var(--text-500)]">{{ $ob->description }}</span>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            <p x-show="simpleOn" x-cloak class="mt-2 text-[12px] text-[var(--color-warning)] font-medium">
                El Régimen SIMPLE bloquea las obligaciones exclusivas del régimen ordinario (Renta, Retefuente, ICA, etc.).
            </p>
            @error('tax_responsibilities')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

    </div>
</div>

{{-- SECCIÓN 3: Contacto --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)]">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Contacto</h2>
    </div>
    <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-5">

        <div>
            <label for="email" class="{{ $labelClass }}">Correo electrónico</label>
            <input id="email" name="email" type="email"
                   value="{{ old('email', $client?->email) }}"
                   placeholder="cliente@empresa.com"
                   class="{{ $inputClass }}" />
            @error('email')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="{{ $labelClass }}">Teléfono</label>
            <input id="phone" name="phone" type="text"
                   value="{{ old('phone', $client?->phone) }}"
                   placeholder="300 123 4567"
                   class="{{ $inputClass }}" />
            @error('phone')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="city" class="{{ $labelClass }}">Ciudad</label>
            <input id="city" name="city" type="text"
                   value="{{ old('city', $client?->city) }}"
                   placeholder="Bogotá D.C."
                   class="{{ $inputClass }}" />
            @error('city')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="department" class="{{ $labelClass }}">Departamento</label>
            <input id="department" name="department" type="text"
                   value="{{ old('department', $client?->department) }}"
                   placeholder="Cundinamarca"
                   class="{{ $inputClass }}" />
            @error('department')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <label for="address" class="{{ $labelClass }}">Dirección</label>
            <input id="address" name="address" type="text"
                   value="{{ old('address', $client?->address) }}"
                   placeholder="Calle, carrera, número..."
                   class="{{ $inputClass }}" />
            @error('address')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <label for="contact_person" class="{{ $labelClass }}">
                Persona de contacto
                <span class="text-[12px] text-[var(--text-400)] font-normal">(contador interno, asistente, tesorero, etc.)</span>
            </label>
            <input id="contact_person" name="contact_person" type="text"
                   value="{{ old('contact_person', $client?->contact_person) }}"
                   placeholder="Nombre de la persona de contacto"
                   class="{{ $inputClass }}" />
            @error('contact_person')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

    </div>
</div>

{{-- SECCIÓN 4: Numeración de cuentas de cobro --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)]">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Numeración de cuentas de cobro</h2>
    </div>
    <div class="px-6 py-5 space-y-4">

        {{-- Preview del próximo número --}}
        <div class="flex items-center gap-3 p-3.5 bg-[var(--color-primary-light)] border border-[var(--border-default)] rounded-[var(--radius-control)]">
            <x-lucide-file-text class="w-4 h-4 text-[var(--color-primary)] flex-shrink-0" />
            <div>
                <p class="text-[12px] text-[var(--color-primary)] font-medium">Próxima cuenta de cobro para este cliente</p>
                <p class="text-[14px] font-bold font-mono text-[var(--color-primary-dark)] mt-0.5"
                   x-text="invPreview"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

            {{-- Prefijo --}}
            <div>
                <label for="invoice_prefix" class="{{ $labelClass }}">
                    Prefijo
                    <span class="text-[12px] text-[var(--text-400)] font-normal ml-1">ej: CC, FAC, CONS</span>
                </label>
                <input id="invoice_prefix" name="invoice_prefix" type="text"
                       x-model="invPrefix"
                       @input="invPrefix = $event.target.value.toUpperCase()"
                       value="{{ old('invoice_prefix', $client?->invoice_prefix) }}"
                       maxlength="20"
                       placeholder="CC"
                       class="{{ $inputClass }} font-mono uppercase tracking-widest" />
                @error('invoice_prefix')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
            </div>

            {{-- Consecutivo actual --}}
            <div>
                <label for="invoice_consecutive" class="{{ $labelClass }}">
                    Consecutivo actual
                    <span class="text-[12px] text-[var(--text-400)] font-normal ml-1">la próxima será este + 1</span>
                </label>
                <input id="invoice_consecutive" name="invoice_consecutive" type="number"
                       x-model.number="invConsecutive"
                       value="{{ old('invoice_consecutive', $client?->invoice_consecutive ?? 0) }}"
                       min="0"
                       class="{{ $inputClass }}" />
                @error('invoice_consecutive')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                @if($client && $client->invoice_consecutive > 0)
                <p class="mt-1 text-[12px] text-[var(--color-warning)]">
                    Modificar este valor cambia la numeración futura de este cliente.
                </p>
                @endif
            </div>

        </div>
    </div>
</div>

{{-- SECCIÓN 4b: Configuración de nómina --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)]">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Configuración de nómina</h2>
    </div>
    <div class="px-6 py-5 space-y-4">

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label for="payroll_periodicity" class="{{ $labelClass }}">Periodicidad de pago</label>
                <select id="payroll_periodicity" name="payroll_periodicity" class="{{ $inputClass }}">
                    <option value="">Sin configurar</option>
                    <option value="mensual" {{ old('payroll_periodicity', $client?->payroll_periodicity) === 'mensual' ? 'selected' : '' }}>Mensual</option>
                    <option value="quincenal" {{ old('payroll_periodicity', $client?->payroll_periodicity) === 'quincenal' ? 'selected' : '' }}>Quincenal</option>
                </select>
                @error('payroll_periodicity')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="payroll_prefix" class="{{ $labelClass }}">
                    Prefijo de nómina
                    <span class="text-[12px] text-[var(--text-400)] font-normal ml-1">ej: NOM</span>
                </label>
                <input id="payroll_prefix" name="payroll_prefix" type="text"
                       value="{{ old('payroll_prefix', $client?->payroll_prefix) }}"
                       maxlength="20" placeholder="NOM"
                       class="{{ $inputClass }} font-mono uppercase tracking-widest" />
                @error('payroll_prefix')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="payroll_consecutive" class="{{ $labelClass }}">
                    Consecutivo actual
                    <span class="text-[12px] text-[var(--text-400)] font-normal ml-1">el próximo será este + 1</span>
                </label>
                <input id="payroll_consecutive" name="payroll_consecutive" type="number"
                       value="{{ old('payroll_consecutive', $client?->payroll_consecutive ?? 0) }}"
                       min="0" class="{{ $inputClass }}" />
                @error('payroll_consecutive')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
            </div>
        </div>

        <label class="flex items-start gap-2.5 cursor-pointer">
            <input type="checkbox" name="payroll_pila_exempt" value="1"
                   {{ old('payroll_pila_exempt', $client?->payroll_pila_exempt) ? 'checked' : '' }}
                   class="mt-0.5 rounded border-[var(--border-strong)] text-[var(--color-primary)] focus:ring-[var(--color-primary-light)]" />
            <span class="text-[14px] text-[var(--text-700)]">
                Empleador exonerado de aportes a salud, Sena e ICBF
                <span class="block text-[12px] text-[var(--text-400)] font-normal">Artículo 114-1 del Estatuto Tributario</span>
            </span>
        </label>
    </div>
</div>

{{-- SECCIÓN 5: Otros --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)]">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Otros</h2>
    </div>
    <div class="px-6 py-5 space-y-4">

        <div class="max-w-xs">
            <label for="status" class="{{ $labelClass }}">Estado</label>
            <select id="status" name="status" class="{{ $inputClass }}">
                <option value="active"   {{ old('status', $client?->status ?? 'active')   === 'active'   ? 'selected' : '' }}>Activo</option>
                <option value="inactive" {{ old('status', $client?->status ?? 'active')   === 'inactive' ? 'selected' : '' }}>Inactivo</option>
            </select>
            @error('status')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="notes" class="{{ $labelClass }}">Notas internas</label>
            <textarea id="notes" name="notes" rows="3"
                      placeholder="Observaciones, condiciones especiales, recordatorios..."
                      class="w-full border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 py-2.5 text-[14px] text-[var(--text-700)] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">{{ old('notes', $client?->notes) }}</textarea>
            @error('notes')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

    </div>
</div>

</div>

<script>
function clientForm() {
    return {
        personType:     '{{ old('person_type', $client?->person_type ?? 'juridica') }}',
        docType:        '{{ old('document_type', $client?->document_type ?? 'NIT') }}',
        docNumber:      '{{ old('document_number', $client?->document_number ?? '') }}',
        dv:             '{{ old('dv', $client?->dv ?? '') }}',
        invPrefix:      '{{ strtoupper(old('invoice_prefix', $client?->invoice_prefix ?? '')) }}',
        invConsecutive: {{ (int) old('invoice_consecutive', $client?->invoice_consecutive ?? 0) }},
        simpleOn:       {{ collect($responsible)->contains(fn($c) => str_starts_with($c, 'SIMPLE_')) ? 'true' : 'false' }},
        blocked:        @json($blockedBySimple),

        get invPreview() {
            const num = String((parseInt(this.invConsecutive) || 0) + 1).padStart(4, '0');
            const prefix = this.invPrefix.trim().toUpperCase();
            return prefix ? prefix + '-' + num : num;
        },

        calcDV() {
            if (this.docType !== 'NIT' || !this.docNumber) { this.dv = ''; return; }
            const primes = [3,7,13,17,19,23,29,37,41,43,47,53,59,67,71];
            const digits = this.docNumber.replace(/\D/g,'').split('').reverse();
            let sum = 0;
            digits.forEach((d, i) => { sum += parseInt(d) * (primes[i] ?? 0); });
            const r = sum % 11;
            this.dv = String(r < 2 ? r : 11 - r);
        },

        toggleSimple() {
            this.$nextTick(() => {
                this.simpleOn = Array.from(
                    document.querySelectorAll('input[name="tax_responsibilities[]"]')
                ).some(el => el.checked && el.value.startsWith('SIMPLE_'));

                if (this.simpleOn) {
                    this.blocked.forEach(code => {
                        const el = document.querySelector(
                            'input[name="tax_responsibilities[]"][value="' + code + '"]'
                        );
                        if (el) el.checked = false;
                    });
                }
            });
        },

        isBlocked(code) {
            return this.simpleOn && this.blocked.includes(code);
        },
    };
}
</script>
