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
        font-size: 8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.2pt;
        border-top: 1pt solid #111111; border-bottom: 1pt solid #111111;
        padding: 6pt; text-align: right; background: #ffffff;
    }
    table.payroll thead th.name-col { text-align: left; }
    table.payroll tbody td {
        font-size: 8.5pt; color: #222222; padding: 5pt 6pt;
        border-bottom: 0.5pt solid #eeeeee; text-align: right; background: #ffffff;
    }
    table.payroll tbody td.name-col { text-align: left; }
    table.payroll tfoot td {
        font-size: 8.5pt; font-weight: bold; padding: 7pt 6pt; text-align: right;
        border-top: 1.5pt solid #111111;
    }
    table.payroll tfoot td.name-col { text-align: left; }

    .footer {
        margin-top: 16pt; padding-top: 8pt; border-top: 0.5pt solid #cccccc;
        text-align: center; font-size: 7pt; color: #888888;
    }
</style>
</head>
<body>
<div class="page">

    <div class="header clearfix">
        <div class="header-left">
            <div class="company-name">{{ $cesantiaSettlement->client->name }}</div>
            <div class="company-detail">
                {{ $cesantiaSettlement->client->document_type }}: {{ $cesantiaSettlement->client->full_document }}
            </div>
        </div>
        <div class="header-right">
            <div class="doc-title">LIQUIDACIÓN DE CESANTÍAS</div>
            <div class="doc-dates">
                <strong>Año:</strong> {{ $cesantiaSettlement->year }}<br/>
                <strong>Fecha de pago:</strong> {{ $cesantiaSettlement->payment_date->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <table class="payroll">
        <thead>
            <tr>
                <th class="name-col" style="width:40%">Empleado</th>
                <th>Días</th>
                <th>Cesantías</th>
                <th>Intereses</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cesantiaSettlement->items as $item)
            <tr>
                <td class="name-col">{{ $item->employee->full_name }}</td>
                <td>{{ number_format($item->worked_days, 0, ',', '.') }}</td>
                <td>{{ number_format($item->cesantias_value, 0, ',', '.') }}</td>
                <td>{{ number_format($item->interest_value, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="name-col">TOTALES</td>
                <td>{{ number_format($cesantiaSettlement->items->sum('worked_days'), 0, ',', '.') }}</td>
                <td>{{ number_format($cesantiaSettlement->total_cesantias, 0, ',', '.') }}</td>
                <td>{{ number_format($cesantiaSettlement->total_interest, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Elaborado por <strong>ALERNAL S.A.S.</strong> - Construyendo el manana &bull; Valores calculados a partir de las provisiones mensuales de las nóminas del período
    </div>

</div>
</body>
</html>
