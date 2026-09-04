<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Helvetica, Arial, sans-serif;
        font-size: 10pt;
        color: #111111;
        line-height: 1.5;
    }

    .page { padding: 30pt; }

    .header {
        text-align: center;
        padding-bottom: 12pt;
        margin-bottom: 18pt;
        border-bottom: 1pt solid #cccccc;
    }
    .header h1 { font-size: 13pt; text-transform: uppercase; }
    .header p { font-size: 9pt; color: #555555; margin-top: 3pt; }
    .header-logo { margin-bottom: 8pt; }
    .header-logo img { max-height: 55pt; max-width: 210pt; }

    .clause { margin-bottom: 12pt; text-align: justify; }
    .clause strong { display: block; margin-bottom: 3pt; }
    .clause ol { margin: 4pt 0 4pt 18pt; }

    /* ── Firmas ── */
    .clearfix::after { content: ''; display: table; clear: both; }
    .signatures { width: 100%; margin-top: 30pt; page-break-inside: avoid; }
    .signature-box { float: left; width: 46%; text-align: center; }
    .signature-box + .signature-box { float: right; }
    .signature-mark { min-height: 24pt; }
    .signature-mark img { max-height: 24pt; max-width: 100%; }
    .signature-line { border-top: 0.75pt solid #111111; margin-top: 6pt; padding-top: 5pt; }
    .signature-name { font-size: 9pt; font-weight: bold; color: #111111; }
    .signature-detail { font-size: 8pt; color: #666666; margin-top: 2pt; }

    /* La cláusula de Firmas es siempre el último .clause del documento (ver
       ClauseEngine::buildDocument — la plantilla siempre termina en el bloque
       'firmas'). Si no cupiera completa al final de una página, dompdf busca
       hacia atrás el punto de corte más cercano permitido: "inside: avoid" en
       el bloque de firmas evita partir el párrafo y las dos cajas de firma
       entre dos páginas, y "after: avoid" en la cláusula anterior evita que
       el salto ocurra justo antes de las firmas — así siempre se llevan al
       menos el texto de la última cláusula real a la página siguiente, en
       vez de quedar solas en una hoja aparte. */
    .page > .clause:last-child { page-break-inside: avoid; }
    .page > .clause:nth-last-child(2) { page-break-after: avoid; }
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
        <p>{{ $document->documentType->default_prefix }} {{ $document->full_number }} — {{ $document->client->name }}</p>
    </div>

    {!! $document->currentVersion->content_html !!}

</div>
</body>
</html>
