<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Helvetica, Arial, sans-serif;
        font-size: 11pt;
        color: #111111;
        line-height: 1.6;
        background: #ffffff;
    }

    /* Deliberadamente SIN min-height en .page ni fondo gris de "escritorio":
       un min-height:792pt (alto de una hoja carta) causaba una página en
       blanco espuria al final de cualquier certificado que llenara casi
       —pero no del todo— una sola página completa (DomPDF reserva ese alto
       mínimo por cada fragmento de página en el que se parte el contenido,
       no solo una vez en total). */
    .page {
        max-width: 612pt;
        margin: 0 auto;
        padding: 36pt 40pt 40pt 40pt;
        background: #ffffff;
    }

    .header {
        text-align: center;
        padding-bottom: 12pt;
        margin-bottom: 20pt;
        border-bottom: 1pt solid #cccccc;
    }
    .header h1 { font-size: 14pt; text-transform: uppercase; letter-spacing: 0.5pt; }
    .header-logo { margin-bottom: 8pt; }
    .header-logo img { max-height: 55pt; max-width: 210pt; }

    .clause { margin-bottom: 13pt; text-align: justify; }
    .clause strong { color: #000000; }
    .clause ol, .clause ul { margin: 4pt 0 4pt 18pt; }

    /* ── Firma (una sola caja — carta unilateral, sin firma del cliente) ── */
    .signature-single { width: 240pt; margin-top: 34pt; page-break-inside: avoid; text-align: left; }
    .signature-mark { min-height: 24pt; }
    .signature-mark img { max-height: 24pt; max-width: 100%; }
    .signature-line { border-top: 0.75pt solid #111111; margin-top: 6pt; padding-top: 5pt; }
    .signature-name { font-size: 10pt; font-weight: bold; color: #111111; }
    .signature-detail { font-size: 9pt; color: #666666; margin-top: 2pt; }

    .page > .clause:last-child { page-break-inside: avoid; }
    .page > .clause:nth-last-child(2) { page-break-after: avoid; }

    /* ── Pie de página corporativo — bloque normal del flujo, NO position:fixed.
       Se probó primero repitiéndolo en cada página física vía position:fixed
       (DomPDF lo soporta bien), pero el motor de impresión de Chromium
       (window.print()/"Guardar como PDF" del navegador) NO reserva espacio
       para un elemento fixed de forma confiable durante la paginación —
       confirmado con una prueba aislada mínima (sin nada del certificado de
       por medio) donde el texto seguía atravesando el pie de página pese al
       espacio reservado. Como bloque normal, si no cabe en lo que resta de
       la página simplemente se traslada completo a la siguiente hoja
       (`page-break-inside: avoid`) — nunca puede quedar encima del texto en
       NINGÚN motor, a costa de no repetirse en cada página física (solo
       aparece una vez, al final, después de la firma). ── */
    .footer {
        margin-top: 40pt;
        padding-top: 8pt;
        border-top: 0.75pt solid #dddddd;
        text-align: center;
        page-break-inside: avoid;
    }
    .footer-contact { font-size: 8pt; color: #999999; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        @if($logoDataUri)
        <div class="header-logo">
            <img src="{{ $logoDataUri }}" alt="Logo" />
        </div>
        @endif
        <h1>{{ $document->documentType->label }}</h1>
    </div>

    {!! $document->currentVersion->content_html !!}

    @php
        $footerContact = collect([
            $document->user->email,
            $document->user->phone,
            collect([$document->user->address, $document->user->city])->filter()->implode(', ') ?: null,
        ])->filter()->implode('  ·  ');
    @endphp
    @if($footerContact !== '')
    <div class="footer">
        <div class="footer-contact">{{ $footerContact }}</div>
    </div>
    @endif

</div>
</body>
</html>
