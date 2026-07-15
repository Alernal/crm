<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Desprendible {{ $payroll->payrollPeriod->number }} — {{ $payroll->employee->full_name }}</title>
<style>
    @page { size: letter portrait; margin: 16mm 18mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #1F2937; background: #f5f5f5; }

    .page-wrap {
        background: #ffffff; max-width: 760px; margin: 0 auto;
        min-height: 100vh; box-shadow: 0 0 24px rgba(0,0,0,.08); overflow: hidden;
    }
    @media print {
        body { background: #fff; }
        .page-wrap { max-width: none; margin: 0; box-shadow: none; }
    }

    .accent-bar { height: 5pt; background: #2563EB; }
    .page-inner { padding: 28pt 34pt 30pt; }

    .clearfix::after { content: ''; display: table; clear: both; }

    .header { width: 100%; padding-bottom: 14pt; margin-bottom: 18pt; border-bottom: 1pt solid #E5E7EB; }
    .header-left { width: 55%; float: left; }
    .header-right { width: 43%; float: right; text-align: right; padding-top: 2pt; }

    .party-name { font-size: 15pt; font-weight: bold; color: #111827; }
    .party-detail { font-size: 9pt; color: #6B7280; line-height: 1.65; margin-top: 4pt; }

    .doc-badge {
        display: inline-block; font-size: 8pt; font-weight: bold; text-transform: uppercase;
        letter-spacing: 0.6pt; color: #2563EB; background: #EFF6FF;
        padding: 4pt 10pt; border-radius: 10pt; margin-bottom: 7pt;
    }
    .doc-number { font-size: 13pt; font-weight: bold; color: #111827; margin-bottom: 6pt; }
    .doc-dates { font-size: 9pt; color: #6B7280; line-height: 1.8; }
    .doc-dates strong { color: #111827; }

    .info-strip {
        width: 100%; margin-bottom: 18pt; padding: 12pt 14pt;
        background: #F9FAFB; border-left: 3pt solid #2563EB; border-radius: 0 4pt 4pt 0;
    }
    .info-item { float: left; }
    .info-item.w-name { width: 36%; }
    .info-item.w-doc { width: 20%; }
    .info-item.w-role { width: 26%; }
    .info-item.w-days { width: 18%; }
    .label { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5pt; color: #9CA3AF; margin-bottom: 3pt; }
    .value { font-size: 10pt; color: #111827; font-weight: bold; }
    .value.muted { font-weight: normal; color: #374151; }

    .section-title {
        font-size: 8.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6pt;
        color: #111827; margin: 18pt 0 8pt; padding-bottom: 5pt; border-bottom: 1.5pt solid #2563EB;
    }

    table.concepts { width: 100%; border-collapse: collapse; }
    table.concepts th {
        font-size: 8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt;
        color: #6B7280; border-bottom: 1pt solid #D1D5DB; padding: 5pt 8pt; text-align: left;
    }
    table.concepts th.right { text-align: right; }
    table.concepts td { font-size: 9.5pt; padding: 5.5pt 8pt; border-bottom: 0.5pt solid #F3F4F6; }
    table.concepts tr:nth-child(even) td { background: #FAFBFC; }
    table.concepts td.right { text-align: right; }
    table.concepts tr.total td { font-weight: bold; color: #111827; border-top: 1pt solid #D1D5DB; border-bottom: none; padding-top: 7pt; }

    .summary-row { width: 100%; margin-top: 18pt; }
    .summary-box { width: 47.5%; padding: 10pt 12pt; border: 1pt solid #E5E7EB; border-radius: 6pt; }
    .summary-box.earned { float: left; }
    .summary-box.deductions { float: right; }
    .summary-box .label { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4pt; color: #9CA3AF; margin-bottom: 4pt; }
    .summary-box .amount { font-size: 13pt; font-weight: bold; }
    .summary-box.earned .amount { color: #059669; }
    .summary-box.deductions .amount { color: #DC2626; }

    .net-banner { width: 100%; margin-top: 12pt; background: #1E3A8A; border-radius: 8pt; padding: 14pt 18pt; }
    .net-banner .net-label { float: left; font-size: 10pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8pt; color: #DBEAFE; padding-top: 6pt; }
    .net-banner .net-amount { float: right; font-size: 20pt; font-weight: bold; color: #ffffff; }

    .footer { margin-top: 24pt; padding-top: 12pt; border-top: 0.5pt solid #E5E7EB; text-align: center; font-size: 8pt; color: #9CA3AF; line-height: 1.7; }

    .print-actions { max-width: 760px; margin: 0 auto 12px; text-align: right; }
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
<div class="accent-bar"></div>
<div class="page-inner">

    <div class="header clearfix">
        <div class="header-left">
            <div class="party-name">{{ $payroll->client->name }}</div>
            <div class="party-detail">
                {{ $payroll->client->document_type }}: {{ $payroll->client->full_document }}
                @if($payroll->client->address)
                <br/>{{ $payroll->client->address }}{{ $payroll->client->city ? ', '.$payroll->client->city : '' }}
                @endif
            </div>
        </div>
        <div class="header-right">
            <div class="doc-badge">Comprobante de pago de nómina</div>
            <div class="doc-number">{{ $payroll->payrollPeriod->number }}</div>
            <div class="doc-dates">
                <strong>Período:</strong> {{ $payroll->payrollPeriod->start_date->format('d/m/Y') }} – {{ $payroll->payrollPeriod->end_date->format('d/m/Y') }}<br/>
                <strong>Fecha de pago:</strong> {{ $payroll->payrollPeriod->payment_date->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <div class="info-strip clearfix">
        <div class="info-item w-name">
            <div class="label">Empleado</div>
            <div class="value">{{ $payroll->employee->full_name }}</div>
        </div>
        <div class="info-item w-doc">
            <div class="label">Documento</div>
            <div class="value muted">{{ $payroll->employee->document_type }} {{ $payroll->employee->document_number }}</div>
        </div>
        <div class="info-item w-role">
            <div class="label">Cargo</div>
            <div class="value muted">{{ $payroll->employee->position ?? '—' }}</div>
        </div>
        <div class="info-item w-days">
            <div class="label">Días laborados</div>
            <div class="value muted">{{ number_format($payroll->worked_days, 0) }}</div>
        </div>
    </div>

    <div class="section-title">Devengado</div>
    <table class="concepts">
        <thead><tr><th>Concepto</th><th class="right" style="width:30%">Valor</th></tr></thead>
        <tbody>
            <tr><td>Salario por días ordinarios laborados</td><td class="right">$ {{ number_format($payroll->basic_salary_pay, 0, ',', '.') }}</td></tr>
            @if($payroll->overtime_pay > 0)<tr><td>Horas extra y recargos</td><td class="right">$ {{ number_format($payroll->overtime_pay, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->commissions > 0)<tr><td>Comisiones</td><td class="right">$ {{ number_format($payroll->commissions, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->bonuses_salarial > 0)<tr><td>Bonificaciones salariales</td><td class="right">$ {{ number_format($payroll->bonuses_salarial, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->per_diem_salarial > 0)<tr><td>Viáticos permanentes</td><td class="right">$ {{ number_format($payroll->per_diem_salarial, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->other_salarial > 0)<tr><td>Otros pagos salariales</td><td class="right">$ {{ number_format($payroll->other_salarial, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->transport_allowance > 0)<tr><td>Auxilio de transporte</td><td class="right">$ {{ number_format($payroll->transport_allowance, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->occasional_bonuses > 0)<tr><td>Bonificaciones ocasionales</td><td class="right">$ {{ number_format($payroll->occasional_bonuses, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->extralegal_premiums > 0)<tr><td>Primas extralegales</td><td class="right">$ {{ number_format($payroll->extralegal_premiums, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->per_diem_no_salarial > 0)<tr><td>Viáticos (no salariales)</td><td class="right">$ {{ number_format($payroll->per_diem_no_salarial, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->other_no_salarial > 0)<tr><td>Otros pagos no salariales</td><td class="right">$ {{ number_format($payroll->other_no_salarial, 0, ',', '.') }}</td></tr>@endif
            <tr class="total"><td>Total devengado</td><td class="right">$ {{ number_format($payroll->total_earned, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <div class="section-title">Deducciones</div>
    <table class="concepts">
        <thead><tr><th>Concepto</th><th class="right" style="width:30%">Valor</th></tr></thead>
        <tbody>
            <tr><td>Salud</td><td class="right">$ {{ number_format($payroll->health_employee, 0, ',', '.') }}</td></tr>
            @if($payroll->pension_employee > 0)<tr><td>Pensión</td><td class="right">$ {{ number_format($payroll->pension_employee, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->fsp_employee > 0)<tr><td>Fondo de solidaridad pensional</td><td class="right">$ {{ number_format($payroll->fsp_employee, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->loans_deduction > 0)<tr><td>Préstamos a empleados</td><td class="right">$ {{ number_format($payroll->loans_deduction, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->withholding_tax > 0)<tr><td>Retención en la fuente</td><td class="right">$ {{ number_format($payroll->withholding_tax, 0, ',', '.') }}</td></tr>@endif
            @if($payroll->other_deductions > 0)<tr><td>Otras deducciones</td><td class="right">$ {{ number_format($payroll->other_deductions, 0, ',', '.') }}</td></tr>@endif
            <tr class="total"><td>Total deducciones</td><td class="right">$ {{ number_format($payroll->total_deductions, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <div class="summary-row clearfix">
        <div class="summary-box earned">
            <div class="label">Total devengado</div>
            <div class="amount">$ {{ number_format($payroll->total_earned, 0, ',', '.') }}</div>
        </div>
        <div class="summary-box deductions">
            <div class="label">Total deducciones</div>
            <div class="amount">$ {{ number_format($payroll->total_deductions, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="net-banner clearfix">
        <div class="net-label">Neto a pagar</div>
        <div class="net-amount">$ {{ number_format($payroll->net_pay, 0, ',', '.') }} COP</div>
    </div>

    <div class="footer">
        Elaborado por <strong>ALERNAL S.A.S.</strong> - Construyendo el manana<br/>
        Documento generado electrónicamente, no requiere firma
    </div>

</div>
</div>
</body>
</html>
