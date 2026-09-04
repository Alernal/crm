<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Control de vacaciones — {{ $employee->full_name }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    @page { size: letter portrait; margin: 12mm 14mm; }

    body { font-family: Arial, Helvetica, sans-serif; font-size: 9pt; color: #111111; background: #f5f5f5; }
    .page-wrap {
        background: #ffffff; max-width: 760px; margin: 0 auto;
        min-height: 100vh; box-shadow: 0 0 24px rgba(0,0,0,.08); overflow: hidden;
    }
    @media print {
        body { background: #fff; }
        .page-wrap { max-width: none; margin: 0; box-shadow: none; }
    }

    .accent-bar { height: 3pt; background: #111111; }
    .page-inner { padding: 18pt 26pt 16pt; }
    .clearfix::after { content: ''; display: table; clear: both; }

    .header { width: 100%; padding-bottom: 9pt; margin-bottom: 11pt; border-bottom: 1pt solid #111111; }
    .header-left { width: 55%; float: left; }
    .header-right { width: 43%; float: right; text-align: right; padding-top: 1pt; }

    .party-name { font-size: 13pt; font-weight: bold; color: #111111; }
    .party-detail { font-size: 8pt; color: #555555; line-height: 1.5; margin-top: 3pt; }

    .doc-badge {
        display: inline-block; font-size: 7.5pt; font-weight: bold; text-transform: uppercase;
        letter-spacing: 0.5pt; color: #111111; background: #F0F0F0; border: 0.5pt solid #D4D4D4;
        padding: 3.5pt 9pt; border-radius: 9pt; margin-bottom: 5pt;
    }
    .doc-number { font-size: 12pt; font-weight: bold; color: #111111; margin-bottom: 4pt; }
    .doc-dates { font-size: 8pt; color: #555555; line-height: 1.6; }
    .doc-dates strong { color: #111111; }

    .info-strip {
        width: 100%; margin-bottom: 11pt; padding: 8pt 11pt;
        background: #F7F7F7; border-left: 2.5pt solid #111111; border-radius: 0 3pt 3pt 0;
    }
    .info-item { float: left; }
    .info-item.w-name { width: 34%; }
    .info-item.w-doc { width: 22%; }
    .info-item.w-role { width: 22%; }
    .info-item.w-hire { width: 22%; }
    .label { font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4pt; color: #8A8A8A; margin-bottom: 2pt; }
    .value { font-size: 9pt; color: #111111; font-weight: bold; }
    .value.muted { font-weight: normal; color: #333333; }

    .section-title {
        font-size: 7.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5pt;
        color: #111111; margin: 11pt 0 4pt; padding-bottom: 3pt; border-bottom: 1pt solid #111111;
    }

    table.concepts { width: 100%; border-collapse: collapse; }
    table.concepts th {
        font-size: 7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.2pt;
        color: #555555; border-bottom: 0.75pt solid #B5B5B5; padding: 3pt 7pt; text-align: left;
    }
    table.concepts th.right { text-align: right; }
    table.concepts td { font-size: 8.5pt; padding: 3.5pt 7pt; border-bottom: 0.5pt solid #EDEDED; }
    table.concepts tr:nth-child(even) td { background: #FAFAFA; }
    table.concepts td.right { text-align: right; }

    .net-banner { width: 100%; margin-top: 8pt; background: #111111; border-radius: 4pt; padding: 9pt 14pt; }
    .net-banner .net-label { float: left; font-size: 8.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6pt; color: #D4D4D4; padding-top: 4pt; }
    .net-banner .net-amount { float: right; font-size: 16pt; font-weight: bold; color: #ffffff; }

    .signature-single { width: 240pt; margin-top: 24pt; text-align: left; }
    .signature-line { border-top: 1pt solid #111111; margin-top: 18pt; padding-top: 5pt; font-size: 7.5pt; color: #333333; }

    .print-actions { max-width: 760px; margin: 0 auto 12px; text-align: right; }
    .print-actions button {
        font-family: Arial, sans-serif; font-size: 13px; padding: 8px 16px; border-radius: 6px;
        border: 1px solid #111111; background: #111111; color: #fff; cursor: pointer;
    }
    @media print { .print-actions { display: none; } }
</style>
</head>
<body>

<div class="print-actions"><button onclick="window.print()">Imprimir</button></div>

<div class="page-wrap">
<div class="accent-bar"></div>
<div class="page-inner">

    <div class="header clearfix">
        <div class="header-left">
            <div class="party-name">{{ $employee->client->name }}</div>
            <div class="party-detail">
                {{ $employee->client->document_type }}: {{ $employee->client->full_document }}
                @if($employee->client->address)
                <br/>{{ $employee->client->address }}{{ $employee->client->city ? ', '.$employee->client->city : '' }}
                @endif
            </div>
        </div>
        <div class="header-right">
            <div class="doc-badge">Control de vacaciones</div>
            <div class="doc-number">{{ $employee->full_name }}</div>
            <div class="doc-dates">
                <strong>Corte al:</strong> {{ now()->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <div class="info-strip clearfix">
        <div class="info-item w-name">
            <div class="label">Empleado</div>
            <div class="value">{{ $employee->full_name }}</div>
        </div>
        <div class="info-item w-doc">
            <div class="label">Documento</div>
            <div class="value muted">{{ $employee->document_type }} {{ $employee->document_number }}</div>
        </div>
        <div class="info-item w-role">
            <div class="label">Cargo</div>
            <div class="value muted">{{ $employee->position ?? '—' }}</div>
        </div>
        <div class="info-item w-hire">
            <div class="label">Ingreso</div>
            <div class="value muted">{{ $employee->hire_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
    </div>

    <div class="section-title">Saldo de vacaciones</div>
    <table class="concepts">
        <tbody>
            <tr><td>Saldo inicial (a {{ $balance['opening_date']?->format('d/m/Y') ?? '—' }})</td><td class="right">{{ number_format($balance['opening_balance'], 1) }} días</td></tr>
            <tr><td>Años cumplidos desde la fecha de referencia</td><td class="right">{{ $balance['accrued_years'] }}</td></tr>
            <tr><td>Días acumulados</td><td class="right">{{ number_format($balance['accrued_days'], 1) }} días</td></tr>
            <tr><td>Días tomados desde la fecha de referencia</td><td class="right">{{ number_format($balance['taken_days_since_opening'], 1) }} días</td></tr>
            <tr><td>Próxima fecha de acumulación</td><td class="right">{{ $balance['next_accrual_date']?->format('d/m/Y') ?? '—' }}</td></tr>
            <tr><td>Mínimo legal disfrutado este año (art. 190 CST)</td><td class="right">{{ $balance['complies_minimum_current_year'] ? 'Cumple' : 'Pendiente' }} ({{ number_format($balance['taken_days_current_year'], 1) }}/6 días)</td></tr>
        </tbody>
    </table>

    <div class="net-banner clearfix">
        <div class="net-label">Saldo pendiente</div>
        <div class="net-amount">{{ number_format($balance['pending_balance'], 1) }} días</div>
    </div>

    <div class="section-title">Historial de períodos disfrutados</div>
    @if($periods->isEmpty())
    <p style="font-size:8.5pt;color:#555555;padding:4pt 0;">Sin períodos registrados.</p>
    @else
    <table class="concepts">
        <thead><tr><th>Desde</th><th>Hasta</th><th class="right">Días hábiles</th><th>Notas</th></tr></thead>
        <tbody>
            @foreach($periods as $period)
            <tr>
                <td>{{ $period->start_date->format('d/m/Y') }}</td>
                <td>{{ $period->end_date->format('d/m/Y') }}</td>
                <td class="right">{{ number_format($period->business_days, 1) }}</td>
                <td>{{ $period->notes ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="signature-single">
        <div class="signature-line">Firma de recibido — {{ $employee->full_name }}</div>
    </div>

</div>
</div>
</body>
</html>
