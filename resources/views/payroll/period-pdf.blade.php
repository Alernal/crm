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

    .page { padding: 20pt 24pt; }
    .clearfix::after { content: ''; display: table; clear: both; }

    .header {
        width: 100%;
        padding-bottom: 10pt;
        margin-bottom: 12pt;
        border-bottom: 2pt solid #111111;
    }
    .header-left { width: 60%; float: left; }
    .header-right { width: 38%; float: right; text-align: right; padding-top: 2pt; }

    .company-name { font-size: 13pt; font-weight: bold; }
    .company-detail { font-size: 8pt; color: #444444; margin-top: 2pt; }

    .doc-title { font-size: 14pt; font-weight: bold; letter-spacing: 0.4pt; margin-bottom: 3pt; }
    .doc-dates { font-size: 8pt; color: #444444; line-height: 1.7; }
    .doc-dates strong { color: #111111; }

    table.payroll { width: 100%; border-collapse: collapse; }
    table.payroll thead th {
        font-size: 7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.2pt;
        border-top: 1pt solid #111111; border-bottom: 1pt solid #111111;
        padding: 5pt 5pt; text-align: right; background: #ffffff;
    }
    table.payroll thead th.name-col { text-align: left; }
    table.payroll tbody td {
        font-size: 7.5pt; color: #222222; padding: 4pt 5pt;
        border-bottom: 0.5pt solid #eeeeee; text-align: right; background: #ffffff;
    }
    table.payroll tbody td.name-col { text-align: left; }
    table.payroll tfoot td {
        font-size: 7.5pt; font-weight: bold; padding: 5pt; text-align: right;
        border-top: 1.5pt solid #111111;
    }
    table.payroll tfoot td.name-col { text-align: left; }

    .footer {
        margin-top: 16pt; padding-top: 8pt; border-top: 0.5pt solid #cccccc;
        text-align: center; font-size: 6.5pt; color: #888888;
    }
</style>
</head>
<body>
<div class="page">

    <div class="header clearfix">
        <div class="header-left">
            <div class="company-name">{{ $payrollPeriod->client->name }}</div>
            <div class="company-detail">
                {{ $payrollPeriod->client->document_type }}: {{ $payrollPeriod->client->full_document }}
            </div>
        </div>
        <div class="header-right">
            <div class="doc-title">NÓMINA {{ $payrollPeriod->number }}</div>
            <div class="doc-dates">
                <strong>Período:</strong> {{ $payrollPeriod->start_date->format('d/m/Y') }} – {{ $payrollPeriod->end_date->format('d/m/Y') }}<br/>
                <strong>Fecha de pago:</strong> {{ $payrollPeriod->payment_date->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <table class="payroll">
        <thead>
            <tr>
                <th class="name-col" style="width:16%">Empleado</th>
                @foreach($devengoFields as $field => $label)
                <th>{{ $label }}</th>
                @endforeach
                <th>Total devengado</th>
                @foreach($descuentoFields as $field => $label)
                <th>{{ $label }}</th>
                @endforeach
                <th>Total descuentos</th>
                <th>Neto a pagar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payrollPeriod->payrolls as $payroll)
            @php $shownDeductions = collect($descuentoFields)->keys()->sum(fn ($field) => (float) $payroll->{$field}); @endphp
            <tr>
                <td class="name-col">{{ $payroll->employee->full_name }}</td>
                @foreach($devengoFields as $field => $label)
                <td>{{ $payroll->{$field} > 0 ? number_format($payroll->{$field}, 0, ',', '.') : '—' }}</td>
                @endforeach
                <td>{{ number_format($payroll->total_earned, 0, ',', '.') }}</td>
                @foreach($descuentoFields as $field => $label)
                <td>{{ $payroll->{$field} > 0 ? number_format($payroll->{$field}, 0, ',', '.') : '—' }}</td>
                @endforeach
                <td>{{ number_format($shownDeductions, 0, ',', '.') }}</td>
                <td>{{ number_format($payroll->net_pay, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            @php $totalShownDeductions = $payrollPeriod->payrolls->sum(fn ($p) => collect($descuentoFields)->keys()->sum(fn ($field) => (float) $p->{$field})); @endphp
            <tr>
                <td class="name-col">TOTALES</td>
                @foreach($devengoFields as $field => $label)
                <td>{{ number_format($payrollPeriod->payrolls->sum($field), 0, ',', '.') }}</td>
                @endforeach
                <td>{{ number_format($payrollPeriod->payrolls->sum('total_earned'), 0, ',', '.') }}</td>
                @foreach($descuentoFields as $field => $label)
                <td>{{ number_format($payrollPeriod->payrolls->sum($field), 0, ',', '.') }}</td>
                @endforeach
                <td>{{ number_format($totalShownDeductions, 0, ',', '.') }}</td>
                <td>{{ number_format($payrollPeriod->payrolls->sum('net_pay'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Elaborado por <strong>ALERNAL S.A.S.</strong> - Construyendo el manana &bull; No incluye aportes patronales ni provisión de prestaciones sociales
    </div>

</div>
</body>
</html>
