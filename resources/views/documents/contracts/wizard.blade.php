<x-app-layout>
<x-slot name="title">Nuevo Contrato</x-slot>

@php
    $inputClass = 'w-full bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 h-10 text-[14px] text-[var(--text-700)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]';
    $labelClass = 'block text-[13px] font-medium text-[var(--text-700)] mb-1.5';

    $clientsData = $clients->mapWithKeys(fn ($c) => [$c->id => [
        'name' => $c->name,
        'document' => $c->document_type.' '.$c->document_number.($c->dv ? '-'.$c->dv : ''),
        'address' => $c->address,
        'city' => $c->city,
        'department' => $c->department,
        'email' => $c->email,
        'phone' => $c->phone,
        'contact_person' => $c->contact_person,
    ]]);

    $company = auth()->user();
@endphp

<a href="{{ route('documents.contracts.index') }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-5">
    <x-lucide-arrow-left class="w-4 h-4" />
    Cancelar
</a>

@if($template === null)
<div class="bg-[var(--color-warning-bg)] border border-[var(--color-warning)]/20 text-[var(--color-warning-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    No hay una plantilla activa de "Contrato de Prestación de Servicios". Contacta al administrador o revisa Documentos › Plantillas.
</div>
@else

<form method="POST" action="{{ route('documents.contracts.generate') }}"
      x-data="documentWizard(@js($clientsData), @js($services), @js($especialidades))">
