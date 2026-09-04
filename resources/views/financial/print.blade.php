<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>{{ in_array($budget->type, ['esf', 'eri'], true) ? $budget->name : 'Presupuesto ' . $budget->name }}</title>
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
    .header-right { width: 100%; text-align: right; padding-top: 4pt; }

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

    /* ── Estados Financieros: tabla minimalista, sin líneas — jerarquía solo por negrita/tamaño ── */
    .stmt-doc .header { border-bottom: none; padding-bottom: 6pt; }

    table.stmt {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5pt;
    }
    table.stmt th {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5pt;
        color: #999;
        padding: 0 5pt 9pt 5pt;
        text-align: right;
        border: none;
        background: none;
    }
    table.stmt th.concept { text-align: left; }
    table.stmt td {
        font-size: 8.5pt;
        font-weight: normal;
        color: #333;
        padding: 4pt 5pt;
        text-align: right;
        border: none;
        background: none;
        vertical-align: top;
    }
    table.stmt td.concept { text-align: left; padding-left: 14pt; }

    tr.stmt-group td {
        padding-top: 17pt;
        padding-bottom: 3pt;
        font-size: 8pt;
        font-weight: bold;
        text-transform: uppercase;
        color: #111;
        border: none;
        background: none;
    }
    tr.stmt-group td.concept { padding-left: 0; }

    tr.stmt-section td {
        padding-top: 9pt;
        padding-bottom: 2pt;
        font-size: 7.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.6pt;
        color: #777;
        border: none;
        background: none;
    }
    tr.stmt-section td.concept { padding-left: 0; }

    tr.stmt-total td {
        font-weight: bold;
        font-size: 8.5pt;
        color: #111;
        padding-top: 6pt;
        padding-bottom: 6pt;
        border: none;
        background: none;
    }
    tr.stmt-total td.concept { padding-left: 0; }

    tr.stmt-highlight td {
        font-weight: bold;
        font-size: 10.5pt;
        color: #111;
        padding-top: 8pt;
        padding-bottom: 8pt;
        border: none;
        background: none;
    }
    tr.stmt-highlight td.concept { padding-left: 0; }

    /* ── Flujo de caja: Ppto/Real/Var% ── */
    table.cashflow { font-size: 7pt; }
    table.cashflow th, table.cashflow td { font-size: 7pt; padding: 4pt 3pt; }
    table.cashflow th.period-group { text-align: center; border-left: 0.5pt solid #ccc; }
    table.cashflow th.sub { font-size: 6.5pt; text-align: right; border-left: 0.5pt solid #eee; border-top: none; }
    table.cashflow td.real { color: #059669; }
    table.cashflow td.var-pos { color: #059669; }
    table.cashflow td.var-neg { color: #DC2626; }
    table.cashflow td { border-left: 0.5pt solid #f3f3f3; }
    td.neg, table.budget td.neg { color: #DC2626; }

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

    /* ── Firmas (Estados Financieros) ── */
    .signatures {
        width: 100%;
        margin-top: 36pt;
    }
    .signature-box {
        float: left;
        width: 46%;
        text-align: center;
    }
    .signature-box + .signature-box {
        float: right;
    }
    .signature-line {
        border-top: 0.75pt solid #111;
        margin-bottom: 5pt;
        padding-top: 5pt;
    }
    .signature-name {
        font-size: 9pt;
        font-weight: bold;
        color: #111;
    }
    .signature-detail {
        font-size: 8pt;
        color: #666;
        margin-top: 1pt;
    }
    .signature-role {
        font-size: 7pt;
        text-transform: uppercase;
        letter-spacing: 0.6pt;
        color: #888;
        margin-top: 3pt;
    }
</style>
</head>
<body>

@php
    $typeLabels   = \App\Models\Budget::TYPES;
    $statusLabels = \App\Models\Budget::STATUS_LABELS;
    $periodTypes  = \App\Models\Budget::PERIOD_TYPES;
    $sl           = $statusLabels[$budget->status] ?? $statusLabels['draft'];
    $isFlujoCaja  = $budget->type === 'flujo_caja';
    $isEsf        = $budget->type === 'esf' && $esfReport !== null;
    $isEri        = $budget->type === 'eri' && $eriReport !== null;
    $isStatement  = $isEsf || $isEri;
    $colCount     = count($periodLabels);
    $conceptWidth = $isStatement ? max(38, 60 - ($colCount * 4)) : 20;
    $periodWidth  = (100 - $conceptWidth) / max($colCount, 1);
    $fmtNumber  = fn ($v) => number_format($v, 0, ',', '.');
    $signerRole   = 'Representante Legal';
    // Identificación PERSONAL del contador que firma — siempre cédula/CE/pasaporte,
    // nunca NIT (un NIT es un registro tributario, no un documento de identidad).
    // Mismo criterio que CertificateSignatureClauseResolver/CompanyPlaceholderProvider.
    // Abreviatura ("C.C.") para la firma, igual que en Contratos/Propuestas/Certificados.
    $accountantIdType   = \App\Services\DocumentEngine\Providers\ClientPlaceholderProvider::documentTypeAbbreviation($user->identification_type ?: 'CC');
    $accountantIdNumber = $user->identification_number ?: $user->nit;
@endphp

<div class="page-wrap {{ $isStatement ? 'stmt-doc' : '' }}">

    {{-- Cabecera — sin logo a propósito, ver `financial/pdf.blade.php` --}}
    <div class="header clearfix">
        <div class="header-right">
            @if($isStatement)
            <div class="doc-title">{{ $isEsf ? 'ESTADO DE SITUACIÓN FINANCIERA' : 'ESTADO DE RESULTADOS' }}</div>
            <div class="doc-number">{{ $budget->client->name }}</div>
            <div class="doc-dates">
                {{ $budget->client->document_type }}: {{ $budget->client->full_document }}<br/>
                A corte {{ ($budget->cutoff_date ?? $budget->periodEndDate($budget->periods_count))->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}<br/>
                Cifras expresadas en pesos colombianos (COP)
            </div>
            @else
            <div class="doc-title">{{ strtoupper($typeLabels[$budget->type] ?? 'PRESUPUESTO') }}</div>
            <div class="doc-number">{{ $budget->name }}</div>
            <div class="doc-dates">
                <strong>Estado:</strong> {{ $sl['label'] }}<br/>
                <strong>Fecha de generación:</strong> {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}<br/>
            </div>
            @endif
        </div>
    </div>

    @unless($isStatement)
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
            <div class="meta-label">Período base</div>
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
    @endunless

    @if($isFlujoCaja && $cashFlowReport)
    @php $cfConceptWidth = 16; $cfPeriodWidth = (100 - $cfConceptWidth) / max($colCount, 1); @endphp
    <table class="budget cashflow">
        <thead>
            <tr>
                <th class="concept" rowspan="2" style="width:{{ $cfConceptWidth }}%">Concepto</th>
                @foreach($periodLabels as $idx => $label)
                <th class="period-group" colspan="3" style="width:{{ $cfPeriodWidth }}%">{{ $label }}{{ $idx === 0 ? ' (base)' : '' }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach($periodLabels as $idx => $label)
                <th class="sub">Ppto</th>
                <th class="sub">Real</th>
                <th class="sub">Var%</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($cashFlowReport['rows'] as $row)
                @if($row['type'] === 'section')
                <tr class="section-row">
                    <td class="concept" colspan="{{ 1 + ($colCount * 3) }}">{{ $row['label'] }}</td>
                </tr>
                @else
                @php
                    $rowClass  = $row['type'] === 'total' ? 'total-row' : (in_array($row['type'], ['highlight', 'final'], true) ? 'grand-total-row' : '');
                    $isOutflow = $row['is_outflow'] ?? false;
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="concept">{{ $row['label'] }}</td>
                    @foreach($periodLabels as $idx => $label)
                    @php
                        $ppto = $row['values'][$idx]['ppto'] ?? 0.0;
                        $real = $row['values'][$idx]['real'] ?? 0.0;
                        $var  = $ppto != 0.0 ? (($real - $ppto) / abs($ppto)) * 100 : null;
                    @endphp
                    <td class="{{ $isOutflow ? 'neg' : '' }}">{{ $ppto == 0 ? '—' : ($isOutflow ? '-$ ' : '$ ') . number_format(abs($ppto), 0, ',', '.') }}</td>
                    <td class="{{ $isOutflow ? 'neg' : 'real' }}">{{ $real == 0 ? '—' : ($isOutflow ? '-$ ' : '$ ') . number_format(abs($real), 0, ',', '.') }}</td>
                    <td class="{{ $var === null ? '' : ($var >= 0 ? 'var-pos' : 'var-neg') }}">{{ $var === null ? '—' : number_format($var, 1) . '%' }}</td>
                    @endforeach
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>
    @elseif($isEsf || $isEri)
    @php
        $statementReport = $isEsf ? $esfReport : $eriReport;
        $visibleRows      = \App\Models\Budget::filterStatementRowsForPrint($statementReport['rows'], $isEri);
    @endphp
    <table class="stmt">
        <thead>
            <tr>
                <th class="concept" style="width:{{ $conceptWidth }}%">Concepto</th>
                @foreach($periodLabels as $idx => $label)
                <th style="width:{{ $periodWidth }}%">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($visibleRows as $row)
                @if($row['type'] === 'group')
                <tr class="stmt-group">
                    <td class="concept" colspan="{{ 1 + $colCount }}">{{ $row['label'] }}</td>
                </tr>
                @elseif($row['type'] === 'section')
                <tr class="stmt-section">
                    <td class="concept" colspan="{{ 1 + $colCount }}">{{ $row['label'] }}</td>
                </tr>
                @else
                @php $rowClass = $row['type'] === 'highlight' ? 'stmt-highlight' : ($row['type'] === 'total' ? 'stmt-total' : ''); @endphp
                <tr class="{{ $rowClass }}">
                    <td class="concept">{{ $row['label'] }}</td>
                    @foreach($periodLabels as $idx => $label)
                    @php $v = $row['values'][$idx] ?? 0; @endphp
                    <td class="{{ $v < 0 ? 'neg' : '' }}">{{ $v < 0 ? '-$ ' : '$ ' }}{{ $fmtNumber(abs($v)) }}</td>
                    @endforeach
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>
    @else
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
                <td class="{{ $line->sign_negative ? 'neg' : '' }}">{{ $line->sign_negative ? '-$ ' : '$ ' }}{{ number_format($val, 0, ',', '.') }}</td>
                @endforeach
            </tr>
            @endforeach

            <tr class="total-row">
                <td class="concept">Total {{ $section->name }}</td>
                @foreach($periodLabels as $idx => $label)
                @php $grandTotals[$idx] += $sectionTotals[$idx]; @endphp
                <td class="{{ $sectionTotals[$idx] < 0 ? 'neg' : '' }}">{{ $sectionTotals[$idx] < 0 ? '-$ ' : '$ ' }}{{ number_format(abs($sectionTotals[$idx]), 0, ',', '.') }}</td>
                @endforeach
            </tr>
            @endforeach

            <tr class="grand-total-row">
                <td class="concept">TOTAL GENERAL</td>
                @foreach($periodLabels as $idx => $label)
                <td class="{{ $grandTotals[$idx] < 0 ? 'neg' : '' }}">{{ $grandTotals[$idx] < 0 ? '-$ ' : '$ ' }}{{ number_format(abs($grandTotals[$idx]), 0, ',', '.') }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
    @endif

    {{-- Notas --}}
    @if($budget->notes)
    <div class="notes-wrap">
        <div class="notes-title">Notas</div>
        <div class="notes-text">{{ $budget->notes }}</div>
    </div>
    @endif

    {{-- Firmas (Estados Financieros) --}}
    @if($isEsf || $isEri)
    <div class="signatures clearfix">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">{{ $budget->client->name }}</div>
            <div class="signature-detail">{{ $budget->client->document_type }}: {{ $budget->client->full_document }}</div>
            <div class="signature-role">{{ $signerRole }}</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">{{ $user->name }}</div>
            @if($accountantIdNumber)
            <div class="signature-detail">{{ $accountantIdType }} No. {{ $accountantIdNumber }}</div>
            @endif
            @if($user->professional_card_number)
            <div class="signature-detail">T.P. {{ $user->professional_card_number }}</div>
            @endif
            <div class="signature-role">Contador Público</div>
        </div>
    </div>
    @endif

    {{-- Pie --}}
    @unless($isStatement)
    <div class="footer">
        Elaborado por <strong>ALERNAL S.A.S.</strong> — Construyendo el mañana
    </div>
    @endunless

</div>
</body>
</html>
