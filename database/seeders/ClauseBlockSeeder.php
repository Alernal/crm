<?php

namespace Database\Seeders;

use App\Models\ClauseBlock;
use Illuminate\Database\Seeder;

/**
 * Catálogo de bloques de cláusula extraído íntegramente del contrato real
 * analizado (Documentos/CONTRATO_CONSULTORIA_TRIBUTARIA.docx). De las 19
 * filas (preámbulo + 17 cláusulas numeradas + firmas), solo 3 son
 * dinámicas (objeto/servicios, duración, pago) — el resto son bloques de
 * texto fijo con sustitución de placeholders simples vía StaticClauseResolver.
 *
 * Los bloques `propuesta_*` (agregados para el módulo de Propuestas
 * Comerciales, modelo Documentos/PROPUESTA_EJEMPLO_COMPLETO.docx) son
 * propios de ese documento. La sección "Términos y Condiciones" de la
 * propuesta NO tiene bloque propio: la plantilla de propuesta
 * (`DefaultTemplateProvisioner::PROPOSAL_CLAUSE_ORDER`) reutiliza
 * directamente los `clause_blocks` `obligaciones_consultor`,
 * `obligaciones_cliente`, `propiedad_intelectual`, `confidencialidad` y
 * `terminacion_anticipada` ya definidos abajo para el contrato — la
 * propuesta es el antecedente contractual del contrato, así que comparte
 * el mismo texto legal en vez de duplicarlo con una redacción distinta.
 *
 * Los bloques `certificado_*` (Certificado de Ingresos de persona natural,
 * modelo Documentos/VB25-Certificado-contador-ingresos-persona-natural.docx)
 * son propios — a diferencia de contrato/propuesta, es una carta unilateral
 * del contador (sin cláusulas numeradas, sin firma del cliente), así que no
 * reutiliza ningún bloque de los anteriores.
 */
class ClauseBlockSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->blocks() as $block) {
            ClauseBlock::updateOrCreate(['key' => $block['key']], $block + ['is_active' => true]);
        }
    }

    private function blocks(): array
    {
        return [
            [
                'key' => 'preambulo',
                'label' => 'Preámbulo — identificación de las partes',
                // computed: la descripción de EL CLIENTE cambia de estructura completa (no solo
                // placeholders) cuando es persona jurídica — ahí firma el representante legal,
                // no la empresa. PreambleClauseResolver arma {{contrato.descripcion_cliente}};
                // la parte de EL CONSULTOR + cierre es igual en ambos casos y queda aquí.
                'resolver_strategy' => ClauseBlock::STRATEGY_COMPUTED,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\PreambleClauseResolver::class,
                'default_title' => '',
                'default_content' => 'Entre los suscritos a saber: (i) {{contrato.descripcion_cliente}}, quien en lo sucesivo se denominará "EL CLIENTE"; y (ii) {{empresa.nombre}}, mayor de edad, identificado con {{empresa.tipo_identificacion}} No. {{empresa.identificacion}}, expedida en {{empresa.ciudad}}, con domicilio en {{empresa.direccion}}, quien en lo sucesivo se denominará "EL CONSULTOR", han convenido en celebrar el presente {{contrato.titulo_documento}} conforme a los términos y condiciones siguientes:',
            ],
            [
                'key' => 'objeto_servicios',
                'label' => 'Cláusula Primera — Objeto del Contrato',
                'resolver_strategy' => ClauseBlock::STRATEGY_BUILDER,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\ServicesObjectClauseResolver::class,
                'default_title' => 'CLÁUSULA PRIMERA - OBJETO DEL CONTRATO',
                'default_content' => "El presente contrato tiene por objeto la prestación de servicios profesionales de {{contrato.especialidad_texto}} por parte de EL CONSULTOR a favor de EL CLIENTE, en el marco de su actividad económica y conforme a la normatividad vigente en Colombia. Los servicios incluyen:\n{{contrato.objeto}}\n{{contrato.especialidad_disclaimer}}",
            ],
            [
                'key' => 'duracion',
                'label' => 'Cláusula Segunda — Término y Duración',
                'resolver_strategy' => ClauseBlock::STRATEGY_COMPUTED,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\DurationClauseResolver::class,
                'default_title' => 'CLÁUSULA SEGUNDA - TÉRMINO Y DURACIÓN',
                'default_content' => 'El presente contrato tendrá duración de {{contrato.duracion_texto}} calendario, contados a partir de la fecha de firma por ambas partes. La renovación deberá formalizarse mediante acuerdo escrito. Terminado el objeto, para continuar servicios deberá celebrarse un nuevo contrato.',
            ],
            [
                'key' => 'obligaciones_consultor',
                'label' => 'Cláusula Tercera — Obligaciones de El Consultor',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA TERCERA - OBLIGACIONES DE EL CONSULTOR',
                'default_content' => 'Son obligaciones de EL CONSULTOR: (i) Prestar servicios con debida diligencia, profesionalismo y bajo criterios de confidencialidad e independencia técnica; (ii) Informar oportunamente sobre riesgos tributarios, fiscales o legales identificados; (iii) Entregar informes técnicos y soportes derivados de los servicios; (iv) Guardar confidencialidad sobre información del CLIENTE, incluso posterior a la terminación del contrato; (v) Actuar con independencia técnica conforme a normas tributarias, contables y éticas aplicables; (vi) Respetar plazos para entrega de productos tributarios.',
            ],
            [
                'key' => 'obligaciones_cliente',
                'label' => 'Cláusula Cuarta — Obligaciones de El Cliente',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA CUARTA - OBLIGACIONES DE EL CLIENTE',
                'default_content' => 'EL CLIENTE se obliga a: (i) Pagar de manera puntual los honorarios pactados en la Cláusula Quinta, conforme a periodicidad y forma allí señaladas; (ii) Suministrar de manera completa, veraz y oportuna toda la información tributaria requerida; (iii) Cubrir honorarios adicionales de servicios no contemplados, previa aceptación; (iv) Pagar por anticipado gastos de viaje cuando EL CONSULTOR se desplace fuera de la ciudad; (v) Informar de forma oportuna sobre cambios tributarios, estructurales o de actividad económica que afecten el contrato; (vi) Mantener indemne a EL CONSULTOR de responsabilidades derivadas de información inexacta suministrada por EL CLIENTE. EL CLIENTE es responsable exclusivo de la veracidad de la información tributaria. Cualquier omisión, falsedad o inexactitud que genere sanciones tributarias es responsabilidad exclusiva de EL CLIENTE. En caso de error atribuible exclusivamente a EL CONSULTOR que genere erogación verificada, EL CONSULTOR responderá hasta por el DIEZ POR CIENTO (10%) del valor total del contrato, sin perjuicio de cláusula penal.',
            ],
            [
                'key' => 'clausula_pago',
                'label' => 'Cláusula Quinta — Valor, Forma y Condiciones de Pago',
                'resolver_strategy' => ClauseBlock::STRATEGY_COMPUTED,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\PaymentClauseResolver::class,
                'default_title' => 'CLÁUSULA QUINTA - VALOR, FORMA Y CONDICIONES DE PAGO',
                'default_content' => 'El valor total de los servicios asciende a {{contrato.valor_letras}} (${{contrato.valor}}) por un período de {{contrato.duracion_texto}}. EL CLIENTE pagará {{contrato.valor_periodico_letras}} (${{contrato.valor_periodico}}) {{contrato.periodicidad_texto}}. Condiciones de pago: pago (mes, quincena, trimestre, semestre, etc.) anticipado mediante transferencia bancaria a nombre de {{empresa.titular_cuenta}}, {{empresa.banco}} Cta. {{empresa.numero_cuenta}}, C.C. {{empresa.titular_cedula}}. Los gastos de viaje, hospedaje y desplazamiento a otras ciudades serán cobrados a costo real, previa comunicación. EL CLIENTE suministrará información con mínimo cinco (5) días hábiles antes de vencimientos tributarios. Entregas tardías eximen a EL CONSULTOR de responsabilidad por errores derivados de falta de tiempo.',
            ],
            [
                'key' => 'fuerza_mayor',
                'label' => 'Cláusula Sexta — Caso Fortuito y Fuerza Mayor',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA SEXTA - CASO FORTUITO Y FUERZA MAYOR',
                'default_content' => 'Ninguna de las partes será responsable por incumplimientos derivados de hechos imprevisibles, irresistibles y ajenos a su voluntad. Las obligaciones se suspenderán mientras persista la causa. La parte afectada notificará por escrito dentro de dos (2) días hábiles.',
            ],
            [
                'key' => 'mora_suspension',
                'label' => 'Cláusula Séptima — Mora en el Pago y Suspensión de Servicios',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA SÉPTIMA - MORA EN EL PAGO Y SUSPENSIÓN DE SERVICIOS',
                'default_content' => 'EL CLIENTE entra en mora automáticamente cuando incumple el pago. Deberá pagar intereses moratorios del tres por ciento (3%) mensual sobre el saldo vencido. Cuando la mora supere treinta (30) días hábiles, EL CONSULTOR está autorizado para suspender de forma inmediata los servicios, sin responsabilidad por daños que genere al CLIENTE.',
            ],
            [
                'key' => 'clausula_penal',
                'label' => 'Cláusula Octava — Cláusula Penal por Incumplimiento',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA OCTAVA - CLÁUSULA PENAL POR INCUMPLIMIENTO',
                'default_content' => 'Incumplimiento de EL CLIENTE: DOS (2) SALARIOS MÍNIMOS MENSUALES LEGALES VIGENTES (SMMLV) más intereses moratorios sobre saldos vencidos. Incumplimiento de EL CONSULTOR: UN (1) SMMLV al momento del incumplimiento.',
            ],
            [
                'key' => 'terminacion_anticipada',
                'label' => 'Cláusula Novena — Terminación Anticipada del Contrato',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA NOVENA - TERMINACIÓN ANTICIPADA DEL CONTRATO',
                'default_content' => 'El contrato podrá ser terminado: (i) Por mutuo consentimiento escrito; (ii) Por iniciativa de cualquiera con notificación escrita con antelación de UN (1) mes; (iii) EL CONSULTOR puede terminar inmediatamente si: EL CLIENTE está en mora >30 días, no suministra información requerida, suministra información falsa, o participa en actividades potencialmente ilícitas. EL CONSULTOR conserva derecho a exigir pago de servicios prestados, cláusula penal y gastos incurridos. Si EL CLIENTE termina sin aviso de un mes, indemnizará a EL CONSULTOR con UN (1) SMMLV como compensación por perjuicios, exigible inmediatamente. El contrato presta mérito ejecutivo conforme a artículos 422 y siguientes del Código General del Proceso.',
            ],
            [
                'key' => 'retencion_documentos',
                'label' => 'Cláusula Décima — Retención de Documentos y Garantía por Pago',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA DÉCIMA - RETENCIÓN DE DOCUMENTOS Y GARANTÍA POR PAGO',
                'default_content' => 'EL CONSULTOR está autorizado para retener documentación, informes y productos generados hasta que EL CLIENTE haya pagado íntegramente todas las obligaciones pecuniarias: honorarios, gastos, intereses y cláusula penal. Tal retención constituye derecho de retención legal sin generar responsabilidad a EL CONSULTOR.',
            ],
            [
                'key' => 'solucion_controversias',
                'label' => 'Cláusula Undécima — Solución de Controversias y Arbitramento',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA UNDÉCIMA - SOLUCIÓN DE CONTROVERSIAS Y ARBITRAMENTO',
                'default_content' => 'Toda controversia será resuelta mediante: (i) Audiencia obligatoria de conciliación ante centro autorizado por el Ministerio del Interior Colombiano; (ii) De no lograrse acuerdo, arbitramento ante Centro de Arbitraje y Conciliación de la Cámara de Comercio competente; (iii) El tribunal estará conformado por UN (1) árbitro único que decidirá en derecho; (iv) Los costos serán asumidos en partes iguales por ambas partes.',
            ],
            [
                'key' => 'naturaleza_juridica',
                'label' => 'Cláusula Duodécima — Naturaleza Jurídica del Contrato',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA DUODÉCIMA - NATURALEZA JURÍDICA DEL CONTRATO',
                'default_content' => 'El contrato es de naturaleza civil y comercial. No genera vínculo laboral. EL CONSULTOR actuará con autonomía técnica y operativa, sin subordinación ni horarios fijos. EL CLIENTE no asumirá obligaciones de seguridad social, prestaciones ni aportes parafiscales.',
            ],
            [
                'key' => 'propiedad_intelectual',
                'label' => 'Cláusula Decimotercera — Propiedad Intelectual y Derechos de Autor',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA DECIMOTERCERA - PROPIEDAD INTELECTUAL Y DERECHOS DE AUTOR',
                'default_content' => 'Toda documentación, informe, análisis y producto intelectual generado por EL CONSULTOR será de su propiedad intelectual exclusiva conforme a Ley 23 de 1982. EL CLIENTE tendrá derecho limitado de uso exclusivamente para su actividad empresarial. No podrá ceder, reproducir, modificar ni divulgar tales contenidos sin consentimiento escrito de EL CONSULTOR.',
            ],
            [
                'key' => 'confidencialidad',
                'label' => 'Cláusula Decimocuarta — Confidencialidad y Reserva Profesional',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA DECIMOCUARTA - CONFIDENCIALIDAD Y RESERVA PROFESIONAL',
                'default_content' => 'Las partes guardarán confidencialidad absoluta sobre información tributaria y datos del CLIENTE. Esta obligación permanece vigente sin límite temporal, incluso posterior a la terminación del contrato. La información no será divulgada sin consentimiento escrito previo, excepto por requerimiento de autoridades competentes, información pública o orden judicial.',
            ],
            [
                'key' => 'exclusiones_alcance',
                'label' => 'Cláusula Decimoquinta — Exclusiones del Alcance del Contrato',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA DECIMOQUINTA - EXCLUSIONES DEL ALCANCE DEL CONTRATO',
                'default_content' => 'Los servicios NO incluyen: (i) Auditoría tributaria o revisoría fiscal; (ii) Asesoría legal tributaria especializada o litigios; (iii) Contabilidad operativa o contabilidad general; (iv) Implementación y soporte técnico de software; (v) Gestión de recursos humanos; (vi) Consultoría empresarial general.',
            ],
            [
                'key' => 'conservacion_documentacion',
                'label' => 'Cláusula Decimosexta — Conservación y Retorno de Documentación',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA DECIMOSEXTA - CONSERVACIÓN Y RETORNO DE DOCUMENTACIÓN',
                'default_content' => 'EL CONSULTOR conservará documentación durante CINCO (5) años. Vencido tal plazo, podrá destruirla sin responsabilidad. Si EL CLIENTE desea retomar documentos antes, deberá solicitarlo con diez (10) días de anticipación, asumiendo costos de organización, empaque y transporte.',
            ],
            [
                'key' => 'disposiciones_finales',
                'label' => 'Cláusula Decimoséptima — Disposiciones Finales',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'CLÁUSULA DECIMOSÉPTIMA - DISPOSICIONES FINALES',
                'default_content' => 'Modificaciones requieren consentimiento escrito de ambas partes. Este contrato constituye acuerdo integral. Se rige por leyes de la República de Colombia. La nulidad de una cláusula no afecta las demás. Notificaciones se realizarán por escrito a los domicilios del preámbulo.',
            ],
            [
                'key' => 'firmas',
                'label' => 'Bloque de Firmas',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => '',
                // HTML explícito (no texto plano con espacios) — el HTML colapsa espacios/saltos
                // de línea consecutivos, así que alinear firmas en dos columnas a punta de
                // espacios nunca se separa visualmente ni dibuja línea alguna. Misma estructura
                // .signatures/.signature-box/.signature-line que financial/pdf.blade.php.
                // .signature-name lleva el nombre real de quien firma (no la etiqueta de rol,
                // que va aparte en .signature-role) — EL CLIENTE usa {{cliente.firmante_*}},
                // que ya resuelve al representante legal si es persona jurídica o al propio
                // cliente si es natural (ver ClientPlaceholderProvider).
                'default_content' => '<p>En constancia de lo anterior, se firma en dos ejemplares de igual tenor en la ciudad de {{contrato.ciudad_celebracion}}, a los {{contrato.fecha_elaboracion}}.</p>'
                    .'<div class="signatures clearfix">'
                    .'<div class="signature-box">'
                    .'<div class="signature-mark">{{firma.cliente}}</div>'
                    .'<div class="signature-line"></div>'
                    .'<div class="signature-name">{{cliente.firmante_nombre}}</div>'
                    .'<div class="signature-role">EL CLIENTE</div>'
                    .'<div class="signature-detail">{{cliente.firmante_tipo_identificacion}} No. {{cliente.firmante_identificacion}}</div>'
                    .'</div>'
                    .'<div class="signature-box">'
                    .'<div class="signature-mark">{{firma.consultor}}</div>'
                    .'<div class="signature-line"></div>'
                    .'<div class="signature-name">{{empresa.nombre}}</div>'
                    .'<div class="signature-role">EL CONSULTOR</div>'
                    .'<div class="signature-detail">{{empresa.tipo_identificacion_abrev}} No. {{empresa.identificacion}}</div>'
                    .'</div>'
                    .'</div>',
            ],
            [
                'key' => 'texto_libre',
                'label' => 'Cláusula de Texto Libre (genérica)',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => 'NUEVA CLÁUSULA',
                'default_content' => '',
            ],

            // ── Propuestas Comerciales ────────────────────────────────
            [
                'key' => 'propuesta_datos_generales',
                'label' => 'Propuesta — Datos Generales',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => '1. DATOS GENERALES',
                // fecha_vencimiento la calcula ProposalValidityClauseResolver, aunque está
                // adjunto al bloque 'propuesta_validez_aceptacion' — ClauseEngine enriquece
                // TODAS las cláusulas antes de renderizar NINGUNA, así que la variable ya
                // está disponible aquí (mismo patrón que Duración → Pago en el contrato).
                'default_content' => "Fecha de elaboración: {{propuesta.fecha_elaboracion_corta}}\nValidez de la propuesta: {{propuesta.validez_dias}} días hábiles (hasta el {{propuesta.fecha_vencimiento}})",
            ],
            [
                'key' => 'propuesta_datos_cliente',
                'label' => 'Propuesta — Datos del Cliente',
                // computed (no passthrough directo): rellena con "—" los campos opcionales
                // vacíos (dirección, ciudad, contacto, teléfono) — ver
                // ProposalPartyDetailsClauseResolver, que también atiende
                // 'propuesta_datos_consultor'.
                'resolver_strategy' => ClauseBlock::STRATEGY_COMPUTED,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\ProposalPartyDetailsClauseResolver::class,
                'default_title' => '2. DATOS DEL CLIENTE',
                'default_content' => '{{propuesta.datos_cliente_html}}',
            ],
            [
                'key' => 'propuesta_datos_consultor',
                'label' => 'Propuesta — Datos del Consultor',
                'resolver_strategy' => ClauseBlock::STRATEGY_COMPUTED,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\ProposalPartyDetailsClauseResolver::class,
                'default_title' => '3. DATOS DEL CONSULTOR',
                'default_content' => '{{propuesta.datos_consultor_html}}',
            ],
            [
                'key' => 'propuesta_descripcion_proyecto',
                'label' => 'Propuesta — Descripción General del Proyecto/Servicio',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => '4. DESCRIPCIÓN GENERAL DEL PROYECTO/SERVICIO',
                'default_content' => '{{propuesta.descripcion_proyecto}}',
            ],
            [
                'key' => 'propuesta_objetivos',
                'label' => 'Propuesta — Objetivos',
                'resolver_strategy' => ClauseBlock::STRATEGY_BUILDER,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\ProposalObjectivesClauseResolver::class,
                'default_title' => '5. OBJETIVOS DE LA PROPUESTA',
                'default_content' => '{{propuesta.objetivos_html}}',
            ],
            [
                'key' => 'propuesta_alcance_servicios',
                'label' => 'Propuesta — Alcance de los Servicios',
                // Reutiliza ServicesObjectClauseResolver tal cual (ya numera
                // $context->variables['servicios'] como lista jurídica en {{*.objeto}} —
                // no tiene nada específico de "contrato" pese a su ubicación original).
                'resolver_strategy' => ClauseBlock::STRATEGY_BUILDER,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\ServicesObjectClauseResolver::class,
                'default_title' => '6. ALCANCE DE LOS SERVICIOS',
                'default_content' => "La presente propuesta contempla la prestación de los siguientes servicios profesionales, sujeta a los alcances y condiciones aquí descritos:\n{{propuesta.objeto}}",
            ],
            [
                'key' => 'propuesta_servicios_no_incluidos',
                'label' => 'Propuesta — Servicios No Incluidos',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => '7. SERVICIOS NO INCLUIDOS',
                'default_content' => 'Quedan expresamente excluidos del presente acuerdo y, de ser requeridos, serán objeto de una propuesta o cotización adicional, los siguientes servicios: (a) Auditoría tributaria o revisoría fiscal; (b) Asesoría legal tributaria especializada o representación en litigios; (c) Contabilidad operativa o contabilidad general; (d) Implementación y soporte técnico de software; (e) Gestión de recursos humanos; (f) Consultoría empresarial general.',
            ],
            [
                'key' => 'propuesta_metodologia',
                'label' => 'Propuesta — Metodología y Proceso de Trabajo',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => '8. METODOLOGÍA Y PROCESO DE TRABAJO',
                'default_content' => "Fase 1 — Diagnóstico y Recopilación de Información:\n{{propuesta.metodologia_fase1}}\n\nFase 2 — Análisis y Evaluación:\n{{propuesta.metodologia_fase2}}\n\nFase 3 — Presentación de Resultados y Recomendaciones:\n{{propuesta.metodologia_fase3}}",
            ],
            [
                'key' => 'propuesta_inversion_pago',
                'label' => 'Propuesta — Inversión y Forma de Pago',
                'resolver_strategy' => ClauseBlock::STRATEGY_COMPUTED,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\ProposalPaymentClauseResolver::class,
                'default_title' => '9. INVERSIÓN Y FORMA DE PAGO',
                'default_content' => '{{propuesta.inversion_html}}',
            ],
            [
                'key' => 'propuesta_validez_aceptacion',
                'label' => 'Propuesta — Validez y Aceptación',
                'resolver_strategy' => ClauseBlock::STRATEGY_COMPUTED,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\ProposalValidityClauseResolver::class,
                'default_title' => '11. VALIDEZ Y ACEPTACIÓN DE LA PROPUESTA',
                'default_content' => 'Esta propuesta es válida hasta el {{propuesta.fecha_vencimiento}} ({{propuesta.validez_dias}} días hábiles contados a partir de la fecha de elaboración). Después de esta fecha, la propuesta pierde su validez y deberá solicitarse una nueva, la cual podrá incluir variaciones en valor o condiciones conforme a cambios en circunstancias o disponibilidad de EL CONSULTOR.'
                    ."\n\n".'La aceptación expresa de esta propuesta por parte de EL CLIENTE, mediante firma de autorizado y remisión de esta copia debidamente firmada a EL CONSULTOR, constituirá la base y antecedente contractual para la celebración formal del Contrato de Prestación de Servicios correspondiente, el cual contendrá los términos y condiciones definitivos que regirán la relación entre las partes.',
            ],
            [
                'key' => 'propuesta_firmas',
                'label' => 'Propuesta — Bloque de Firmas',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => '',
                // Mismo patrón .signatures/.signature-box/.signature-line que la cláusula
                // 'firmas' del contrato — orden de firmas igual al modelo de referencia
                // (CONSULTOR a la izquierda, CLIENTE a la derecha).
                // .signature-name lleva el nombre real de quien firma (no la etiqueta de rol,
                // que va aparte en .signature-role) — mismo criterio que la cláusula 'firmas'
                // del contrato.
                'default_content' => '<p>En constancia de lo anterior, se firma la presente propuesta en dos ejemplares de igual tenor en la ciudad de {{propuesta.ciudad_celebracion}}, a los {{propuesta.fecha_elaboracion}}.</p>'
                    .'<div class="signatures clearfix">'
                    .'<div class="signature-box">'
                    .'<div class="signature-mark">{{firma.consultor}}</div>'
                    .'<div class="signature-line"></div>'
                    .'<div class="signature-name">{{empresa.nombre}}</div>'
                    .'<div class="signature-role">CONSULTOR (PRESTADOR DE SERVICIOS)</div>'
                    .'<div class="signature-detail">{{empresa.tipo_identificacion_abrev}} No. {{empresa.identificacion}}</div>'
                    .'</div>'
                    .'<div class="signature-box">'
                    .'<div class="signature-mark">{{firma.cliente}}</div>'
                    .'<div class="signature-line"></div>'
                    .'<div class="signature-name">{{cliente.firmante_nombre}}</div>'
                    .'<div class="signature-role">CLIENTE (AUTORIZADO)</div>'
                    .'<div class="signature-detail">{{cliente.firmante_tipo_identificacion}} No. {{cliente.firmante_identificacion}}</div>'
                    .'</div>'
                    .'</div>',
            ],

            // ── Certificado de Ingresos (persona natural) ────────────
            [
                'key' => 'certificado_encabezado',
                'label' => 'Certificado de Ingresos — Encabezado',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => '',
                'default_content' => "{{certificado.ciudad_expedicion}}, {{certificado.fecha_expedicion_corta}}[[BR]][[BR]]Señor(a/es)[[BR]]{{certificado.destinatario}}{{certificado.destinatario_ciudad_linea}}[[BR]][[BR]][[B]]Asunto:[[/B]] Certificado de ingresos.",
            ],
            [
                'key' => 'certificado_identificacion',
                'label' => 'Certificado de Ingresos — Identificación',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => '',
                'default_content' => 'Conforme a la facultad que me es otorgada mediante los artículos 2 y 13 de la Ley 43 de 1990 y el artículo 777 del Estatuto Tributario, y, además, atendiendo lo expuesto en los artículos 69 y 70 de la Ley 43 de 1990, yo {{empresa.nombre}}, con tarjeta profesional vigente No. {{empresa.tarjeta_profesional}}, realizo esta certificación únicamente con el fin de confirmar los ingresos obtenidos en el período [[B]]{{certificado.periodo_texto}}[[/B]] por [[B]]{{cliente.nombre}}[[/B]], identificado(a) con [[B]]{{cliente.tipo_identificacion}} No. {{cliente.identificacion}}[[/B]], quien desarrolla la actividad de {{certificado.actividad_economica}}.',
            ],
            [
                'key' => 'certificado_marco_normativo',
                'label' => 'Certificado de Ingresos — Marco Normativo',
                // computed (adjunto a 'certificado_certificacion', no a este bloque — ver
                // CertificateIncomeClauseResolver): necesita grupo_niif_texto, calculado ahí.
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => '',
                'default_content' => 'Para realizar este encargo, como contador(a), utilizo el marco de referencia de la Norma Internacional de Servicios Relacionados NISR 4400 – Debidas diligencias, la NICC 1 que aborda el sistema de control de calidad, el Código de ética profesional descrito en el capítulo IV de la Ley 43 de 1990 y el anexo 4-2019 contenido en el DUR 2420 de 2015.{{certificado.grupo_niif_texto}}',
            ],
            [
                'key' => 'certificado_procedimientos',
                'label' => 'Certificado de Ingresos — Procedimientos Realizados',
                'resolver_strategy' => ClauseBlock::STRATEGY_BUILDER,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\CertificateProceduresClauseResolver::class,
                'default_title' => '',
                'default_content' => "Los procedimientos utilizados para verificar los ingresos obtenidos en el período {{certificado.periodo_texto}} fueron los siguientes:\n{{certificado.procedimientos_html}}",
            ],
            [
                'key' => 'certificado_resultado',
                'label' => 'Certificado de Ingresos — Resultado de la Revisión',
                'resolver_strategy' => ClauseBlock::STRATEGY_STATIC,
                'resolver_class' => null,
                'default_title' => '',
                'default_content' => "Como resultado de la labor realizada se extrae la siguiente información:\n{{certificado.resultado_revision}}",
            ],
            [
                'key' => 'certificado_certificacion',
                'label' => 'Certificado de Ingresos — Certificación',
                // Único bloque computed del certificado: calcula periodo_texto, grupo_niif_texto
                // y el valor en cifras/letras — quedan disponibles para TODAS las cláusulas
                // (ClauseEngine enriquece antes de renderizar), no solo esta.
                'resolver_strategy' => ClauseBlock::STRATEGY_COMPUTED,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\CertificateIncomeClauseResolver::class,
                'default_title' => '',
                'default_content' => 'Certifico que la información indicada en el párrafo anterior representa un ingreso {{certificado.ingreso_periodicidad_texto}} de [[B]]{{certificado.ingreso_valor_letras}} (${{certificado.ingreso_valor_formateado}})[[/B]] devengado por [[B]]{{cliente.nombre}}[[/B]], identificado(a) con [[B]]{{cliente.tipo_identificacion}} No. {{cliente.identificacion}}[[/B]], para el período [[B]]{{certificado.periodo_texto}}[[/B]] y por las actividades de {{certificado.actividad_economica}}. Esta información se refiere únicamente a los montos certificados que son el reflejo del soporte documental revisado por el período en cuestión, y es exclusivamente para el propósito ya expuesto y para el destinatario ubicado al inicio de este documento; por lo tanto, no debe usarse para ninguna otra finalidad ni distribuirse a terceros distintos.'
                    .'[[BR]][[BR]]Manifiesto que estoy facultado(a) para emitir este certificado y que no percibo amenazas que afecten mi independencia y objetividad. La presente certificación es expedida en {{certificado.ciudad_expedicion}} el {{certificado.fecha_expedicion}}.',
            ],
            [
                'key' => 'certificado_firma',
                'label' => 'Certificado de Ingresos — Firma',
                // computed: CertificateSignatureClauseResolver rellena con "—" los campos
                // opcionales vacíos (teléfono, dirección, ciudad) — mismo motivo que
                // ProposalPartyDetailsClauseResolver en la propuesta.
                'resolver_strategy' => ClauseBlock::STRATEGY_COMPUTED,
                'resolver_class' => \App\Services\DocumentEngine\Resolvers\CertificateSignatureClauseResolver::class,
                'default_title' => '',
                // Una sola caja de firma (solo EL CONTADOR firma esta carta) — clase
                // .signature-single propia, sin el .clearfix/float de dos columnas que
                // usan las firmas de contrato/propuesta.
                'default_content' => '<div class="signature-single">'
                    .'<div class="signature-mark">{{firma.consultor}}</div>'
                    .'<div class="signature-line"></div>'
                    .'<div class="signature-name">{{empresa.nombre}}</div>'
                    .'{{certificado.firma_detalle_html}}'
                    .'</div>',
            ],
        ];
    }
}
