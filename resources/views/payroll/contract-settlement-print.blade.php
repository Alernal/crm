<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Liquidación de contrato — {{ $contractSettlement->employee->full_name }}</title>
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
    .info-item.w-name { width: 30%; }
    .info-item.w-doc { width: 18%; }
    .info-item.w-role { width: 20%; }
    .info-item.w-hire { width: 16%; }
    .info-item.w-end { width: 16%; }
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
    table.concepts tr.total td { font-weight: bold; color: #111111; border-top: 1pt solid #B5B5B5; border-bottom: none; padding-top: 5pt; }

    .indemnification-box {
        width: 100%; margin-top: 11pt; padding: 8pt 11pt; background: #F0F0F0;
        border: 1pt solid #B5B5B5; border-radius: 4pt;
    }
    .indemnification-box .amount { float: right; font-size: 11pt; font-weight: bold; color: #111111; }
    .indemnification-box .title { font-size: 8.5pt; font-weight: bold; color: #111111; }

    .summary-row { width: 100%; margin-top: 11pt; }
    .summary-box { width: 47.5%; padding: 7pt 10pt; border: 1pt solid #B5B5B5; border-radius: 4pt; }
    .summary-box.earned { float: left; }
    .summary-box.deductions { float: right; }
    .summary-box .label { font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt; color: #8A8A8A; margin-bottom: 3pt; }
    .summary-box .amount { font-size: 11.5pt; font-weight: bold; color: #111111; }

    .net-banner { width: 100%; margin-top: 8pt; background: #111111; border-radius: 4pt; padding: 9pt 14pt; }
    .net-banner .net-label { float: left; font-size: 8.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6pt; color: #D4D4D4; padding-top: 4pt; }
    .net-banner .net-amount { float: right; font-size: 16pt; font-weight: bold; color: #ffffff; }

    .signatures { width: 100%; margin-top: 18pt; }
    .signature-box { float: left; width: 31%; margin-right: 3.5%; text-align: left; }
    .signature-box:last-child { margin-right: 0; }
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
            <div class="party-name">{{ $contractSettlement->client->name }}</div>
            <div class="party-detail">
                {{ $contractSettlement->client->document_type }}: {{ $contractSettlement->client->full_document }}
                @if($contractSettlement->client->address)
                <br/>{{ $contractSettlement->client->address }}{{ $contractSettlement->client->city ? ', '.$contractSettlement->client->city : '' }}
                @endif
            </div>
        </div>
        <div class="header-right">
            <div class="doc-badge">Liquidación de contrato de trabajo</div>
            <div class="doc-number">{{ $contractSettlement->employee->full_name }}</div>
            <div class="doc-dates">
                <strong>Tipo de contrato:</strong> {{ \App\Models\Employee::CONTRACT_TYPES[$contractSettlement->contract_type] }}<br/>
                <strong>Fecha de terminación:</strong> {{ $contractSettlement->contract_end_date->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <div class="info-strip clearfix">
        <div class="info-item w-name">
            <div class="label">Empleado</div>
            <div class="value">{{ $contractSettlement->employee->full_name }}</div>
        </div>
        <div class="info-item w-doc">
            <div class="label">Documento</div>
            <div class="value muted">{{ $contractSettlement->employee->document_type }} {{ $contractSettlement->employee->document_number }}</div>
        </div>
        <div class="info-item w-role">
            <div class="label">Cargo</div>
            <div class="value muted">{{ $contractSettlement->employee->position ?? '—' }}</div>
        </div>
        <div class="info-item w-hire">
            <div class="label">Ingreso</div>
            <div class="value muted">{{ $contractSettlement->employee->hire_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <div class="info-item w-end">
            <div class="label">Retiro</div>
            <div class="value muted">{{ $contractSettlement->contract_end_date->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="section-title">Prestaciones sociales pendientes</div>
    <table class="concepts">
        <thead><tr><th>Concepto</th><th class="right">Días</th><th class="right" style="width:26%">Valor</th></tr></thead>
        <tbody>
            <tr><td>Prima de servicios</td><td class="right">{{ number_format($contractSettlement->prima_days, 0) }}</td><td class="right">$ {{ number_format($contractSettlement->prima_value, 0, ',', '.') }}</td></tr>
            <tr><td>Cesantías</td><td class="right">{{ number_format($contractSettlement->cesantias_days, 0) }}</td><td class="right">$ {{ number_format($contractSettlement->cesantias_value, 0, ',', '.') }}</td></tr>
            <tr><td>Intereses a las cesantías</td><td class="right">{{ number_format($contractSettlement->cesantias_days, 0) }}</td><td class="right">$ {{ number_format($contractSettlement->interest_cesantias_value, 0, ',', '.') }}</td></tr>
            <tr><td>Vacaciones</td><td class="right">{{ number_format($contractSettlement->vacation_days, 0) }}</td><td class="right">$ {{ number_format($contractSettlement->vacation_value, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <div class="section-title">Pagos del último período laborado</div>
    <table class="concepts">
        <thead><tr><th>Concepto</th><th class="right" style="width:30%">Valor</th></tr></thead>
        <tbody>
            <tr><td>Salario días laborados</td><td class="right">$ {{ number_format($contractSettlement->basic_salary_pay, 0, ',', '.') }}</td></tr>
            @if($contractSettlement->overtime_value > 0)<tr><td>Horas extra</td><td class="right">$ {{ number_format($contractSettlement->overtime_value, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->recargos_value > 0)<tr><td>Recargos</td><td class="right">$ {{ number_format($contractSettlement->recargos_value, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->commissions > 0)<tr><td>Comisiones</td><td class="right">$ {{ number_format($contractSettlement->commissions, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->bonuses_salarial > 0)<tr><td>Bonificaciones salariales</td><td class="right">$ {{ number_format($contractSettlement->bonuses_salarial, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->per_diem_salarial > 0)<tr><td>Viáticos permanentes</td><td class="right">$ {{ number_format($contractSettlement->per_diem_salarial, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->other_salarial > 0)<tr><td>Otros pagos salariales</td><td class="right">$ {{ number_format($contractSettlement->other_salarial, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->occasional_bonuses > 0)<tr><td>Bonificaciones ocasionales</td><td class="right">$ {{ number_format($contractSettlement->occasional_bonuses, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->extralegal_premiums > 0)<tr><td>Primas, beneficios o auxilios extralegales</td><td class="right">$ {{ number_format($contractSettlement->extralegal_premiums, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->per_diem_no_salarial > 0)<tr><td>Viáticos (no salariales)</td><td class="right">$ {{ number_format($contractSettlement->per_diem_no_salarial, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->transport_allowance_value > 0)<tr><td>Auxilio de transporte</td><td class="right">$ {{ number_format($contractSettlement->transport_allowance_value, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->other_no_salarial > 0)<tr><td>Otros pagos no salariales</td><td class="right">$ {{ number_format($contractSettlement->other_no_salarial, 0, ',', '.') }}</td></tr>@endif
        </tbody>
    </table>

    @if($contractSettlement->indemnification_value > 0)
    <div class="indemnification-box clearfix">
        <span class="title">Indemnización por despido sin justa causa</span>
        <span class="amount">$ {{ number_format($contractSettlement->indemnification_value, 0, ',', '.') }}</span>
    </div>
    @endif

    <div class="section-title">Deducciones</div>
    <table class="concepts">
        <thead><tr><th>Concepto</th><th class="right" style="width:30%">Valor</th></tr></thead>
        <tbody>
            <tr><td>Salud</td><td class="right">$ {{ number_format($contractSettlement->health_employee, 0, ',', '.') }}</td></tr>
            @if($contractSettlement->pension_employee > 0)<tr><td>Pensión</td><td class="right">$ {{ number_format($contractSettlement->pension_employee, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->fsp_employee > 0)<tr><td>Fondo de solidaridad pensional</td><td class="right">$ {{ number_format($contractSettlement->fsp_employee, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->withholding_tax > 0)<tr><td>Retención en la fuente</td><td class="right">$ {{ number_format($contractSettlement->withholding_tax, 0, ',', '.') }}</td></tr>@endif
            @if($contractSettlement->other_deductions > 0)<tr><td>Otros valores a deducir</td><td class="right">$ {{ number_format($contractSettlement->other_deductions, 0, ',', '.') }}</td></tr>@endif
            <tr class="total"><td>Total deducciones</td><td class="right">$ {{ number_format($contractSettlement->total_deductions, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <div class="summary-row clearfix">
        <div class="summary-box earned">
            <div class="label">Total a pagar</div>
            <div class="amount">$ {{ number_format($contractSettlement->total_to_pay, 0, ',', '.') }}</div>
        </div>
        <div class="summary-box deductions">
            <div class="label">Total deducciones</div>
            <div class="amount">$ {{ number_format($contractSettlement->total_deductions, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="net-banner clearfix">
        <div class="net-label">Neto a pagar</div>
        <div class="net-amount">$ {{ number_format($contractSettlement->net_pay, 0, ',', '.') }} COP</div>
    </div>

    <div class="signatures clearfix">
        <div class="signature-box">
            <div class="signature-line">Firma de recibido — {{ $contractSettlement->employee->full_name }}</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Elaborado por</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Revisado por</div>
        </div>
    </div>

</div>
</div>
</body>
</html>
