<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Estado de Cuenta — {{ $client->name }}</title>
<style>
    @page {
        size: letter portrait;
        margin: 18mm 20mm;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10pt;
        color: #111111;
        background: #f5f5f5;
    }

    .page-wrap {
        background: #ffffff;
        max-width: 760px;
        margin: 0 auto;
        padding: 32pt 36pt 36pt 36pt;
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
        margin-bottom: 18pt;
        border-bottom: 2pt solid #111;
    }
    .header-left  { width: 38%; float: left; }
    .header-right { width: 60%; float: right; text-align: right; padding-top: 4pt; }

    .logo-box {
        width: 130pt; height: 65pt;
        border: 1pt solid #ddd; background: #fafafa;
        overflow: hidden; display: flex;
        align-items: center; justify-content: center;
    }
    .logo-box img { max-width: 124pt; max-height: 59pt; object-fit: contain; }

    .doc-title      { font-size: 18pt; font-weight: bold; color: #111; letter-spacing: 0.5pt; margin-bottom: 4pt; }
    .client-name    { font-size: 11pt; font-weight: bold; color: #333; margin-bottom: 6pt; }
    .doc-meta       { font-size: 8.5pt; color: #444; line-height: 1.9; }
    .doc-meta strong { color: #111; }

    /* ── KPIs resumen ── */
    .summary-bar {
        width: 100%;
        margin-bottom: 20pt;
        border: 0.5pt solid #e5e5e5;
        background: #fafafa;
        padding: 10pt 14pt;
    }
    .summary-bar table { width: 100%; border-collapse: collapse; }
    .summary-bar td {
        font-size: 8.5pt; color: #444;
        padding: 2pt 6pt;
        text-align: center;
        border-right: 0.5pt solid #e5e5e5;
    }
    .summary-bar td:first-child { text-align: left; border-right: 0.5pt solid #e5e5e5; }
    .summary-bar td:last-child  { border-right: none; text-align: right; }
    .summary-bar .lbl { font-size: 7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5pt; color: #888; display: block; margin-bottom: 2pt; }
    .summary-bar .val { font-size: 11pt; font-weight: bold; color: #111; }

    /* ── Sección label ── */
    .section-title {
        font-size: 7pt; font-weight: bold; text-transform: uppercase;
        letter-spacing: 1pt; color: #888; margin-bottom: 7pt;
    }

    /* ── Tablas ── */
    table.stmt {
        width: 100%; border-collapse: collapse; font-size: 9pt;
        margin-bottom: 24pt;
    }
    table.stmt th {
        font-size: 7.5pt; font-weight: bold; text-transform: uppercase;
        letter-spacing: 0.4pt; color: #111;
        border-top: 1pt solid #111; border-bottom: 1pt solid #111;
        padding: 6pt 7pt; text-align: left; background: #fff;
    }
    table.stmt th.r { text-align: right; }
    table.stmt td {
        font-size: 9pt; color: #333; padding: 6.5pt 7pt;
        border-bottom: 0.5pt solid #eee; vertical-align: top; background: #fff;
    }
    table.stmt td.r   { text-align: right; }
    table.stmt td.dim { color: #888; }
    table.stmt tr.foot td {
        font-size: 9.5pt; font-weight: bold; color: #111;
        border-top: 1.5pt solid #111; border-bottom: none;
        padding-top: 6pt; padding-bottom: 5pt;
    }
    table.stmt tr.foot td.r { text-align: right; }

    .section-gap { margin-top: 20pt; }

    /* ── Pie ── */
    .footer {
        margin-top: 30pt; padding-top: 10pt;
        border-top: 0.5pt solid #ccc;
        text-align: center; font-size: 7.5pt; color: #888;
    }
</style>
</head>
<body>
<div class="page-wrap">

@php
    $statusMap = [
        'sent'    => 'Emitida',
        'paid'    => 'Pagada',
        'overdue' => 'Vencida',
    ];
    $payMethodMap = [
        'efectivo'      => 'Efectivo',
        'transferencia' => 'Transferencia bancaria',
        'cheque'        => 'Cheque',
        'otro'          => 'Otro',
    ];
    $totalFacturado = $invoices->sum('total');
    $totalAbonado   = $invoices->sum(fn($i) => $i->paid_amount);
    $totalSaldo     = max(0, $totalFacturado - $totalAbonado);
    $totalPagos     = $allPayments->sum('amount');
@endphp

{{-- Cabecera --}}
<div class="header clearfix">
    <div class="header-left">
        @if($user->logo_path && file_exists(storage_path('app/public/' . $user->logo_path)))
        <div class="logo-box">
            <img src="{{ asset('storage/' . $user->logo_path) }}" alt="Logo" />
        </div>
        @endif
    </div>
    <div class="header-right">
        <div class="doc-title">ESTADO DE CUENTA</div>
        <div class="client-name">{{ $client->name }}</div>
        <div class="doc-meta">
            <strong>{{ $client->document_type }}:</strong> {{ $client->full_document }}<br/>
            <strong>Emisor:</strong> {{ $user->name }}
            @if($user->nit) · NIT {{ $user->nit }}@endif<br/>
            <strong>Fecha:</strong> {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
        </div>
    </div>
</div>

{{-- Resumen KPIs --}}
<div class="summary-bar">
    <table>
        <tr>
            <td>
                <span class="lbl">Total facturado</span>
                <span class="val">$ {{ number_format($totalFacturado, 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="lbl">Total abonado</span>
                <span class="val">$ {{ number_format($totalAbonado, 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="lbl">Saldo por cobrar</span>
                <span class="val">$ {{ number_format($totalSaldo, 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="lbl">Cuentas</span>
                <span class="val">{{ $invoices->count() }}</span>
            </td>
        </tr>
    </table>
</div>

{{-- Tabla 1: Cuentas de cobro --}}
<div class="section-title">Cuentas de cobro</div>
@if($invoices->isEmpty())
<p style="font-size:8.5pt; color:#888; margin-bottom:24pt;">Sin cuentas de cobro registradas.</p>
@else
<table class="stmt">
    <thead>
        <tr>
            <th style="width:18%">N° Cuenta</th>
            <th style="width:13%">Emisión</th>
            <th style="width:13%">Vencimiento</th>
            <th class="r" style="width:18%">Total</th>
            <th class="r" style="width:16%">Abonado</th>
            <th class="r" style="width:14%">Saldo</th>
            <th style="width:8%">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $inv)
        @php
            $invPaid    = $inv->paid_amount;
            $invBalance = max(0, (float)$inv->total - $invPaid);
        @endphp
        <tr>
            <td>{{ $inv->number }}</td>
            <td>{{ $inv->issue_date->format('d/m/Y') }}</td>
            <td>{{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '—' }}</td>
            <td class="r">$ {{ number_format($inv->total, 0, ',', '.') }}</td>
            <td class="r">$ {{ number_format($invPaid, 0, ',', '.') }}</td>
            <td class="r">$ {{ number_format($invBalance, 0, ',', '.') }}</td>
            <td class="{{ $inv->status === 'overdue' ? '' : 'dim' }}">{{ $statusMap[$inv->status] ?? $inv->status }}</td>
        </tr>
        @endforeach
        <tr class="foot">
            <td colspan="3">TOTALES</td>
            <td class="r">$ {{ number_format($totalFacturado, 0, ',', '.') }}</td>
            <td class="r">$ {{ number_format($totalAbonado, 0, ',', '.') }}</td>
            <td class="r">$ {{ number_format($totalSaldo, 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
@endif

{{-- Tabla 2: Pagos recibidos --}}
<div class="section-title section-gap">Pagos recibidos</div>
@if($allPayments->isEmpty())
<p style="font-size:8.5pt; color:#888;">Sin pagos registrados.</p>
@else
<table class="stmt">
    <thead>
        <tr>
            <th style="width:14%">Fecha</th>
            <th style="width:18%">N° Cuenta</th>
            <th style="width:28%">Método de pago</th>
            <th style="width:22%">N° Comprobante</th>
            <th class="r" style="width:18%">Valor</th>
        </tr>
    </thead>
    <tbody>
        @foreach($allPayments as $pago)
        <tr>
            <td>{{ $pago->payment_date->format('d/m/Y') }}</td>
            <td>{{ $pago->invoice_number }}</td>
            <td>{{ $payMethodMap[$pago->payment_method] ?? $pago->payment_method }}</td>
            <td class="dim">{{ $pago->reference ?: '—' }}</td>
            <td class="r">$ {{ number_format($pago->amount, 0, ',', '.') }}</td>
        </tr>
        @endforeach
        <tr class="foot">
            <td colspan="4">TOTAL PAGADO</td>
            <td class="r">$ {{ number_format($totalPagos, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>
@endif

<div class="footer">
    Elaborado por <strong>ALERNAL S.A.S.</strong> — Construyendo el mañana
</div>

</div>
</body>
</html>
