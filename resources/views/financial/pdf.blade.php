<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Helvetica, Arial, sans-serif;
        font-size: 9pt;
        color: #111111;
        background: #ffffff;
    }

    .page {
        padding: 24pt 28pt 24pt 28pt;
    }

    .clearfix::after { content: ''; display: table; clear: both; }

    /* ── Cabecera ── */
    .header {
        width: 100%;
        padding-bottom: 14pt;
        margin-bottom: 14pt;
        border-bottom: 2pt solid #111111;
    }
    .header-left {
        width: 38%;
        float: left;
    }
    .header-right {
        width: 60%;
        float: right;
        text-align: right;
        padding-top: 4pt;
    }

    .logo-box {
        width: 120pt;
        height: 60pt;
        border: 1pt solid #dddddd;
        background: #fafafa;
        text-align: center;
        line-height: 60pt;
        font-size: 0;
        overflow: hidden;
    }
    .logo-box img {
        display: inline-block;
        vertical-align: middle;
        max-width: 114pt;
        max-height: 54pt;
        line-height: normal;
    }
    .logo-empty {
        width: 120pt;
        height: 60pt;
    }

    .doc-title {
        font-size: 17pt;
        font-weight: bold;
        color: #111111;
        letter-spacing: 0.5pt;
        margin-bottom: 3pt;
    }
    .doc-number {
        font-size: 10pt;
        font-weight: bold;
        color: #444444;
        margin-bottom: 8pt;
    }
    .doc-dates {
        font-size: 8pt;
        color: #444444;
        line-height: 1.8;
    }
    .doc-dates strong { color: #111111; }

    /* ── Cliente ── */
    .client-block {
        width: 100%;
        margin-bottom: 16pt;
        padding: 10pt 14pt;
        background: #fafafa;
        border: 0.5pt solid #eeeeee;
        border-radius: 4pt;
    }
    .section-label {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.8pt;
        color: #888888;
        margin-bottom: 5pt;
    }
    .client-name {
        font-size: 12pt;
        font-weight: bold;
        color: #111111;
        margin-bottom: 3pt;
    }
    .client-detail {
        font-size: 8pt;
        color: #444444;
        line-height: 1.5;
    }

    /* ── Meta ── */
    .meta-bar {
        width: 100%;
        margin-bottom: 14pt;
    }
    .meta-item {
        float: left;
        width: 25%;
    }
    .meta-label {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.6pt;
        color: #888888;
        margin-bottom: 2pt;
    }
    .meta-value {
        font-size: 9.5pt;
        font-weight: bold;
        color: #111111;
    }

    /* ── Tabla de presupuesto ── */
    .section-title {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.8pt;
        color: #888888;
        margin-bottom: 6pt;
    }
    .budget-table {
        width: 100%;
        border-collapse: collapse;
    }
    .budget-table th {
        font-size: 6.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.3pt;
        color: #111111;
        border-top: 1pt solid #111111;
        border-bottom: 1pt solid #111111;
        padding: 5pt 4pt;
        text-align: right;
        background: #ffffff;
    }
    .budget-table th.concept { text-align: left; }
    .budget-table td {
        font-size: 7.5pt;
        font-weight: normal;
        color: #222222;
        padding: 4pt 4pt;
        border-bottom: 0.5pt solid #eeeeee;
        vertical-align: top;
        background: #ffffff;
        text-align: right;
    }
    .budget-table td.concept { text-align: left; }

    .section-row td {
        padding-top: 9pt;
        padding-bottom: 2pt;
        border-bottom: 0.5pt solid #cccccc;
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.6pt;
        color: #666666;
        background: #f7f7f7;
    }
    .total-row td {
        font-weight: bold;
        color: #111111;
        border-top: 0.75pt solid #999999;
        border-bottom: 1pt solid #111111;
        background: #fafafa;
    }
    .grand-total-row td {
        font-size: 8.5pt;
        font-weight: bold;
        color: #111111;
        border-top: 1.5pt solid #111111;
        border-bottom: 1.5pt solid #111111;
        padding-top: 6pt;
        padding-bottom: 6pt;
        background: #f0f0f0;
    }

    /* ── Notas ── */
    .notes-wrap { margin-top: 14pt; }
    .notes-title {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.6pt;
        color: #888888;
        margin-bottom: 4pt;
    }
    .notes-text {
        font-size: 8pt;
        color: #444444;
        line-height: 1.6;
    }

    /* ── Pie ── */
    .footer {
        margin-top: 20pt;
        padding-top: 10pt;
        border-top: 0.5pt solid #cccccc;
        text-align: center;
        font-size: 7pt;
        color: #888888;
        line-height: 1.6;
    }
</style>
</head>
<body>
<div class="page">

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

    {{-- ===== CABECERA ===== --}}
    <div class="header clearfix">
        <div class="header-left">
            @if($user->logo_path && file_exists(storage_path('app/public/' . $user->logo_path)))
            <div class="logo-box">
                <img src="{{ storage_path('app/public/' . $user->logo_path) }}" alt="Logo" />
            </div>
            @else
            <div class="logo-empty"></div>
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

    {{-- ===== CLIENTE ===== --}}
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

    {{-- ===== META ===== --}}
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

    {{-- ===== TABLA DE PRESUPUESTO ===== --}}
    <div class="section-title">Detalle por período</div>
    <table class="budget-table">
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

    {{-- ===== NOTAS ===== --}}
    @if($budget->notes)
    <div class="notes-wrap">
        <div class="notes-title">Notas</div>
        <div class="notes-text">{{ $budget->notes }}</div>
    </div>
    @endif

    {{-- ===== PIE ===== --}}
    <div class="footer">
        Elaborado por <strong>ALERNAL S.A.S.</strong> - Construyendo el manana
    </div>

</div>
</body>
</html>
