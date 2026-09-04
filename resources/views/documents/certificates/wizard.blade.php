<x-app-layout>
<x-slot name="title">Nuevo Certificado de Ingresos</x-slot>

@php
    $inputClass = 'w-full bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 h-10 text-[14px] text-[var(--text-700)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]';
    $textareaClass = 'w-full bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 py-2.5 text-[14px] text-[var(--text-700)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] resize-none';
    $labelClass = 'block text-[13px] font-medium text-[var(--text-700)] mb-1.5';

    $clientsData = $clients->mapWithKeys(fn ($c) => [$c->id => [
        'name' => $c->name,
        'document' => $c->document_type.' '.$c->document_number.($c->dv ? '-'.$c->dv : ''),
        'address' => $c->address,
        'city' => $c->city,
        'email' => $c->email,
        'phone' => $c->phone,
    ]]);

    $company = auth()->user();
@endphp

<a href="{{ route('documents.certificates.index') }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-5">
    <x-lucide-arrow-left class="w-4 h-4" />
    Cancelar
</a>

@if($template === null)
<div class="bg-[var(--color-warning-bg)] border border-[var(--color-warning)]/20 text-[var(--color-warning-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    No hay una plantilla activa de "Certificado de Ingresos". Contacta al administrador o revisa Documentos › Plantillas.
</div>
@elseif($clients->isEmpty())
<div class="bg-[var(--color-warning-bg)] border border-[var(--color-warning)]/20 text-[var(--color-warning-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    No tienes clientes persona natural activos — los certificados de ingresos solo se emiten para personas naturales.
</div>
@else

<form method="POST" action="{{ route('documents.certificates.generate') }}"
      x-data="certificateWizard(@js($clientsData))">
@csrf
<input type="hidden" name="client_id" :value="clientId">

@if($errors->any())
<div class="mb-4 bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 rounded-[var(--radius-control)] px-4 py-3 text-[14px] text-[var(--color-danger-text)]">
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

{{-- Indicador de pasos --}}
<div class="flex items-center gap-2 mb-5">
    <template x-for="n in 2" :key="n">
        <div class="flex items-center gap-2 flex-1">
            <div class="w-7 h-7 rounded-full flex items-center justify-center text-[12px] font-semibold flex-shrink-0"
                 :class="step >= n ? 'bg-[var(--color-primary)] text-white' : 'bg-[var(--surface-muted)] text-[var(--text-400)]'"
                 x-text="n"></div>
            <div class="h-[2px] flex-1" :class="step > n ? 'bg-[var(--color-primary)]' : 'bg-[var(--surface-muted)]'" x-show="n < 2"></div>
        </div>
    </template>
</div>

