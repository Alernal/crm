<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Helvetica, Arial, sans-serif;
        font-size: 9pt;
        color: #1F2937;
        background: #ffffff;
    }

    .accent-bar { height: 4pt; background: #2563EB; }

    .page { padding: 24pt 32pt 20pt 32pt; }
    .clearfix::after { content: ''; display: table; clear: both; }

    .header { width: 100%; padding-bottom: 12pt; margin-bottom: 16pt; border-bottom: 0.75pt solid #E5E7EB; }
    .header-left { width: 55%; float: left; }
    .header-right { width: 43%; float: right; text-align: right; padding-top: 1pt; }

    .party-name { font-size: 13pt; font-weight: bold; color: #111827; margin-bottom: 3pt; }
    .party-detail { font-size: 8pt; color: #6B7280; line-height: 1.6; }

    .doc-badge {
        display: inline-block; font-size: 7pt; font-weight: bold; text-transform: uppercase;
        letter-spacing: 0.6pt; color: #2563EB; background: #EFF6FF;
        padding: 3.5pt 8pt; border-radius: 9pt; margin-bottom: 6pt;
    }
    .doc-number { font-size: 12pt; font-weight: bold; color: #111827; margin-bottom: 5pt; }
    .doc-dates { font-size: 8pt; color: #6B7280; line-height: 1.75; }
    .doc-dates strong { color: #111827; font-weight: bold; }

    .info-strip {
        width: 100%; margin-bottom: 16pt; padding: 10pt 12pt;
        background: #F9FAFB; border-left: 2.5pt solid #2563EB;
    }
    .info-item { float: left; }
    .info-item.w-name { width: 36%; }
    .info-item.w-doc { width: 20%; }
    .info-item.w-role { width: 26%; }
    .info-item.w-days { width: 18%; }
    .label { font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5pt; color: #9CA3AF; margin-bottom: 2pt; }
    .value { font-size: 9pt; color: #111827; font-weight: bold; }
    .value.muted { font-weight: normal; color: #374151; }

    .section-title {
        font-size: 7.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6pt;
        color: #111827; margin: 16pt 0 6pt; padding-bottom: 4pt; border-bottom: 1.25pt solid #2563EB;
    }

    table.concepts { width: 100%; border-collapse: collapse; }
    table.concepts th {
        font-size: 7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt;
        color: #6B7280; border-bottom: 0.75pt solid #D1D5DB;
        padding: 4pt 6pt; text-align: left; background: #ffffff;
    }
    table.concepts th.right { text-align: right; }
    table.concepts td {
        font-size: 8.5pt; color: #1F2937; padding: 4.5pt 6pt;
        border-bottom: 0.5pt solid #F3F4F6; background: #ffffff;
    }
    table.concepts tr:nth-child(even) td { background: #FAFBFC; }
    table.concepts td.right { text-align: right; }
    table.concepts tr.total td {
        font-weight: bold; color: #111827; border-top: 1pt solid #D1D5DB; border-bottom: none;
        padding-top: 6pt; background: #ffffff;
    }

    .summary-row { width: 100%; margin-top: 16pt; }
    .summary-box {
        width: 47%; padding: 8pt 10pt; border: 0.75pt solid #E5E7EB; border-radius: 4pt;
    }
    .summary-box.earned { float: left; }
    .summary-box.deductions { float: right; }
    .summary-box .label { font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4pt; color: #9CA3AF; margin-bottom: 3pt; }
    .summary-box .amount { font-size: 11.5pt; font-weight: bold; }
    .summary-box.earned .amount { color: #059669; }
    .summary-box.deductions .amount { color: #DC2626; }

    .net-banner {
        width: 100%; margin-top: 10pt; background: #1E3A8A; border-radius: 5pt;
        padding: 11pt 14pt;
    }
    .net-banner .net-label {
        float: left; font-size: 9pt; font-weight: bold; text-transform: uppercase;
        letter-spacing: 0.8pt; color: #DBEAFE; padding-top: 4pt;
    }
    .net-banner .net-amount { float: right; font-size: 17pt; font-weight: bold; color: #ffffff; }

    .footer {
        margin-top: 20pt; padding-top: 9pt; border-top: 0.5pt solid #E5E7EB;
        text-align: center; font-size: 7pt; color: #9CA3AF; line-height: 1.7;
    }
</style>
</head>
<body>
<div class="accent-bar"></div>
<div class="page">

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
</body>
</html>