@csrf
{{-- Los <template x-if="step === N"> desmontan por completo su contenido del DOM al
     cambiar de paso — un <select>/<input> dentro de un paso ya no viajaría en el POST
     final si solo viviera ahí. Este hidden persiste fuera de los x-if y siempre lleva
     el valor vigente. --}}
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

    {{-- Paso 1: Cliente + datos de EL CONSULTOR (no hay nada que "elegir" aquí — siempre eres tú,
         datos de solo lectura de tu propio perfil; fusionado con el paso de cliente para no gastar
         un paso completo del wizard en una pantalla sin ninguna decisión real) --}}
    <template x-if="step === 1">
        <div class="p-6 space-y-6">
            <div>
                <h2 class="text-[16px] font-bold text-[var(--text-900)] mb-1">Paso 1 — Cliente y tus datos</h2>
                <p class="text-[13px] text-[var(--text-500)] mb-4">El sistema cargará automáticamente los datos del contratante desde la ficha del cliente.</p>

                <label class="{{ $labelClass }}">Cliente <span class="text-[var(--color-danger)]">*</span></label>
                <select x-model="clientId" required class="{{ $inputClass }}">
                    <option value="">Seleccionar…</option>
                    @foreach($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>

                <template x-if="selectedClient()">
                    <div class="mt-4 bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] p-4 text-[13px] text-[var(--text-700)] space-y-1">
                        <p><span class="text-[var(--text-400)]">Razón social / nombre:</span> <span x-text="selectedClient().name"></span></p>
                        <p><span class="text-[var(--text-400)]">Identificación:</span> <span x-text="selectedClient().document"></span></p>
                        <p><span class="text-[var(--text-400)]">Dirección:</span> <span x-text="selectedClient().address || '—'"></span> · <span x-text="selectedClient().city || '—'"></span></p>
                        <p><span class="text-[var(--text-400)]">Contacto:</span> <span x-text="selectedClient().email || '—'"></span> · <span x-text="selectedClient().phone || '—'"></span></p>
                    </div>
                </template>
            </div>

            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-1">Tus datos como EL CONSULTOR</h3>
                <p class="text-[12px] text-[var(--text-400)] mb-3">Cargados automáticamente desde tu perfil — no necesitas escribir nada aquí.</p>

                <div class="bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] p-4 text-[13px] text-[var(--text-700)] space-y-1">
                    <p><span class="text-[var(--text-400)]">Nombre:</span> {{ $company->name }}</p>
                    <p><span class="text-[var(--text-400)]">{{ \App\Services\DocumentEngine\Providers\ClientPlaceholderProvider::documentTypeAbbreviation($company->identification_type ?: 'CC') }}:</span> {{ $company->identification_number ?: ($company->nit ?? '—') }}</p>
                    <p><span class="text-[var(--text-400)]">Dirección:</span> {{ $company->address ?? '—' }} · {{ $company->city ?? '—' }}</p>
                    <p><span class="text-[var(--text-400)]">Contacto:</span> {{ $company->email }} · {{ $company->phone ?? '—' }}</p>
                    <p><span class="text-[var(--text-400)]">Tarjeta profesional:</span> {{ $company->professional_card_number ?? '—' }}</p>
                </div>
                <p class="text-[12px] text-[var(--text-400)] mt-3">
                    ¿Algún dato desactualizado? <a href="{{ route('profile.edit') }}" class="text-[var(--color-primary)] font-medium">Actualízalo en tu perfil</a>.
                </p>
            </div>
        </div>
    </template>

    {{-- Paso 2: Variables del contrato --}}
    <template x-if="step === 2">
        <div class="p-6 space-y-6">
            <div>
                <h2 class="text-[16px] font-bold text-[var(--text-900)] mb-1">Paso 2 — Variables del Contrato</h2>
                <p class="text-[13px] text-[var(--text-500)]">Solo la información que cambia entre un contrato y otro.</p>
            </div>

            {{-- Tipo de asesoría --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-1">Tipo de asesoría</h3>
                <p class="text-[12px] text-[var(--text-400)] mb-3">
                    Define el título del contrato y sugiere los servicios típicos de esa especialidad en el Objeto del contrato —
                    los puedes editar, eliminar o agregar libremente después.
                </p>
                <select name="especialidad" x-model="especialidad" @change="applyEspecialidadPreset()" required class="{{ $inputClass }} max-w-md">
                    @foreach($especialidades as $key => $e)
                    <option value="{{ $key }}">{{ $e['label'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Información general --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-3">Información general</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $labelClass }}">Ciudad de celebración <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="text" name="ciudad_celebracion" x-model="ciudadCelebracion" required class="{{ $inputClass }}" placeholder="Bogotá D.C."/>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Fecha de elaboración <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="date" name="fecha_elaboracion" x-model="fechaElaboracion" required class="{{ $inputClass }}"/>
                    </div>
                </div>
            </div>

            {{-- Objeto del contrato --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-1">Objeto del contrato</h3>
                <p class="text-[12px] text-[var(--text-400)] mb-3">Selecciona servicios existentes o crea uno personalizado. El sistema arma la cláusula automáticamente.</p>

                <div class="flex items-center gap-2 mb-3 flex-wrap">
                    <select @change="addFromCatalog($event.target.value); $event.target.value=''" class="{{ $inputClass }} max-w-xs">
                        <option value="">+ Agregar servicio existente…</option>
                        @foreach($services as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" @click="addService()"
                            class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
                        <x-lucide-plus class="w-3.5 h-3.5" />
                        Servicio personalizado
                    </button>
                </div>

                <div class="space-y-2">
                    <template x-for="(service, i) in servicios" :key="i">
                        <div class="flex items-start gap-2 bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] p-3">
                            <div class="flex-1 space-y-2">
                                <input type="text" :name="`servicios[${i}][nombre]`" x-model="service.nombre" required
                                       placeholder="Nombre del servicio"
                                       class="w-full h-9 px-3 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[13px] bg-[var(--surface-card)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                                <input type="text" :name="`servicios[${i}][descripcion]`" x-model="service.descripcion"
                                       placeholder="Descripción adicional (opcional)"
                                       class="w-full h-9 px-3 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[12.5px] bg-[var(--surface-card)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                            </div>
                            <button type="button" @click="removeService(i)" x-show="servicios.length > 1"
                                    class="w-8 h-8 flex items-center justify-center rounded-[var(--radius-control)] text-[var(--text-400)] hover:text-[var(--color-danger)] hover:bg-[var(--color-danger-bg)] flex-shrink-0">
                                <x-lucide-x class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                </div>

                <div class="mt-3 bg-[var(--color-primary-light)] border border-[var(--border-default)] rounded-[var(--radius-control)] p-3 text-[12.5px] text-[var(--text-700)]">
                    <p class="font-medium text-[var(--color-primary)] mb-1">Vista previa de la cláusula:</p>
                    <p>Los servicios incluyen:</p>
                    <ol class="list-decimal list-inside">
                        <template x-for="(service, i) in servicios" :key="i">
                            <li x-text="(service.nombre || '(sin nombre)') + (service.descripcion ? ': ' + service.descripcion : '')"></li>
                        </template>
                    </ol>
                </div>
            </div>

            {{-- Duración --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-3">Duración</h3>
                <div class="flex items-center gap-4 mb-3">
                    <label class="inline-flex items-center gap-2 text-[13px] text-[var(--text-700)]">
                        <input type="radio" name="duracion_modo" value="meses" x-model="duracionModo"> Número de meses
                    </label>
                    <label class="inline-flex items-center gap-2 text-[13px] text-[var(--text-700)]">
                        <input type="radio" name="duracion_modo" value="fechas" x-model="duracionModo"> Fecha de inicio y fin
                    </label>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="{{ $labelClass }}">Fecha de inicio <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="date" name="fecha_inicio" x-model="fechaInicio" required class="{{ $inputClass }}"/>
                    </div>
                    <div x-show="duracionModo === 'meses'">
                        <label class="{{ $labelClass }}">Número de meses <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="number" name="duracion_meses" x-model.number="duracionMeses" min="1" max="120"
                               :required="duracionModo === 'meses'" class="{{ $inputClass }} tabular-nums"/>
                    </div>
                    <div x-show="duracionModo === 'fechas'">
                        <label class="{{ $labelClass }}">Fecha de terminación <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="date" name="fecha_fin" x-model="fechaFin" :required="duracionModo === 'fechas'" class="{{ $inputClass }}"/>
                    </div>
                </div>
            </div>

            {{-- Honorarios --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-3">Honorarios</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="{{ $labelClass }}">Valor total del contrato <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="text" name="valor" x-money="valor" required class="{{ $inputClass }} tabular-nums"/>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Periodicidad de pago <span class="text-[var(--color-danger)]">*</span></label>
                        <select name="periodicidad" x-model="periodicidad" required class="{{ $inputClass }}">
                            <option value="unico">Pago único</option>
                            <option value="mensual">Mensual</option>
                            <option value="bimestral">Bimestral</option>
                            <option value="trimestral">Trimestral</option>
                            <option value="semestral">Semestral</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                    <div x-show="periodicidad !== 'unico'">
                        <label class="{{ $labelClass }}">Valor por período</label>
                        <input type="text" name="valor_periodico" x-money="valorPeriodico" class="{{ $inputClass }} tabular-nums"/>
                        <p class="text-[11px] text-[var(--text-400)] mt-1">Calculado automáticamente: valor total ÷ número de períodos. Editable si necesitas un reparto distinto.</p>
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
        Generar Contrato
    </button>
</div>
</form>
@endif

<script>
function documentWizard(clientsData, servicesCatalog, especialidades) {
    return {
        step: 1,
        clientId: '',
        clients: clientsData,
        servicesCatalog: servicesCatalog,
        especialidades: especialidades,
        especialidad: 'tributaria',
        servicios: especialidades['tributaria'].servicios.map(s => ({ nombre: s.nombre || '', descripcion: s.descripcion || '' })),
        ciudadCelebracion: '',
        fechaElaboracion: '',
        duracionModo: 'meses',
        duracionMeses: 6,
        fechaInicio: '',
        fechaFin: '',
        valor: 0,
        periodicidad: 'mensual',
        valorPeriodico: 0,
        mesesPorPeriodo: { mensual: 1, bimestral: 2, trimestral: 3, semestral: 6, anual: 12 },

        init() {
            this.recalcValorPeriodico();
            this.$watch('valor', () => this.recalcValorPeriodico());
            this.$watch('periodicidad', () => this.recalcValorPeriodico());
            this.$watch('duracionModo', () => this.recalcValorPeriodico());
            this.$watch('duracionMeses', () => this.recalcValorPeriodico());
            this.$watch('fechaInicio', () => this.recalcValorPeriodico());
            this.$watch('fechaFin', () => this.recalcValorPeriodico());
        },
        duracionEnMeses() {
            if (this.duracionModo === 'meses') {
                return Number(this.duracionMeses) || 0;
            }
            if (!this.fechaInicio || !this.fechaFin) return 0;
            const inicio = new Date(this.fechaInicio);
            const fin = new Date(this.fechaFin);
            if (isNaN(inicio) || isNaN(fin) || fin <= inicio) return 0;
            let meses = (fin.getFullYear() - inicio.getFullYear()) * 12 + (fin.getMonth() - inicio.getMonth());
            if (fin.getDate() >= inicio.getDate()) meses += 1;
            return Math.max(meses, 1);
        },
        numeroPeriodos() {
            const meses = this.duracionEnMeses();
            const mesesPer = this.mesesPorPeriodo[this.periodicidad] || 1;
            if (meses <= 0) return 1;
            return Math.max(1, Math.round(meses / mesesPer));
        },
        recalcValorPeriodico() {
            if (this.periodicidad === 'unico') return;
            const n = this.numeroPeriodos();
            this.valorPeriodico = n > 0 ? Math.round((Number(this.valor) || 0) / n) : 0;
        },

        selectedClient() {
            return this.clients[this.clientId] || null;
        },
        applyEspecialidadPreset() {
            const preset = this.especialidades[this.especialidad];
            if (!preset) return;
            this.servicios = preset.servicios.map(s => ({ nombre: s.nombre || '', descripcion: s.descripcion || '' }));
        },
        addService() {
            this.servicios.push({ nombre: '', descripcion: '' });
        },
        removeService(i) {
            this.servicios.splice(i, 1);
        },
        addFromCatalog(serviceId) {
            if (!serviceId) return;
            const service = this.servicesCatalog.find(s => String(s.id) === String(serviceId));
            if (!service) return;
            if (this.servicios.length === 1 && !this.servicios[0].nombre) {
                this.servicios[0] = { nombre: service.name, descripcion: '' };
            } else {
                this.servicios.push({ nombre: service.name, descripcion: '' });
            }
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
