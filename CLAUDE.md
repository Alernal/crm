# CRM Profesional — Contadores Públicos Colombia

CRM multi-contador en la nube. Cada usuario gestiona sus clientes, servicios, cobros, cartera y obligaciones tributarias. Datos aislados por `user_id`.

## Stack
Laravel 12 (PHP 8.2+) · Blade + Tailwind CSS (Vite) · SQLite · DomPDF · Intervention Image · Alpine.js

## Convenciones
- Código en inglés, UI en español
- Resource routes · Form Requests · Sin JS pesado (solo Alpine.js) · Sin comentarios obvios

## Módulos completados
- **Auth**: Breeze, vistas en español, diseño split-screen
- **Layout**: sidebar azul oscuro, top bar con alertas, contenido centrado `max-w-7xl`
- **Dashboard**: 4 KPIs orientados a insight, no solo conteos (cartera por cobrar, cobrado del mes con tendencia % vs. mes anterior, % de cartera vencida, días promedio de cobro emisión→pago); dona "Cartera por antigüedad" (mismos 4 rangos que el módulo Cartera) con total al centro y leyenda con monto+porcentaje por rango, colores de estado (verde/ámbar/salmón/rojo, validados con el script de accesibilidad CVD del skill `dataviz` — la combinación ámbar/salmón/rojo del sistema no pasaba el chequeo de separación por sí sola, así que el paso "salmón" se tomó prestado de la paleta de estado de referencia del skill); barras agrupadas "Facturado vs. cobrado" (últimos 6 meses, azul/verde) con `Chart.js` (`resources/js/app.js`, expuesto en `window.Chart`, ya era dependencia de `package.json` sin usar); tabla cuentas recientes y próximos eventos tributarios (sin cambios). Datos en `DashboardController` vía SQL agregado (misma lógica de antigüedad que `CarteraController`, sin duplicar el cálculo salvo el propio query).
- **Perfil**: datos personales/profesionales (NIT, T.P.), logo, datos bancarios, link de pago en línea (`payment_link`), cambio de contraseña
- **Clientes**: CRUD completo, DV automático por NIT (Alpine.js), responsabilidades tributarias, KPIs en detalle
- **Servicios**: CRUD completo, 8 unidades de cobro, IVA 19% automático
- **Cuentas de cobro**: CRUD + PDF (DomPDF) + impresión modal con iframe; numeración por cliente configurable (prefijo libre + consecutivo independiente); ítems dinámicos; descuento por ítem (%); retefuente (%); plazos Contado/15d/30d/60d/90d; estado automático (borrador/emitida/pagada/vencida/anulada); totales: Subtotal → Desc. → Base → IVA → Retef. → Total; pie PDF: "ALERNAL S.A.S."
- **Numeración por cliente**: cada cliente tiene `invoice_prefix` (ej: "CC", "FAC") e `invoice_consecutive` (contador); el número se asigna atómicamente en `store()` via `lockForUpdate()` + `increment()`; en el formulario de factura el No. de cuenta es reactivo vía Alpine al seleccionar el cliente; se configura en la ficha del cliente (sección "Numeración de cuentas de cobro")
- **Cartera**: index con KPIs globales + antigüedad (al día / 1–30d / 31–60d / +60d) + tabla por cliente; vista por cliente con pagos inline (Alpine.js); vista por cuenta con historial; PaymentController con redirección inteligente
- **Sincronización overdue**: `Invoice::syncOverdueForUser($userId)` — actualiza `sent → overdue` en BD al cargar CarteraController (index + client), DashboardController, InvoiceController y ClientController::show(); garantiza que los KPIs de "Cuentas vencidas" sean siempre correctos sin necesidad de scheduler
- **Panel Administrativo**: rutas bajo `/admin` (`routes/admin.php`), protegidas por el middleware `admin.auth` (`app/Http/Middleware/AdminAuthenticate.php`); usa la misma sesión/login del CRM — no tiene un formulario de login propio. Cualquier usuario con `users.is_admin = true` puede entrar; el sidebar del CRM muestra el enlace "Panel Admin" solo a esos usuarios, y el panel tiene un enlace "Volver al CRM". Columna `is_admin` agregada en la migración `add_is_admin_to_users_table`. Usuario admin sembrado por `database/seeders/AdminUserSeeder.php`:
  - Email: `admin@crmprofesional.com`
  - Contraseña: `admin123`
