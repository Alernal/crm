@php
    $employee ??= null;
    $clients ??= collect();
    $selectedClientId ??= null;

    $inputClass = 'w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] text-[var(--text-700)] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
    $labelClass = 'block text-[13px] font-medium text-[var(--text-700)] mb-1';
@endphp

<div x-data="employeeForm()" class="space-y-6">

{{-- SECCIÓN 1: Datos personales --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)] flex items-center gap-2">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Datos personales</h2>
        <x-help-icon title="Datos personales">
            Identificación básica del trabajador. El cliente (empresa) determina a qué nómina pertenece — es obligatorio y no se puede cambiar después sin afectar sus períodos ya generados.
        </x-help-icon>
    </div>
    <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

        <div class="lg:col-span-3">
            <label for="client_id" class="{{ $labelClass }}">Cliente (empresa) <span class="text-[var(--color-danger)]">*</span></label>
            <select id="client_id" name="client_id" class="{{ $inputClass }} max-w-md" required>
                <option value="">Selecciona un cliente...</option>
                @foreach($clients as $c)
                <option value="{{ $c->id }}" {{ (string) old('client_id', $employee?->client_id ?? $selectedClientId) === (string) $c->id ? 'selected' : '' }}>
                    {{ $c->name }}
                </option>
                @endforeach
            </select>
            @error('client_id')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="first_name" class="{{ $labelClass }}">Nombres <span class="text-[var(--color-danger)]">*</span></label>
            <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $employee?->first_name) }}" class="{{ $inputClass }}" required />
            @error('first_name')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="last_name" class="{{ $labelClass }}">Apellidos <span class="text-[var(--color-danger)]">*</span></label>
            <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $employee?->last_name) }}" class="{{ $inputClass }}" required />
            @error('last_name')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="document_type" class="{{ $labelClass }}">Documento</label>
                <select id="document_type" name="document_type" class="{{ $inputClass }}">
                    @foreach(['CC' => 'C.C.', 'CE' => 'C.E.', 'Pasaporte' => 'Pasaporte', 'PEP' => 'PEP'] as $val => $lbl)
                    <option value="{{ $val }}" {{ old('document_type', $employee?->document_type ?? 'CC') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="document_number" class="{{ $labelClass }}">Número</label>
                <input id="document_number" name="document_number" type="text" value="{{ old('document_number', $employee?->document_number) }}" class="{{ $inputClass }}" required />
            </div>
            @error('document_number')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="{{ $labelClass }}">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email', $employee?->email) }}" placeholder="empleado@correo.com" class="{{ $inputClass }}" />
            @error('email')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="{{ $labelClass }}">Teléfono</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $employee?->phone) }}" placeholder="300 123 4567" class="{{ $inputClass }}" />
            @error('phone')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div class="lg:col-span-3">
            <label for="address" class="{{ $labelClass }}">Dirección</label>
            <input id="address" name="address" type="text" value="{{ old('address', $employee?->address) }}" class="{{ $inputClass }}" />
            @error('address')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

    </div>
</div>

