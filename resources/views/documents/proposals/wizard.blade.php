<x-app-layout>
<x-slot name="title">Nueva Propuesta</x-slot>

@php
    $inputClass = 'w-full bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 h-10 text-[14px] text-[var(--text-700)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]';
    $textareaClass = 'w-full bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 py-2.5 text-[14px] text-[var(--text-700)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] resize-none';
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
    No hay una plantilla activa de "Propuesta de Servicios Profesionales". Contacta al administrador o revisa Documentos › Plantillas.
</div>
@else

<form method="POST" action="{{ route('documents.proposals.generate') }}"
      x-data="proposalWizard(@js($clientsData), @js($services), @js($especialidades))">
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
                <p class="text-[13px] text-[var(--text-500)] mb-4">El sistema cargará automáticamente los datos del cliente desde su ficha.</p>

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

    {{-- Paso 2: Contenido de la propuesta --}}
    <template x-if="step === 2">
        <div class="p-6 space-y-6">
            <div>
                <h2 class="text-[16px] font-bold text-[var(--text-900)] mb-1">Paso 2 — Contenido de la Propuesta</h2>
                <p class="text-[13px] text-[var(--text-500)]">Solo la información que cambia entre una propuesta y otra.</p>
            </div>

            {{-- Tipo de asesoría + alcance de servicios --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-1">Tipo de asesoría</h3>
                <p class="text-[12px] text-[var(--text-400)] mb-3">
                    Sugiere los servicios típicos de esa especialidad en el Alcance de los Servicios —
                    los puedes editar, eliminar o agregar libremente después.
                </p>
                <select name="especialidad" x-model="especialidad" @change="applyEspecialidadPreset()" required class="{{ $inputClass }} max-w-md">
                    @foreach($especialidades as $key => $e)
                    <option value="{{ $key }}">{{ $e['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-1">Alcance de los servicios</h3>
                <p class="text-[12px] text-[var(--text-400)] mb-3">Selecciona servicios existentes o crea uno personalizado.</p>

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

                <div class="mt-3 bg-[var(--color-warning-bg)] border border-[var(--color-warning)]/20 rounded-[var(--radius-control)] p-3 text-[12.5px] text-[var(--color-warning-text)]">
                    La sección "Servicios No Incluidos" de la propuesta queda fija (auditoría/revisoría fiscal, asesoría legal especializada, contabilidad operativa, software, RRHH, consultoría empresarial general) y no se edita aquí.
                </div>
            </div>

            {{-- Descripción del proyecto --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-1">Descripción general del proyecto/servicio</h3>
                <p class="text-[12px] text-[var(--text-400)] mb-3">Situación y contexto que motiva la contratación de servicios profesionales.</p>
                <textarea name="descripcion_proyecto" x-model="descripcionProyecto" rows="4" required
                          class="{{ $textareaClass }}" placeholder="Describa la situación actual del cliente y el motivo de esta propuesta…"></textarea>
            </div>

            {{-- Objetivos --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-3">Objetivos de la propuesta</h3>
                <div class="mb-3">
                    <label class="{{ $labelClass }}">Objetivo general <span class="text-[var(--color-danger)]">*</span></label>
                    <textarea name="objetivo_general" x-model="objetivoGeneral" rows="2" required class="{{ $textareaClass }}"></textarea>
                </div>
                <label class="{{ $labelClass }}">Objetivos específicos <span class="text-[var(--color-danger)]">*</span></label>
                <div class="space-y-2">
                    <template x-for="(objetivo, i) in objetivosEspecificos" :key="i">
                        <div class="flex items-center gap-2">
                            <input type="text" :name="`objetivos_especificos[${i}]`" x-model="objetivosEspecificos[i]" required
                                   placeholder="Objetivo específico"
                                   class="flex-1 h-9 px-3 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[13px] bg-[var(--surface-subtle)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                            <button type="button" @click="removeObjetivo(i)" x-show="objetivosEspecificos.length > 1"
                                    class="w-8 h-8 flex items-center justify-center rounded-[var(--radius-control)] text-[var(--text-400)] hover:text-[var(--color-danger)] hover:bg-[var(--color-danger-bg)] flex-shrink-0">
                                <x-lucide-x class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addObjetivo()" x-show="objetivosEspecificos.length < 4"
                        class="mt-2 inline-flex items-center gap-[6px] h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[12.5px] font-medium hover:bg-[var(--surface-muted)]">
                    <x-lucide-plus class="w-3.5 h-3.5" />
                    Agregar objetivo
                </button>
            </div>

            {{-- Metodología --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-3">Metodología y proceso de trabajo</h3>
                <div class="space-y-3">
                    <div>
                        <label class="{{ $labelClass }}">Fase 1 — Diagnóstico y Recopilación de Información</label>
                        <textarea name="metodologia_fase1" x-model="metodologiaFase1" rows="2" required class="{{ $textareaClass }}"></textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Fase 2 — Análisis y Evaluación</label>
                        <textarea name="metodologia_fase2" x-model="metodologiaFase2" rows="2" required class="{{ $textareaClass }}"></textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Fase 3 — Presentación de Resultados y Recomendaciones</label>
                        <textarea name="metodologia_fase3" x-model="metodologiaFase3" rows="2" required class="{{ $textareaClass }}"></textarea>
                    </div>
                </div>
            </div>

            {{-- Información general --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-3">Información general</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="{{ $labelClass }}">Ciudad <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="text" name="ciudad_celebracion" x-model="ciudadCelebracion" required class="{{ $inputClass }}" placeholder="Bogotá D.C."/>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Fecha de elaboración <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="date" name="fecha_elaboracion" x-model="fechaElaboracion" required class="{{ $inputClass }}"/>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Validez (días hábiles) <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="number" name="validez_dias" x-model.number="validezDias" min="1" max="90" required class="{{ $inputClass }} tabular-nums"/>
                    </div>
                </div>
            </div>

            {{-- Inversión y forma de pago --}}
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide mb-3">Inversión y forma de pago</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div>
                        <label class="{{ $labelClass }}">Valor total de los servicios <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="text" name="valor" x-money="valor" required class="{{ $inputClass }} tabular-nums"/>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Forma de pago <span class="text-[var(--color-danger)]">*</span></label>
                        <select name="forma_pago" x-model="formaPago" @change="onFormaPagoChange()" required class="{{ $inputClass }}">
                            <option value="unico">Pago único al inicio de los servicios</option>
                            <option value="cuotas">Pago en cuotas</option>
                        </select>
                    </div>
                </div>

                <div x-show="formaPago === 'cuotas'" class="space-y-2 mb-3">
                    <template x-for="(cuota, i) in cuotas" :key="i">
                        <div class="flex items-center gap-2 bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] p-3">
                            <span class="text-[12.5px] text-[var(--text-400)] w-16 flex-shrink-0" x-text="`Cuota ${i + 1}`"></span>
                            <input type="hidden" :name="`cuotas[${i}][valor]`" x-model="cuota.valor" />
                            <input type="text" x-money="cuota.valor"
                                   :disabled="formaPago !== 'cuotas'"
                                   placeholder="Valor" class="flex-1 h-9 px-3 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[13px] bg-[var(--surface-card)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] tabular-nums"/>
                            <input type="date" :name="`cuotas[${i}][vencimiento]`" x-model="cuota.vencimiento"
                                   :disabled="formaPago !== 'cuotas'"
                                   class="flex-1 h-9 px-3 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[13px] bg-[var(--surface-card)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                            <button type="button" @click="removeCuota(i)" x-show="cuotas.length > 1"
                                    class="w-8 h-8 flex items-center justify-center rounded-[var(--radius-control)] text-[var(--text-400)] hover:text-[var(--color-danger)] hover:bg-[var(--color-danger-bg)] flex-shrink-0">
                                <x-lucide-x class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="addCuota()"
                            class="inline-flex items-center gap-[6px] h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[12.5px] font-medium hover:bg-[var(--surface-muted)]">
                        <x-lucide-plus class="w-3.5 h-3.5" />
                        Agregar cuota
                    </button>
                </div>

                <div>
                    <label class="{{ $labelClass }}">Condiciones de pago <span class="text-[var(--text-400)] font-normal">(opcional)</span></label>
                    <textarea name="condiciones_pago" x-model="condicionesPago" rows="2" class="{{ $textareaClass }}" placeholder="Forma de transferencia, anticipos, etc."></textarea>
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
        Generar Propuesta
    </button>
</div>
</form>
@endif

<script>
function proposalWizard(clientsData, servicesCatalog, especialidades) {
    return {
        step: 1,
        clientId: '',
        clients: clientsData,
        servicesCatalog: servicesCatalog,
        especialidades: especialidades,
        especialidad: 'tributaria',
        servicios: especialidades['tributaria'].servicios.map(s => ({ nombre: s.nombre || '', descripcion: s.descripcion || '' })),
        descripcionProyecto: '',
        objetivoGeneral: '',
        objetivosEspecificos: [''],
        metodologiaFase1: 'Recopilación y análisis de la información suministrada por EL CLIENTE, revisión de documentación relevante y diagnóstico inicial de la situación.',
        metodologiaFase2: 'Evaluación técnica de la información recopilada, identificación de hallazgos, riesgos y oportunidades de mejora conforme al alcance definido.',
        metodologiaFase3: 'Presentación de resultados, conclusiones y recomendaciones a EL CLIENTE, con las observaciones y sugerencias correspondientes.',
        ciudadCelebracion: '',
        fechaElaboracion: '',
        validezDias: 15,
        valor: 0,
        formaPago: 'unico',
        cuotas: [{ valor: 0, vencimiento: '' }],
        condicionesPago: '',

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
        addObjetivo() {
            if (this.objetivosEspecificos.length < 4) this.objetivosEspecificos.push('');
        },
        removeObjetivo(i) {
            this.objetivosEspecificos.splice(i, 1);
        },
        onFormaPagoChange() {
            if (this.formaPago === 'cuotas' && this.cuotas.length === 0) {
                this.cuotas = [{ valor: 0, vencimiento: '' }];
            }
        },
        addCuota() {
            this.cuotas.push({ valor: 0, vencimiento: '' });
        },
        removeCuota(i) {
            this.cuotas.splice(i, 1);
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