- **Nómina**: módulo multi-empresa (cada `Employee` pertenece a un `Client`, aislado por `user_id`). Empleados (CRUD, datos personales/laborales/seguridad social/bancarios, `transport_allowance_mode` automático/siempre/nunca con nota para la excepción de vivienda en las instalaciones — CST art. 132); períodos de nómina (`PayrollPeriod`, mensual/quincenal según `clients.payroll_periodicity`, numeración atómica `payroll_prefix`+`payroll_consecutive` igual que `invoice_prefix`); al generar un período nuevo se puede indicar opcionalmente "Copiar conceptos desde" otro período del mismo cliente (botón "Duplicar" en el listado y en la ficha del período, o seleccionable directamente en el modal "Generar nómina") — copia comisiones/bonificaciones/descuentos y horas extra por trabajador como punto de partida editable, pero siempre recalcula de cero los días trabajados y la seguridad social/provisiones para el período nuevo (`PayrollPeriodController::generatePayrolls()`); tabla de liquidación por período con todos los conceptos por empleado (`Payroll`), editables vía modal ("Editar conceptos") mientras el período esté en borrador o ya procesado (`status` `borrador`/`procesada`; se bloquea al marcarlo `pagada`/`anulada`); submódulo de horas extra (`PayrollOvertimeItem`, 7 tipos legales) con modal de filas dinámicas Alpine, mismo criterio de edición; desprendible de pago en PDF/impresión/correo (`App\Mail\PayslipMail`) igual patrón que `InvoiceMail` — diseño tipo comprobante corporativo (franja de acento azul, insignia del tipo de documento, tira de datos del empleado con borde de acento, tablas con zebra striping, cajas de resumen devengado/deducciones en verde/rojo y banner destacado de "Neto a pagar"), sin firma del trabajador (documento generado electrónicamente). Motor de cálculo en `app/Services/Payroll/{PayrollCalculator,OvertimeCalculator}.php`, replicado celda por celda de los liquidadores de Actualícese (`storage/app/Nómina/`) y verificado con tests (`tests/Unit/PayrollCalculatorTest.php`, `OvertimeCalculatorTest.php`, `tests/Feature/PayrollModuleSmokeTest.php`). Parámetros legales (SMLV, auxilio de transporte, jornada laboral, factores de recargo, tarifas ARL/parafiscales) en la tabla global `payroll_legal_settings` con vigencia por fecha (`PayrollLegalSetting::forDate()`) — **no hardcodeados**, para poder actualizarlos cuando cambie la ley sin tocar código; sembrados por `PayrollLegalSettingSeeder` con los valores 2026 (jornada 44h hasta 14-jul-2026, 42h después — Ley 2101 de 2021). ⚠️ El recargo dominical/festivo del 80% solo está confirmado por la fuente hasta el 30-jun-2026 (Ley 2466 de 2025); revisar y actualizar `payroll_legal_settings` cuando Actualícese publique el valor posterior. Novedad de retiro: `employees.termination_date`/`termination_reason` (renuncia, despido con/sin justa causa) — `PayrollPeriodController::generatePayrolls()` excluye al empleado de períodos posteriores al retiro y prorratea (convención de 30 días, simétrico a `hire_date`) el período en que cae la fecha de retiro; no cambia `status` automáticamente. Exención de pensión: checkbox `employees.pension_exempt` ("No cotiza a pensión", ej. pensionado por vejez que sigue trabajando) — cuando está activo, `PayrollCalculator` no liquida `pension_employee`, `pension_employer` ni `fsp_employee` (la obligación de pensión no existe para esa relación laboral); salud y el resto de conceptos no se ven afectados. Liquidación de Prima (`PrimaSettlement`/`PrimaSettlementItem`, semestral) y Cesantías (`CesantiaSettlement`/`CesantiaSettlementItem`, anual): submenús propios bajo "Nómina", sin motor de cálculo propio — suman las provisiones ya calculadas mes a mes en `payrolls` (`prima_provision`, `cesantias_provision`, `interest_cesantias_provision`) para los períodos del cliente contenidos en el semestre/año, verificado contra los liquidadores de referencia (`storage/app/Nómina/8. Liquidador de cesantías.xlsx`, `9. Liquidador de prima.xlsx`); solo generar/ver/imprimir(PDF-iframe)/eliminar, sin ciclo de estados. Fuera de alcance de esta primera entrega (ver roadmap): novedades de incapacidades/licencias/suspensiones, liquidación definitiva de contrato, salario integral, servicio doméstico, embargos.

