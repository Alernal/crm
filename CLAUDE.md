# CRM Profesional — Contadores Públicos Colombia

## Qué es

Software CRM en la nube para **contadores públicos en Colombia**. Cada contador se registra y gestiona su propio espacio de trabajo: clientes, servicios, cobros, cartera y obligaciones tributarias. Es **multi-contador** — un solo deployment sirve a múltiples usuarios, todos los datos están aislados por `user_id`.

## Qué hace

- **Clientes**: base de datos de clientes con datos tributarios colombianos (NIT, régimen, responsabilidades fiscales, ciudad, departamento).
- **Servicios**: catálogo de servicios o productos que presta el contador con precio base y si aplica IVA.
- **Cuentas de cobro**: documentos de cobro numerados consecutivamente por contador, con ítems, subtotal, IVA y total. Generables como PDF.
- **Cartera**: seguimiento del estado de pago de las cuentas (abonos, pagos totales, mora, saldos).
- **Calendario tributario**: eventos por cliente para IVA, Retefuente, Renta, ICA y otras obligaciones, con alertas configurables por días de anticipación.
- **Documentos con marca de agua**: el contador sube su cédula y tarjeta profesional; al imprimir o generar PDF puede añadir una marca de agua personalizable (texto, opacidad, posición).

## Stack

- **Framework**: Laravel 12 (PHP 8.2+)
- **Frontend**: Blade + Tailwind CSS (Vite)
- **Base de datos**: SQLite (archivo único `database/database.sqlite`)
- **PDF**: DomPDF
- **Marca de agua**: Intervention Image

## Convenciones

- Código en **inglés**, interfaz de usuario en **español**
- Rutas REST con `resource routes` de Laravel
- Validación con Form Requests
- Sin frameworks JS pesados — Blade + Alpine.js ligero
- Sin comentarios salvo que el motivo no sea obvio

---

## Hoja de ruta

### Completado
- [x] Setup Laravel 12 + SQLite
- [x] Migrations: 8 tablas (`users`, `clients`, `services`, `invoices`, `invoice_items`, `payments`, `tax_events`, `documents`)
- [x] Modelos Eloquent con relaciones (`Client`, `Service`, `Invoice`, `InvoiceItem`, `Payment`, `TaxEvent`, `Document`)
- [x] Autenticación con Laravel Breeze (registro, login, logout, recuperación de contraseña)
- [x] Vistas de auth personalizadas en español con diseño split-screen (panel azul + formulario)
- [x] Layout principal con sidebar azul oscuro, navegación completa y top bar con alertas
- [x] Dashboard con 4 KPIs (clientes, cartera, cobrado en el mes, vencimientos próximos), tabla de cuentas recientes y lista de próximos eventos tributarios
- [x] Perfil del contador: datos personales + profesionales (NIT, T.P., teléfono, ciudad, dirección), cambio de contraseña, acceso a documentos y eliminación de cuenta
- [x] Módulo Clientes — CRUD completo (index con búsqueda/filtros, create, edit, show, destroy); cálculo automático del DV por NIT con Alpine.js; responsabilidades tributarias como checkboxes; KPIs y mini-tablas en el detalle del cliente

### Pendiente
- [ ] Módulo Servicios — CRUD completo
- [ ] Módulo Cuentas de Cobro — CRUD + generación PDF
- [ ] Módulo Cartera — listado, abonos, indicadores
- [ ] Módulo Calendario Tributario — CRUD + alertas visuales en dashboard
- [ ] Módulo Documentos — subida + generación PDF con marca de agua