{{-- SECCIÓN 2: Datos laborales --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)] flex items-center gap-2">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Datos laborales</h2>
        <x-help-icon title="Datos laborales">
            El tipo de contrato afecta cómo se liquida una eventual indemnización al terminar el contrato. La jornada semanal solo se diligencia si es distinta a la legal vigente (ej. medio tiempo) — vacía, se usa la jornada legal del período.
        </x-help-icon>
    </div>
    <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

        <div>
            <label for="position" class="{{ $labelClass }}">Cargo</label>
            <input id="position" name="position" type="text" value="{{ old('position', $employee?->position) }}" class="{{ $inputClass }}" />
        </div>

        <div>
            <label for="contract_type" class="{{ $labelClass }}">Tipo de contrato</label>
            <select id="contract_type" name="contract_type" class="{{ $inputClass }}">
                @foreach(\App\Models\Employee::CONTRACT_TYPES as $val => $lbl)
                <option value="{{ $val }}" {{ old('contract_type', $employee?->contract_type ?? 'indefinido') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="hire_date" class="{{ $labelClass }}">Fecha de ingreso <span class="text-[var(--color-danger)]">*</span></label>
            <input id="hire_date" name="hire_date" type="date" value="{{ old('hire_date', $employee?->hire_date?->format('Y-m-d')) }}" class="{{ $inputClass }}" required />
            @error('hire_date')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="salary_type" class="{{ $labelClass }}">Tipo de salario</label>
            <select id="salary_type" name="salary_type" class="{{ $inputClass }}">
                <option value="fijo" {{ old('salary_type', $employee?->salary_type ?? 'fijo') === 'fijo' ? 'selected' : '' }}>Fijo</option>
                <option value="variable" {{ old('salary_type', $employee?->salary_type ?? 'fijo') === 'variable' ? 'selected' : '' }}>Variable</option>
            </select>
        </div>

        <div x-data="{ baseSalary: {{ old('base_salary', $employee?->base_salary ?? 'null') }} }">
            <label for="base_salary" class="{{ $labelClass }}">Salario básico mensual <span class="text-[var(--color-danger)]">*</span></label>
            <input id="base_salary" name="base_salary" type="text" x-money="baseSalary"
                   class="{{ $inputClass }}" required />
            @error('base_salary')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="weekly_hours_override" class="{{ $labelClass }}">
                Jornada semanal (horas)
                <span class="text-[12px] text-[var(--text-400)] font-normal">opcional, medio tiempo</span>
            </label>
            <input id="weekly_hours_override" name="weekly_hours_override" type="number" step="0.5" min="0" max="60"
                   value="{{ old('weekly_hours_override', $employee?->weekly_hours_override) }}"
                   placeholder="Vacío = jornada legal vigente" class="{{ $inputClass }}" />
            @error('weekly_hours_override')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

    </div>
</div>

{{-- SECCIÓN 3: Auxilio de transporte --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)] flex items-center gap-2">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Auxilio de transporte</h2>
        <x-help-icon title="Auxilio de transporte">
            Deja "Automático" en la mayoría de los casos — el sistema decide cada período si aplica según el salario devengado (tope de 2 SMLV). Usa "Siempre" o "Nunca" solo para excepciones puntuales, como la exoneración por vivienda en las instalaciones de la empresa (CST art. 132).
        </x-help-icon>
    </div>
    <div class="px-6 py-5 space-y-4">
        <div class="max-w-sm">
            <label for="transport_allowance_mode" class="{{ $labelClass }}">¿Cuándo otorgarlo?</label>
            <select id="transport_allowance_mode" name="transport_allowance_mode" x-model="transportMode" class="{{ $inputClass }}">
                @foreach(\App\Models\Employee::TRANSPORT_ALLOWANCE_MODES as $val => $lbl)
                <option value="{{ $val }}" {{ old('transport_allowance_mode', $employee?->transport_allowance_mode ?? 'automatico') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
            <p class="mt-1.5 text-[12px] text-[var(--text-400)]">
                Automático: se otorga si el devengado (sin horas extra) no supera 2 SMLV, salvo que el empleado viva en las instalaciones de la empresa (CST art. 132).
            </p>
        </div>
        <div x-show="transportMode === 'nunca'" x-transition>
            <label for="transport_allowance_note" class="{{ $labelClass }}">Motivo</label>
            <input id="transport_allowance_note" name="transport_allowance_note" type="text"
                   value="{{ old('transport_allowance_note', $employee?->transport_allowance_note) }}"
                   placeholder="Ej: vive en las instalaciones de la empresa" class="{{ $inputClass }} max-w-md" />
        </div>
    </div>
</div>

{{-- SECCIÓN 4: Seguridad social --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)] flex items-center gap-2">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Seguridad social</h2>
        <x-help-icon title="Seguridad social">
            El nivel de riesgo ARL define la tarifa de aporte patronal en cada liquidación. Marca "No cotiza a pensión" solo si el trabajador ya está pensionado por vejez y sigue laborando — en ese caso no se le liquida descuento de pensión, aporte patronal ni fondo de solidaridad.
        </x-help-icon>
    </div>
    <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        <div>
            <label for="eps" class="{{ $labelClass }}">EPS</label>
            <input id="eps" name="eps" type="text" value="{{ old('eps', $employee?->eps) }}" class="{{ $inputClass }}" />
        </div>

        <div>
            <label for="afp" class="{{ $labelClass }}">Fondo de pensiones</label>
            <input id="afp" name="afp" type="text" value="{{ old('afp', $employee?->afp) }}" class="{{ $inputClass }}" />
        </div>

        <div>
            <label for="ccf" class="{{ $labelClass }}">Caja de compensación</label>
            <input id="ccf" name="ccf" type="text" value="{{ old('ccf', $employee?->ccf) }}" class="{{ $inputClass }}" />
        </div>

        <div>
            <label for="arl_risk_level" class="{{ $labelClass }}">Nivel de riesgo ARL</label>
            <select id="arl_risk_level" name="arl_risk_level" class="{{ $inputClass }}">
                @foreach(\App\Models\Employee::ARL_RISK_LEVELS as $val => $lbl)
                <option value="{{ $val }}" {{ old('arl_risk_level', $employee?->arl_risk_level ?? 'I') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-4 flex items-start gap-2.5 pt-1">
            <input id="pension_exempt" name="pension_exempt" type="checkbox" value="1"
                   {{ old('pension_exempt', $employee?->pension_exempt) ? 'checked' : '' }}
                   class="mt-0.5 h-4 w-4 rounded border-[var(--border-default)] text-[var(--color-primary)] focus:ring-[var(--color-primary-light)]" />
            <label for="pension_exempt" class="text-[13px] text-[var(--text-700)]">
                No cotiza a pensión
                <span class="block text-[12px] text-[var(--text-400)] font-normal">Ej: ya está pensionado por vejez y sigue trabajando. No se le liquidará descuento de pensión, ni aporte patronal, ni fondo de solidaridad pensional.</span>
            </label>
        </div>

    </div>
</div>

{{-- SECCIÓN 5: Datos bancarios --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)] flex items-center gap-2">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Datos bancarios</h2>
        <x-help-icon title="Datos bancarios">
            Informativos — se muestran en el desprendible de pago como referencia, pero el sistema no procesa pagos ni transferencias reales.
        </x-help-icon>
    </div>
    <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

        <div>
            <label for="bank_name" class="{{ $labelClass }}">Banco</label>
            <input id="bank_name" name="bank_name" type="text" value="{{ old('bank_name', $employee?->bank_name) }}" class="{{ $inputClass }}" />
        </div>

        <div>
            <label for="account_type" class="{{ $labelClass }}">Tipo de cuenta</label>
            <select id="account_type" name="account_type" class="{{ $inputClass }}">
                <option value="">Selecciona...</option>
                <option value="Ahorros" {{ old('account_type', $employee?->account_type) === 'Ahorros' ? 'selected' : '' }}>Ahorros</option>
                <option value="Corriente" {{ old('account_type', $employee?->account_type) === 'Corriente' ? 'selected' : '' }}>Corriente</option>
            </select>
        </div>

        <div>
            <label for="account_number" class="{{ $labelClass }}">Número de cuenta</label>
            <input id="account_number" name="account_number" type="text" value="{{ old('account_number', $employee?->account_number) }}" class="{{ $inputClass }}" />
        </div>

    </div>
</div>

{{-- SECCIÓN 6: Novedad de retiro --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)] flex items-center gap-2">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Novedad de retiro</h2>
        <x-help-icon title="Novedad de retiro">
            Diligencia esto solo cuando el trabajador se retire. Es un requisito para poder generar su Liquidación de Contrato después — el motivo determina si hay lugar a indemnización.
        </x-help-icon>
    </div>
    <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-5">

        <div>
            <label for="termination_reason" class="{{ $labelClass }}">Motivo de retiro</label>
            <select id="termination_reason" name="termination_reason" x-model="terminationReason" class="{{ $inputClass }}">
                <option value="">Ninguna (empleado activo)</option>
                @foreach(\App\Models\Employee::TERMINATION_REASONS as $val => $lbl)
                <option value="{{ $val }}" {{ old('termination_reason', $employee?->termination_reason) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
            @error('termination_reason')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div x-show="terminationReason !== ''" x-transition>
            <label for="termination_date" class="{{ $labelClass }}">Fecha de retiro</label>
            <input id="termination_date" name="termination_date" type="date"
                   value="{{ old('termination_date', $employee?->termination_date?->format('Y-m-d')) }}"
                   :disabled="terminationReason === ''" class="{{ $inputClass }}" />
            @error('termination_date')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <p class="text-[12px] text-[var(--text-400)]">
                A partir de esta fecha el trabajador no se incluirá (o se incluirá prorrateado, si la fecha cae dentro del período) en las nóminas que generes para este cliente. Los períodos ya generados no se modifican.
            </p>
        </div>

    </div>
</div>

{{-- SECCIÓN 7: Otros --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 py-4 border-b border-[var(--border-default)] flex items-center gap-2">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Otros</h2>
        <x-help-icon title="Otros">
            Un empleado "Inactivo" no aparece como opción al generar nuevos períodos de nómina, pero conserva todo su historial.
        </x-help-icon>
    </div>
    <div class="px-6 py-5">
        <div class="max-w-xs">
            <label for="status" class="{{ $labelClass }}">Estado</label>
            <select id="status" name="status" class="{{ $inputClass }}">
                <option value="active" {{ old('status', $employee?->status ?? 'active') === 'active' ? 'selected' : '' }}>Activo</option>
                <option value="inactive" {{ old('status', $employee?->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>
    </div>
</div>

</div>

<script>
function employeeForm() {
    return {
        transportMode: '{{ old('transport_allowance_mode', $employee?->transport_allowance_mode ?? 'automatico') }}',
        terminationReason: '{{ old('termination_reason', $employee?->termination_reason ?? '') }}',
    };
}
</script>