## Sistema de diseño (UI)

Rediseño visual aplicado a todas las vistas del CRM excepto PDFs/impresión/correos y Panel Admin (`/admin`). Referencia: `resources/css/tokens.css`.

- **Tokens**: colores, radios, sombras y tipografía centralizados en `resources/css/tokens.css`, consumidos vía clases arbitrarias de Tailwind (`bg-[var(--...)]`, etc.) — sin tocar `tailwind.config.js`.
- **Layout**: sidebar 220px / header 60px en `resources/views/layouts/app.blade.php`. El sidebar agrupa Clientes, Servicios, Cuentas de Cobro y Cartera bajo un submenú desplegable **"Ventas"**, y Empleados + Períodos de Nómina bajo **"Nómina"** (mismo patrón: Alpine `x-data`, auto-expande si la ruta activa es uno de sus hijos).
- **Componentes compartidos**: `x-status-badge` (variantes `success/warning/danger/info/neutral`, reservada solo para estado real de ciclo de vida — régimen tributario y método de pago van en texto plano, no badge) y los componentes Breeze (botones, inputs, modal) migrados a tokens.
- **Íconos**: `mallardduck/blade-lucide-icons` (Composer, sin JS/npm nuevo) — `<x-lucide-{nombre}>` para nombres estáticos, `@svg('lucide-'.$var, $class, $attrs)` para nombres dinámicos (sidebar, badges de tipo en Financiero/Cartera). Reemplazó un componente `x-icon` dibujado a mano que ya no existe en el proyecto.
- **Convenciones de UX**: solo la card de contenido principal de cada pantalla lleva sombra (las de apoyo solo llevan borde); un único patrón de "volver" (breadcrumb arriba, nunca un botón suelto duplicado); acciones destructivas con tinte rojo permanente (no solo al hover); estado "anulada/cancelada" es `neutral`, no `warning`, para no competir visualmente con lo que sí requiere atención.

### Pendiente / continuar próxima sesión
- Evaluar Preline UI o Flowbite (Tailwind + Alpine, sin framework JS) **solo** para el modal multi-paso "Agregar pago o abono" de Cartera (`resources/views/cartera/index.blade.php`) — decidido en principio con el usuario, no implementado aún.
- El rediseño visual no se aplicó a Panel Admin (`/admin`) ni a PDFs/impresión/correos — quedó fuera de alcance a propósito.
- Cotizaciones no está en el sidebar todavía porque el módulo no existe (ver roadmap abajo); agregar al grupo "Ventas" el día que se construya.

## Migraciones (001–019)
001–008 tablas base · 009 logo users · 010 datos bancarios · 011 payment_method invoices · 012 fix enum · 013 receipt_path payments · 014 rebuild tax_events · 015 índices · 016 discount/withholding en invoices · 017 simple_types tax_events · 018 invoice_prefix + invoice_consecutive en clients · 019 payment_link en users

## Módulos pendientes de desarrollo

### [ ] Nómina — Fase 2 (novedades y liquidación)
El motor base (liquidación mensual/quincenal, horas extra, provisión de prestaciones) ya está implementado. Pendiente, según los mismos liquidadores de Actualícese en `storage/app/Nómina/`:
- [ ] Novedades: incapacidades, licencias de maternidad/paternidad, suspensión de contrato, vacaciones disfrutadas (afectan días liquidables y bases de aportes)
- [ ] Liquidación definitiva de contrato (terminación) — usa promedio de los últimos 12 meses para salario variable (ver hojas "Cesantías/Prima/Vacaciones liquidación de contrato")
- [ ] Salario integral
- [ ] Empleado de servicio doméstico (reglas propias de auxilio de transporte y prestaciones)
- [ ] Embargos judiciales sobre cesantías/prima
- [ ] Confirmar y actualizar en `payroll_legal_settings` el recargo dominical/festivo vigente después del 30-jun-2026 (Ley 2466 de 2025) cuando Actualícese lo publique

