<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Helvetica, Arial, sans-serif;
        font-size: 8pt;
        color: #111111;
        background: #ffffff;
    }

    .accent-bar { height: 3pt; background: #111111; }

    .page { padding: 16pt 26pt 14pt 26pt; }
    .clearfix::after { content: ''; display: table; clear: both; }

    .header { width: 100%; padding-bottom: 8pt; margin-bottom: 10pt; border-bottom: 1pt solid #111111; }
    .header-left { width: 55%; float: left; }
    .header-right { width: 43%; float: right; text-align: right; padding-top: 1pt; }

    .party-name { font-size: 12pt; font-weight: bold; color: #111111; margin-bottom: 2pt; }
    .party-detail { font-size: 7.5pt; color: #555555; line-height: 1.5; }

    .doc-badge {
        display: inline-block; font-size: 7pt; font-weight: bold; text-transform: uppercase;
        letter-spacing: 0.5pt; color: #111111; background: #F0F0F0; border: 0.5pt solid #D4D4D4;
        padding: 3pt 7pt; border-radius: 8pt; margin-bottom: 4pt;
    }
    .doc-number { font-size: 11pt; font-weight: bold; color: #111111; margin-bottom: 3pt; }
    .doc-dates { font-size: 7.5pt; color: #555555; line-height: 1.55; }
    .doc-dates strong { color: #111111; font-weight: bold; }

    .info-strip {
        width: 100%; margin-bottom: 10pt; padding: 7pt 10pt;
        background: #F7F7F7; border-left: 2pt solid #111111;
    }
    .info-item { float: left; }
    .info-item.w-name { width: 34%; }
    .info-item.w-doc { width: 22%; }
    .info-item.w-role { width: 22%; }
    .info-item.w-hire { width: 22%; }
    .label { font-size: 6pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4pt; color: #8A8A8A; margin-bottom: 1.5pt; }
    .value { font-size: 8pt; color: #111111; font-weight: bold; }
    .value.muted { font-weight: normal; color: #333333; }

    .section-title {
        font-size: 7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5pt;
        color: #111111; margin: 9pt 0 3pt; padding-bottom: 2.5pt; border-bottom: 1pt solid #111111;
    }

    table.concepts { width: 100%; border-collapse: collapse; }
    table.concepts th {
        font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.2pt;
        color: #555555; border-bottom: 0.75pt solid #B5B5B5;
        padding: 2.5pt 5pt; text-align: left; background: #ffffff;
    }
    table.concepts th.right { text-align: right; }
    table.concepts td {
        font-size: 7.8pt; color: #222222; padding: 2.7pt 5pt;
        border-bottom: 0.5pt solid #EDEDED; background: #ffffff;
    }
    table.concepts tr:nth-child(even) td { background: #FAFAFA; }
    table.concepts td.right { text-align: right; }

    .summary-row { width: 100%; margin-top: 9pt; }
    .summary-box {
        width: 47%; padding: 6pt 9pt; border: 0.75pt solid #B5B5B5; border-radius: 3pt;
    }
    .summary-box.left { float: left; }
    .summary-box.right { float: right; }
    .summary-box .label { font-size: 6pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt; color: #8A8A8A; margin-bottom: 2pt; }
    .summary-box .amount { font-size: 10pt; font-weight: bold; color: #111111; }

    .net-banner {
        width: 100%; margin-top: 7pt; background: #111111; border-radius: 3pt;
        padding: 8pt 12pt;
    }
    .net-banner .net-label {
        float: left; font-size: 8pt; font-weight: bold; text-transform: uppercase;
        letter-spacing: 0.6pt; color: #D4D4D4; padding-top: 3pt;
    }
    .net-banner .net-amount { float: right; font-size: 14pt; font-weight: bold; color: #ffffff; }

    .signature-single { width: 220pt; margin-top: 22pt; text-align: left; }
    .signature-line { border-top: 0.75pt solid #111111; margin-top: 16pt; padding-top: 4pt; font-size: 7pt; color: #333333; }
</style>
</head>
<body>
<div class="accent-bar"></div>
<div class="page">

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
    <p style="font-size:7.8pt;color:#555555;padding:4pt 0;">Sin períodos registrados.</p>
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
</body>
</html>
