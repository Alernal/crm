<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Nómina {{ $payrollPeriod->number }}</title>
<style>
    @page { size: letter landscape; margin: 14mm 16mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #111111; background: #f5f5f5; }

    .page-wrap {
        background: #ffffff; max-width: 1100px; margin: 0 auto;
        padding: 24pt 28pt; min-height: 100vh; box-shadow: 0 0 24px rgba(0,0,0,.08);
    }
    @media print {
        body { background: #fff; }
        .page-wrap { max-width: none; margin: 0; padding: 0; box-shadow: none; }
    }

    .clearfix::after { content: ''; display: table; clear: both; }

    .header { width: 100%; padding-bottom: 12pt; margin-bottom: 14pt; border-bottom: 2pt solid #111; }
    .header-left { width: 60%; float: left; }
    .header-right { width: 38%; float: right; text-align: right; }

    .company-name { font-size: 14pt; font-weight: bold; }
    .company-detail { font-size: 9pt; color: #444; margin-top: 3pt; }

    .doc-title { font-size: 15pt; font-weight: bold; margin-bottom: 4pt; }
    .doc-dates { font-size: 9pt; color: #444; line-height: 1.7; }
    .doc-dates strong { color: #111; }

    table.payroll { width: 100%; border-collapse: collapse; }
    table.payroll thead th {
        font-size: 8pt; font-weight: bold; text-transform: uppercase;
        border-top: 1pt solid #111; border-bottom: 1pt solid #111;
        padding: 6pt; text-align: right;
    }
    table.payroll thead th.name-col { text-align: left; }
    table.payroll tbody td { font-size: 9pt; padding: 5pt 6pt; border-bottom: 0.5pt solid #eee; text-align: right; }
    table.payroll tbody td.name-col { text-align: left; }
    table.payroll tfoot td { font-size: 9pt; font-weight: bold; padding: 7pt 6pt; text-align: right; border-top: 1.5pt solid #111; }
    table.payroll tfoot td.name-col { text-align: left; }

    .footer { margin-top: 18pt; padding-top: 8pt; border-top: 0.5pt solid #ccc; text-align: center; font-size: 8pt; color: #888; }

    .print-actions { max-width: 1100px; margin: 0 auto 12px; text-align: right; }
    .print-actions button {
        font-family: Arial, sans-serif; font-size: 13px; padding: 8px 16px; border-radius: 6px;
        border: 1px solid #2563EB; background: #2563EB; color: #fff; cursor: pointer;
    }
    @media print { .print-actions { display: none; } }
</style>
</head>
<body>

<div class="print-actions"><button onclick="window.print()">Imprimir</button></div>

<div class="page-wrap">

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

    <div class="footer">Elaborado por <strong>ALERNAL S.A.S.</strong> - Construyendo el manana &bull; No incluye aportes patronales ni provisión de prestaciones sociales</div>

</div>
</body>
</html>