### [ ] Calendario Tributario
- CRUD de obligaciones y fechas límite
- Alertas en dashboard (próximos vencimientos)

### [ ] Cotizaciones
Módulo similar a Cuentas de cobro con sus propias particularidades:
- [ ] CRUD completo (crear, editar, duplicar, anular)
- [ ] PDF descargable e impresión modal (misma lógica que cuentas de cobro)
- [ ] Ítems dinámicos con descripción, cantidad, precio, descuento, IVA
- [ ] Estados: borrador / enviada / aceptada / rechazada / vencida / convertida
- [ ] Numeración por cliente con prefijo propio (ej: "COT")
- [ ] Conversión de cotización aceptada a cuenta de cobro (un clic)
- [ ] Validez configurable (días antes de vencimiento)
- [ ] Envío al cliente por correo desde el sistema

### [ ] Tareas
Módulo inspirado en Microsoft To Do:
- [ ] Listas de tareas por usuario (ej: "Clientes", "Declaraciones", "Personal")
- [ ] Tarea con título, descripción, fecha límite, prioridad (alta/media/baja), estado
- [ ] Asociación opcional de tarea a un cliente
- [ ] Vista principal tipo kanban o lista con filtros (hoy / próximas / vencidas / completadas)
- [ ] Recordatorios / alertas en dashboard
- [ ] Subtareas dentro de una tarea principal

### [ ] Archivo en la nube
Módulo inspirado en OneDrive — gestor de archivos del contador:
- [ ] Estructura de carpetas por usuario (crear, renombrar, mover, eliminar)
- [ ] Subida de archivos (PDF, Word, Excel, imágenes) con previsualización
- [ ] Asociación de archivos a un cliente (los archivos del cliente también aparecen en su ficha)
- [ ] Búsqueda por nombre de archivo o carpeta
- [ ] Descarga individual o en ZIP
- [ ] Marca de agua en PDF al previsualizar (opcional por archivo)
- [ ] Almacenamiento en `storage/app/private` aislado por `user_id`

### [ ] Gestión de Documentos
Módulo de generación de documentos profesionales a partir de plantillas. Datos del contador y del cliente se rellenan automáticamente; el resto es editable directamente en el sistema.

#### [ ] Contratos de prestación de servicios
- [ ] Plantilla base configurable (el usuario sube/pega su modelo de contrato)
- [ ] Auto-relleno de datos del contador (nombre, NIT, T.P., dirección, ciudad) y del cliente (nombre, documento, dirección)
- [ ] Editor de texto enriquecido para modificar cláusulas, valores, servicios y plazos
- [ ] Numeración automática de contratos
- [ ] Exportar a PDF (DomPDF) e imprimir
- [ ] Envío al cliente por correo para firma
- [ ] Carga del contrato firmado y escaneado en la ficha del cliente

#### [ ] Propuestas comerciales / de servicios
- [ ] Mismo flujo que contratos (plantilla + auto-relleno + editor + PDF + correo)
- [ ] Sección de servicios ofertados con valores y condiciones
- [ ] Estado: borrador / enviada / aceptada / rechazada
- [ ] Conversión de propuesta aceptada a contrato o cotización

#### [ ] Estados financieros (plantillas interactivas)
Reemplaza el flujo Excel — el contador ingresa los valores directamente en el sistema.
- [ ] **Estado de Resultados** — ingresos, costos, gastos, utilidad neta
- [ ] **Estado de Situación Financiera** (Balance General) — activos, pasivos, patrimonio
- [ ] **Estado de Cambios en el Patrimonio**
- [ ] **Estado de Flujos de Efectivo** (método directo e indirecto)
- [ ] Periodos configurables: anual, comparativo (2 años), trimestral, cuatrimestral
- [ ] Conceptos estandarizados bajo NIIF / PCGA Colombia (editables)
- [ ] Exportar a PDF e imprimir
- [ ] Guardado por cliente y periodo (historial de estados)

#### [ ] Certificado de ingresos
- [ ] Plantilla estándar con auto-relleno (datos del contador emisor + datos del cliente/empleado)
- [ ] Campos editables: concepto del ingreso, valor, periodo, firma
- [ ] Exportar a PDF e imprimir
- [ ] Carga del certificado firmado en la ficha del cliente
