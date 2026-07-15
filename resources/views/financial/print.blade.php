<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Presupuesto {{ $budget->name }}</title>
<style>
    @page {
        size: {{ $paperSize }} {{ $orientation }};
        margin: 14mm 16mm;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 9pt;
        color: #111111;
        background: #f5f5f5;
    }

    .page-wrap {
        background: #ffffff;
        max-width: {{ $orientation === 'portrait' ? '850px' : '1100px' }};
        margin: 0 auto;
        padding: 26pt 30pt 30pt 30pt;
        min-height: 100vh;
        box-shadow: 0 0 24px rgba(0,0,0,.08);
    }

    @media print {
        body { background: #fff; }
        .page-wrap {
            max-width: none;
            margin: 0;
            padding: 0;
            box-shadow: none;
        }
    }

    .clearfix::after { content: ''; display: table; clear: both; }

    /* ── Cabecera ── */
    .header {
        width: 100%;
        padding-bottom: 14pt;
        margin-bottom: 14pt;
        border-bottom: 2pt solid #111;
    }
    .header-left  { width: 38%; float: left; }
    .header-right { width: 60%; float: right; text-align: right; padding-top: 4pt; }

    .logo-box {
        width: 130pt;
        height: 65pt;
        border: 1pt solid #ddd;
        background: #fafafa;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .logo-box img {
        max-width: 124pt;
        max-height: 59pt;
        object-fit: contain;
    }

    .doc-title  { font-size: 17pt; font-weight: bold; color: #111; letter-spacing: 0.5pt; margin-bottom: 3pt; }
    .doc-number { font-size: 10.5pt; font-weight: bold; color: #444; margin-bottom: 8pt; }
    .doc-dates  { font-size: 8.5pt; color: #444; line-height: 1.9; }
    .doc-dates strong { color: #111; }

    /* ── Cliente ── */
    .client-block {
        width: 100%;
        margin-bottom: 16pt;
        padding: 11pt 15pt;
        background: #fafafa;
        border: 0.5pt solid #eee;
        border-radius: 4pt;
    }

    .section-label {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1pt;
        color: #888;
        margin-bottom: 5pt;
    }
    .client-name   { font-size: 12.5pt; font-weight: bold; color: #111; margin-bottom: 3pt; }
    .client-detail { font-size: 8.5pt; color: #444; line-height: 1.6; }

    /* ── Meta ── */
    .meta-bar { width: 100%; margin-bottom: 14pt; }
    .meta-item { float: left; width: 25%; }
    .meta-label {
        font-size: 7pt; font-weight: bold; text-transform: uppercase;
        letter-spacing: 0.6pt; color: #888; margin-bottom: 2pt;
    }
    .meta-value { font-size: 10pt; font-weight: bold; color: #111; }

    /* ── Tabla de presupuesto ── */
    .section-title {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1pt;
        color: #888;
        margin-bottom: 6pt;
    }

    table.budget {
        width: 100%;
        border-collapse: collapse;
        font-size: 8pt;
    }
    table.budget th {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.3pt;
        color: #111;
        border-top: 1pt solid #111;
        border-bottom: 1pt solid #111;
        padding: 6pt 5pt;
        text-align: right;
        background: #fff;
    }
    table.budget th.concept { text-align: left; }
    table.budget td {
        font-size: 8pt;
        color: #222;
        padding: 5pt 5pt;
        border-bottom: 0.5pt solid #eee;
        vertical-align: top;
        background: #fff;
        text-align: right;
    }
    table.budget td.concept { text-align: left; }

    tr.section-row td {
        padding-top: 10pt;
        padding-bottom: 3pt;
        border-bottom: 0.5pt solid #ccc;
        font-size: 7.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.6pt;
        color: #666;
        background: #f7f7f7;
    }
    tr.total-row td {
        font-weight: bold;
        color: #111;
        border-top: 0.75pt solid #999;
        border-bottom: 1pt solid #111;
        background: #fafafa;
    }
    tr.grand-total-row td {
        font-size: 9.5pt;
        font-weight: bold;
        color: #111;
        border-top: 1.5pt solid #111;
        border-bottom: 1.5pt solid #111;
        padding-top: 7pt;
        padding-bottom: 7pt;
        background: #f0f0f0;
    }

    /* ── Notas ── */
    .notes-wrap { margin-top: 16pt; }
    .notes-title { font-size: 7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6pt; color: #888; margin-bottom: 4pt; }
    .notes-text  { font-size: 8.5pt; color: #444; line-height: 1.6; }

    /* ── Pie ── */
    .footer {
        margin-top: 22pt;
        padding-top: 10pt;
        border-top: 0.5pt solid #ccc;
        text-align: center;
        font-size: 7.5pt;
        color: #888;
    }
</style>
</head>
<body>
<div class="page-wrap">

    @php
        $typeLabels   = \App\Models\Budget::TYPES;
        $statusLabels = \App\Models\Budget::STATUS_LABELS;
        $periodTypes  = \App\Models\Budget::PERIOD_TYPES;
        $sl           = $statusLabels[$budget->status] ?? $statusLabels['draft'];
        $isFlujoCaja  = $budget->type === 'flujo_caja';
        $colCount     = count($periodLabels);
        $conceptWidth = 20;
        $periodWidth  = (100 - $conceptWidth) / max($colCount, 1);
    @endphp

    {{-- Cabecera --}}
    <div class="header clearfix">
        <div class="header-left">
            @if($user->logo_path && file_exists(storage_path('app/public/' . $user->logo_path)))
            <div class="logo-box">
                <img src="{{ asset('storage/' . $user->logo_path) }}" alt="Logo" />
            </div>
            @endif
        </div>
        <div class="header-right">
            <div class="doc-title">{{ strtoupper($typeLabels[$budget->type] ?? 'PRESUPUESTO') }}</div>
            <div class="doc-number">{{ $budget->name }}</div>
            <div class="doc-dates">
                <strong>Estado:</strong> {{ $sl['label'] }}<br/>
                <strong>Fecha de generación:</strong> {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}<br/>
            </div>
        </div>
    </div>

    {{-- Cliente --}}
    <div class="client-block">
        <div class="section-label">Presupuesto para</div>
        <div class="client-name">{{ $budget->client->name }}</div>
        <div class="client-detail">
            {{ $budget->client->document_type }}: {{ $budget->client->full_document }}
            @if($budget->client->address || $budget->client->city)
            &nbsp;·&nbsp;{{ implode(', ', array_filter([$budget->client->address, $budget->client->city, $budget->client->department])) }}
            @endif
            @if($budget->client->email)
            &nbsp;·&nbsp;{{ $budget->client->email }}
            @endif
        </div>
    </div>

    {{-- Meta --}}
    <div class="meta-bar clearfix">
        <div class="meta-item">
            <div class="meta-label">Año base</div>
            <div class="meta-value">{{ $budget->base_year }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Periodicidad</div>
            <div class="meta-value">{{ $periodTypes[$budget->period_type] ?? $budget->period_type }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Períodos proyectados</div>
            <div class="meta-value">{{ $budget->periods_count }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Estado</div>
            <div class="meta-value">{{ $sl['label'] }}</div>
        </div>
    </div>

    {{-- Tabla de presupuesto --}}
    <div class="section-title">Detalle por período</div>
    <table class="budget">
        <thead>
            <tr>
                <th class="concept" style="width:{{ $conceptWidth }}%">Concepto</th>
                @foreach($periodLabels as $idx => $label)
                <th style="width:{{ $periodWidth }}%">{{ $label }}{{ $idx === 0 ? ' (base)' : '' }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php $grandTotals = array_fill(0, $colCount, 0); @endphp
            @foreach($budget->sections as $section)
            <tr class="section-row">
                <td class="concept" colspan="{{ 1 + $colCount }}">{{ $section->name }}</td>
            </tr>

            @php $sectionTotals = array_fill(0, $colCount, 0); @endphp
            @foreach($section->lines as $line)
            <tr>
                <td class="concept">{{ $line->name }}</td>
                @foreach($periodLabels as $idx => $label)
                @php
                    $val = $line->getValueForPeriod($idx);
                    $signedVal = $line->sign_negative ? -$val : $val;
                    $sectionTotals[$idx] += $signedVal;
                @endphp
                <td>$ {{ number_format($val, 0, ',', '.') }}</td>
                @endforeach
            </tr>
            @endforeach

            <tr class="total-row">
                <td class="concept">Total {{ $section->name }}</td>
                @foreach($periodLabels as $idx => $label)
                @php $grandTotals[$idx] += $sectionTotals[$idx]; @endphp
                <td>$ {{ number_format($sectionTotals[$idx], 0, ',', '.') }}</td>
                @endforeach
            </tr>
            @endforeach

            @if(!$isFlujoCaja)
            <tr class="grand-total-row">
                <td class="concept">TOTAL GENERAL</td>
                @foreach($periodLabels as $idx => $label)
                <td>$ {{ number_format($grandTotals[$idx], 0, ',', '.') }}</td>
                @endforeach
            </tr>
            @endif
        </tbody>
    </table>

    {{-- Notas --}}
    @if($budget->notes)
    <div class="notes-wrap">
        <div class="notes-title">Notas</div>
        <div class="notes-text">{{ $budget->notes }}</div>
    </div>
    @endif

    {{-- Pie --}}
    <div class="footer">
        Elaborado por <strong>ALERNAL S.A.S.</strong> — Construyendo el mañana
    </div>

</div>
</body>
</html>