<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] overflow-hidden mb-4">

    {{-- Paso 1: Cliente + datos del contador (no hay nada que "elegir" aquí — siempre eres tú,
         datos de solo lectura de tu propio perfil; fusionado con el paso de cliente para no gastar
         un paso completo del wizard en una pantalla sin ninguna decisión real) --}}
    <template x-if="step === 1">
        <div class="p-6 space-y-6">
            <div>
                <h2 class="text-[16px] font-bold text-[var(--text-900)] mb-1">Paso 1 — Cliente y tus datos</h2>
                <p class="text-[13px] text-[var(--text-500)] mb-4">Solo aparecen clientes persona natural — los certificados de ingresos no se emiten para personas jurídicas.</p>

                <label class="{{ $labelClass }}">Cliente <span class="text-[var(--color-danger)]">*</span></label>
                <select x-model="clientId" required class="{{ $inputClass }}">
                    <option value="">Seleccionar…</option>
                    @foreach($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>

                <template x-if="selectedClient()">
                    <div class="mt-4 bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] p-4 text-[13px] text-[var(--text-700)] space-y-1">
                        <p><span class="text-[var(--text-400)]">Nombre:</span> <span x-text="selectedClient().name"></span></p>
                        <p><span class="text-[var(--text-400)]">Identificación:</span> <span x-text="selectedClient().document"></span></p>
                        <p><span class="text-[var(--text-400)]">Dirección:</span> <span x-text="selectedClient().address || '—'"></span> · <span x-text="selectedClient().city || '—'"></span></p>
                        <p><span class="text-[var(--text-400)]">Contacto:</span> <span x-text="selectedClient().email || '—'"></span> · <span x-text="selectedClient().phone || '—'"></span></p>
                    </div>
                </template>
            </div>

            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-1">Tus datos como contador</h3>
                <p class="text-[12px] text-[var(--text-400)] mb-3">Cargados automáticamente desde tu perfil — no necesitas escribir nada aquí.</p>

                <div class="bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] p-4 text-[13px] text-[var(--text-700)] space-y-1">
                    <p><span class="text-[var(--text-400)]">Nombre:</span> {{ $company->name }}</p>
                    <p><span class="text-[var(--text-400)]">{{ \App\Services\DocumentEngine\Providers\ClientPlaceholderProvider::documentTypeAbbreviation($company->identification_type ?: 'CC') }}:</span> {{ $company->identification_number ?: ($company->nit ?? '—') }}</p>
                    <p><span class="text-[var(--text-400)]">Tarjeta profesional:</span> {{ $company->professional_card_number ?? '—' }}</p>
                    <p><span class="text-[var(--text-400)]">Dirección:</span> {{ $company->address ?? '—' }} · {{ $company->city ?? '—' }}</p>
                    <p><span class="text-[var(--text-400)]">Contacto:</span> {{ $company->email }} · {{ $company->phone ?? '—' }}</p>
                </div>
                <p class="text-[12px] text-[var(--text-400)] mt-3">
                    ¿Algún dato desactualizado? <a href="{{ route('profile.edit') }}" class="text-[var(--color-primary)] font-medium">Actualízalo en tu perfil</a>.
                </p>
            </div>
        </div>
    </template>

    {{-- Paso 2: Variables del certificado --}}
    <template x-if="step === 2">
        <div class="p-6 space-y-6">
            <div>
                <h2 class="text-[16px] font-bold text-[var(--text-900)] mb-1">Paso 2 — Variables del Certificado</h2>
                <p class="text-[13px] text-[var(--text-500)]">Solo la información que cambia entre un certificado y otro.</p>
            </div>

            {{-- Destinatario --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-3">Destinatario</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $labelClass }}">Dirigido a <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="text" name="destinatario" x-model="destinatario" required class="{{ $inputClass }}"/>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Ciudad del destinatario <span class="text-[var(--text-400)] font-normal">(opcional)</span></label>
                        <input type="text" name="ciudad_destinatario" x-model="ciudadDestinatario" class="{{ $inputClass }}"/>
                    </div>
                </div>
            </div>

            {{-- Período e ingreso certificado --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-3">Período e ingreso certificado</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="{{ $labelClass }}">Desde <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="date" name="periodo_inicio" x-model="periodoInicio" required class="{{ $inputClass }}"/>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Hasta <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="date" name="periodo_fin" x-model="periodoFin" required class="{{ $inputClass }}"/>
                    </div>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Actividad económica principal <span class="text-[var(--color-danger)]">*</span></label>
                    <input type="text" name="actividad_economica" x-model="actividadEconomica" required class="{{ $inputClass }}" placeholder="Ej. Prestación de servicios de consultoría"/>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="{{ $labelClass }}">Valor del ingreso certificado <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="text" name="ingreso_valor" x-money="ingresoValor" required class="{{ $inputClass }} tabular-nums"/>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Periodicidad <span class="text-[var(--color-danger)]">*</span></label>
                        <select name="ingreso_periodicidad" x-model="ingresoPeriodicidad" required class="{{ $inputClass }}">
                            <option value="anual">Anual</option>
                            <option value="mensual">Mensual</option>
                            <option value="otro">Otra (período certificado)</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Grupo NIIF</label>
                        <select name="grupo_niif" x-model="grupoNiif" class="{{ $inputClass }}">
                            @foreach($grupoNiifOptions as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Procedimientos realizados --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-1">Procedimientos de verificación realizados</h3>
                <p class="text-[12px] text-[var(--text-400)] mb-3">Precargados con los procedimientos habituales — edítalos, quítalos o agrega los que correspondan a este caso.</p>
                <div class="space-y-2">
                    <template x-for="(procedimiento, i) in procedimientos" :key="i">
                        <div class="flex items-center gap-2">
                            <input type="text" :name="`procedimientos[${i}]`" x-model="procedimientos[i]" required
                                   class="flex-1 h-9 px-3 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[13px] bg-[var(--surface-subtle)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                            <button type="button" @click="removeProcedimiento(i)" x-show="procedimientos.length > 1"
                                    class="w-8 h-8 flex items-center justify-center rounded-[var(--radius-control)] text-[var(--text-400)] hover:text-[var(--color-danger)] hover:bg-[var(--color-danger-bg)] flex-shrink-0">
                                <x-lucide-x class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addProcedimiento()"
                        class="mt-2 inline-flex items-center gap-[6px] h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[12.5px] font-medium hover:bg-[var(--surface-muted)]">
                    <x-lucide-plus class="w-3.5 h-3.5" />
                    Agregar procedimiento
                </button>
            </div>

            {{-- Resultado de la revisión --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-1">Resultado de la revisión</h3>
                <p class="text-[12px] text-[var(--text-400)] mb-3">Describe los soportes revisados, el concepto y el detalle de los ingresos evidenciados.</p>
                <textarea name="resultado_revision" x-model="resultadoRevision" rows="4" required class="{{ $textareaClass }}"
                          placeholder="Ej. De la revisión realizada a los extractos bancarios de los períodos... y a los comprobantes de nómina de los meses... se evidencia que el/la señor(a) obtuvo un ingreso de..."></textarea>
            </div>

            {{-- Expedición --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-3">Expedición</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $labelClass }}">Ciudad de expedición <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="text" name="ciudad_expedicion" x-model="ciudadExpedicion" required class="{{ $inputClass }}" placeholder="Bogotá D.C."/>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Fecha de expedición <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="date" name="fecha_expedicion" x-model="fechaExpedicion" required class="{{ $inputClass }}"/>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

{{-- Navegación --}}
<div class="flex items-center justify-between gap-3">
    <button type="button" @click="prev()" x-show="step > 1"
            class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)]">
        Atrás
    </button>
    <div class="flex-1"></div>
    <button type="button" @click="next()" x-show="step < 2"
            :disabled="step === 1 && !clientId"
            :class="(step === 1 && !clientId) ? 'opacity-50 cursor-not-allowed' : ''"
            class="inline-flex items-center gap-[6px] h-10 px-6 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium rounded-[var(--radius-control)]">
        Siguiente
    </button>
    <button type="submit" x-show="step === 2"
            class="inline-flex items-center gap-[6px] h-10 px-6 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium rounded-[var(--radius-control)]">
        <x-lucide-check-circle class="w-4 h-4" />
        Generar Certificado
    </button>
</div>
</form>
@endif

<script>
function certificateWizard(clientsData) {
    return {
        step: 1,
        clientId: '',
        clients: clientsData,
        destinatario: 'A quien interese',
        ciudadDestinatario: '',
        actividadEconomica: '',
        periodoInicio: '',
        periodoFin: '',
        ingresoValor: 0,
        ingresoPeriodicidad: 'anual',
        grupoNiif: 'no_aplica',
        procedimientos: [
            'Revisión del RUT, a fin de corroborar las responsabilidades y actividades económicas inscritas y vigentes ante la DIAN',
            'Revisión de soportes de las transacciones relacionadas con las actividades señaladas en el RUT (facturas de venta, cuentas de cobro, desprendibles de pago, certificaciones comerciales, contratos)',
            'Revisión de soportes que permiten identificar el detalle de los ingresos percibidos (salarios, honorarios, intereses, dividendos, rendimientos financieros, servicios, comisiones)',
            'Revisión de certificaciones laborales, extractos bancarios y conciliaciones bancarias',
            'Revisión de las declaraciones de renta presentadas',
        ],
        resultadoRevision: '',
        ciudadExpedicion: '',
        fechaExpedicion: '',

        selectedClient() {
            return this.clients[this.clientId] || null;
        },
        addProcedimiento() {
            this.procedimientos.push('');
        },
        removeProcedimiento(i) {
            this.procedimientos.splice(i, 1);
        },
        next() {
            if (this.step < 2) this.step++;
        },
        prev() {
            if (this.step > 1) this.step--;
        },
    };
}
</script>

</x-app-layout>
