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

    .page {
        padding: 28pt 32pt 28pt 32pt;
    }

    .clearfix::after { content: ''; display: table; clear: both; }

    /* ── Cabecera ── */
    .header {
        width: 100%;
        padding-bottom: 14pt;
        margin-bottom: 14pt;
        border-bottom: 2pt solid #111111;
    }
    .header-left {
        width: 38%;
        float: left;
    }
    .header-right {
        width: 60%;
        float: right;
        text-align: right;
        padding-top: 4pt;
    }

    .logo-box {
        width: 120pt;
        height: 60pt;
        text-align: center;
        line-height: 60pt;
        font-size: 0;
        overflow: hidden;
    }
    .logo-box img {
        display: inline-block;
        vertical-align: middle;
        max-width: 114pt;
        max-height: 54pt;
        line-height: normal;
    }
    .logo-empty {
        width: 120pt;
        height: 60pt;
    }

    .doc-title {
        font-size: 17pt;
        font-weight: bold;
        color: #111111;
        letter-spacing: 0.5pt;
        margin-bottom: 3pt;
    }
    .doc-number {
        font-size: 10pt;
        font-weight: bold;
        color: #444444;
        margin-bottom: 8pt;
    }
    .doc-dates {
        font-size: 8pt;
        color: #444444;
        line-height: 1.8;
    }
    .doc-dates strong { color: #111111; }

    /* ── Emisor + Cliente ── */
    .parties {
        width: 100%;
        margin-bottom: 18pt;
        padding-bottom: 14pt;
        border-bottom: 0.5pt solid #dddddd;
    }
    .party-col {
        width: 48%;
        float: left;
    }
    .party-col + .party-col {
        float: right;
    }
    .section-label {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.8pt;
        color: #888888;
        border-bottom: 0.5pt solid #eeeeee;
        padding-bottom: 3pt;
        margin-bottom: 5pt;
    }
    .party-name {
        font-size: 10.5pt;
        font-weight: bold;
        color: #111111;
        margin-bottom: 3pt;
    }
    .party-detail {
        font-size: 8pt;
        color: #444444;
        line-height: 1.65;
    }

    /* ── Tabla de items ── */
    .section-title {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.8pt;
        color: #888888;
        margin-bottom: 6pt;
    }
    .items-table {
        width: 100%;
        border-collapse: collapse;
    }
    .items-table th {
        font-size: 7.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
        color: #111111;
        border-top: 1pt solid #111111;
        border-bottom: 1pt solid #111111;
        padding: 6pt 8pt;
        text-align: left;
        background: #ffffff;
    }
    .items-table th.right { text-align: right; }
    .items-table td {
        font-size: 8.5pt;
        font-weight: normal;
        color: #222222;
        padding: 7pt 8pt;
        border-bottom: 0.5pt solid #eeeeee;
        vertical-align: top;
        background: #ffffff;
    }
    .items-table td.right { text-align: right; }
    .items-table tr:last-child td { border-bottom: 1pt solid #111111; }

    /* ── Totales ── */
    .totals-wrapper {
        width: 100%;
        margin-top: 12pt;
    }
    .totals-notes {
        width: 52%;
        float: left;
    }
    .totals-box {
        width: 44%;
        float: right;
    }
    .totals-table {
        width: 100%;
        border-collapse: collapse;
    }
    .totals-table td {
        font-size: 8.5pt;
        font-weight: normal;
        padding: 4pt 6pt;
        color: #333333;
        background: #ffffff;
        border: none;
    }
    .totals-table td.label { color: #555555; }
    .totals-table td.amount { text-align: right; }
    .totals-table td.deduct { color: #444444; }
    .totals-table td.deduct-amount { text-align: right; color: #444444; }
    .totals-table tr.sep td { border-top: 0.5pt solid #dddddd; padding-top: 5pt; }
    .totals-table tr.total-row td {
        font-size: 10pt;
        font-weight: bold;
        color: #111111;
        border-top: 1.5pt solid #111111;
        padding-top: 6pt;
    }

    /* ── Notas ── */
    .notes-title {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.6pt;
        color: #888888;
        margin-bottom: 4pt;
    }
    .notes-text {
        font-size: 8pt;
        color: #444444;
        line-height: 1.6;
    }

    /* ── Informacion de pago ── */
    .payment-section {
        margin-top: 18pt;
        padding: 10pt 12pt;
        border: 0.25pt solid #999999;
        background: #fafafa;
    }
    .payment-title {
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.8pt;
        color: #888888;
        margin-bottom: 6pt;
    }
    .payment-method-line {
        font-size: 8.5pt;
        color: #222222;
        margin-bottom: 4pt;
    }
    .payment-bank {
        font-size: 8pt;
        color: #444444;
        line-height: 1.7;
    }
    .payment-link {
        font-size: 8pt;
        color: #444444;
        margin-top: 6pt;
    }
    .payment-link a {
        color: #1a56db;
    }

    /* ── Estado de cuenta ── */
    .page-break { page-break-before: always; }

    .stmt-doc-title {
        font-size: 15pt;
        font-weight: bold;
        color: #111111;
        letter-spacing: 0.5pt;
        margin-bottom: 2pt;
    }
    .stmt-client-name {
        font-size: 10pt;
        font-weight: bold;
        color: #333333;
        margin-bottom: 6pt;
    }

    .stmt-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 22pt;
    }
    .stmt-table th {
        font-size: 7.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
        color: #111111;
        border-top: 1pt solid #111111;
        border-bottom: 1pt solid #111111;
        padding: 5pt 7pt;
        text-align: left;
        background: #ffffff;
    }
    .stmt-table th.r { text-align: right; }
    .stmt-table td {
        font-size: 8pt;
        color: #333333;
        padding: 6pt 7pt;
        border-bottom: 0.5pt solid #eeeeee;
        vertical-align: top;
        background: #ffffff;
    }
    .stmt-table td.r { text-align: right; }
    .stmt-foot td {
        font-size: 8.5pt;
        font-weight: bold;
        color: #111111;
        border-top: 1.5pt solid #111111;
        border-bottom: none;
        padding-top: 6pt;
        padding-bottom: 5pt;
        background: #ffffff;
    }
    .stmt-foot td.r {
        text-align: right;
    }
    .stmt-section-gap { margin-top: 20pt; }
</style>
</head>
<body>

{{-- =============================================
     PAGINA 1: CUENTA DE COBRO
     ============================================= --}}
<div class="page">

    {{-- Cabecera --}}
    <div class="header clearfix">
        <div class="header-left">
            @if($user->logo_path && file_exists(storage_path('app/public/' . $user->logo_path)))
            <div class="logo-box">
                <img src="{{ storage_path('app/public/' . $user->logo_path) }}" alt="Logo" />
            </div>
            @else
            <div class="logo-empty"></div>
            @endif
        </div>
        <div class="header-right">
            <div class="doc-title">CUENTA DE COBRO</div>
            <div class="doc-number">No. {{ $invoice->number }}</div>
            <div class="doc-dates">
                <strong>Fecha de emision:</strong> {{ $invoice->issue_date->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}<br/>
                @if($invoice->due_date)
                <strong>Fecha de vencimiento:</strong> {{ $invoice->due_date->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}<br/>
                @endif
            </div>
        </div>
    </div>

    {{-- Emisor | Cliente --}}
    <div class="parties clearfix">
        <div class="party-col">
            <div class="section-label">Emisor</div>
            <div class="party-name">{{ $user->name }}</div>
            <div class="party-detail">
                @if($user->nit)NIT: {{ $user->nit }}<br/>@endif
                @if($user->address){{ $user->address }}@if($user->city), {{ $user->city }}@endif<br/>
                @elseif($user->city){{ $user->city }}<br/>@endif
                @if($user->phone)Tel: {{ $user->phone }}<br/>@endif
                {{ $user->email }}
            </div>
        </div>
        <div class="party-col">
            <div class="section-label">Cobrar a</div>
            <div class="party-name">{{ $invoice->client->name }}</div>
            <div class="party-detail">
                {{ $invoice->client->document_type }}: {{ $invoice->client->full_document }}<br/>
                @if($invoice->client->address || $invoice->client->city)
                {{ implode(', ', array_filter([$invoice->client->address, $invoice->client->city, $invoice->client->department])) }}<br/>
                @endif
                @if($invoice->client->phone)Tel: {{ $invoice->client->phone }}<br/>@endif
                @if($invoice->client->email){{ $invoice->client->email }}@endif
            </div>
        </div>
    </div>

    {{-- Tabla de items --}}
    @php $hasDiscounts = $invoice->discount_amount > 0; @endphp

    <div class="section-title">Descripcion de servicios</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:{{ $hasDiscounts ? '38%' : '44%' }}">Descripcion</th>
                <th class="right" style="width:9%">Cant.</th>
                <th class="right" style="width:{{ $hasDiscounts ? '15%' : '18%' }}">Precio unit.</th>
                @if($hasDiscounts)<th class="right" style="width:9%">Desc. %</th>@endif
                <th class="right" style="width:10%">IVA %</th>
                <th class="right" style="width:19%">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="right">{{ number_format($item->quantity, $item->quantity == intval($item->quantity) ? 0 : 2) }}</td>
                <td class="right">$ {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                @if($hasDiscounts)
                <td class="right">
                    @if((float)$item->discount_rate > 0){{ number_format($item->discount_rate, (float)$item->discount_rate == intval($item->discount_rate) ? 0 : 2) }}%
                    @else 0%@endif
                </td>
                @endif
                <td class="right">{{ $item->vat_rate > 0 ? number_format($item->vat_rate, 0).'%' : '0%' }}</td>
                <td class="right">$ {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totales + notas --}}
    <div class="totals-wrapper clearfix">
        <div class="totals-notes">
            @if($invoice->notes)
            <div style="margin-top: 12pt;">
                <div class="notes-title">Notas y condiciones de pago</div>
                <div class="notes-text">{{ $invoice->notes }}</div>
            </div>
            @endif
        </div>
        <div class="totals-box">
            <table class="totals-table">
                @if($hasDiscounts)
                <tr>
                    <td class="label">Subtotal bruto</td>
                    <td class="amount">$ {{ number_format($invoice->subtotal + $invoice->discount_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="deduct">- Descuentos</td>
                    <td class="deduct-amount">$ {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                </tr>
                <tr class="sep">
                    <td class="label">Base gravable</td>
                    <td class="amount">$ {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                </tr>
                @else
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="amount">$ {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">IVA</td>
                    <td class="amount">$ {{ number_format($invoice->vat_amount, 0, ',', '.') }}</td>
                </tr>
                @if((float)$invoice->withholding_amount > 0)
                @php $wr = (float)$invoice->withholding_rate; @endphp
                <tr>
                    <td class="deduct">- Retefuente ({{ $wr == intval($wr) ? number_format($wr,0) : number_format($wr,2) }}%)</td>
                    <td class="deduct-amount">$ {{ number_format($invoice->withholding_amount, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>TOTAL A COBRAR</td>
                    <td class="amount">$ {{ number_format($invoice->total, 0, ',', '.') }} COP</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Informacion de pago --}}
    @if($invoice->payment_method || $user->bank_name || !empty($user->payment_link))
    @php $pmLabels = ['cash'=>'Efectivo','bank_transfer'=>'Transferencia bancaria','bre_b'=>'BRE-B','check'=>'Cheque']; @endphp
    <div class="payment-section">
        <div class="payment-title">Informacion de pago</div>
        @if($invoice->payment_method)
        <div class="payment-method-line"><strong>Forma de pago:</strong> {{ $pmLabels[$invoice->payment_method] ?? '' }}</div>
        @endif
        @if($user->bank_name)
        <div class="payment-bank">
            <strong>Banco:</strong> {{ $user->bank_name }}
            @if($user->account_type) - {{ $user->account_type === 'savings' ? 'Cuenta de ahorros' : 'Cuenta corriente' }}@endif<br/>
            @if($user->account_number)<strong>No. de cuenta:</strong> {{ $user->account_number }}<br/>@endif
            @if($user->account_holder_name)<strong>Titular:</strong> {{ $user->account_holder_name }}@if($user->account_holder_id) CC {{ $user->account_holder_id }}@endif
            @endif
        </div>
        @endif
        @if(!empty($user->payment_link))
        <div class="payment-link"><strong>Pago en linea:</strong> <a href="{{ $user->payment_link }}">{{ $user->payment_link }}</a></div>
        @endif
    </div>
    @endif


</div>

{{-- =============================================
     PAGINA 2: ESTADO DE CUENTA
     ============================================= --}}
<div class="page-break">
<div class="page">

    @php
        $statusMap = [
            'draft'     => 'Borrador',
            'sent'      => 'Emitida',
            'paid'      => 'Pagada',
            'overdue'   => 'Vencida',
            'cancelled' => 'Anulada',
        ];
        $payMethodMap = [
            'efectivo'      => 'Efectivo',
            'transferencia' => 'Transferencia bancaria',
            'cheque'        => 'Cheque',
            'otro'          => 'Otro',
        ];
        $totalFacturado = $clientInvoices->sum('total');
        $totalAbonado   = $clientInvoices->sum(fn($i) => $i->paid_amount);
        $totalSaldo     = max(0, $totalFacturado - $totalAbonado);
        $totalPagos     = $allPayments->sum('amount');
    @endphp

    {{-- Cabecera estado de cuenta --}}
    <div class="header clearfix">
        <div class="header-left">
            @if($user->logo_path && file_exists(storage_path('app/public/' . $user->logo_path)))
            <div class="logo-box">
                <img src="{{ storage_path('app/public/' . $user->logo_path) }}" alt="Logo" />
            </div>
            @else
            <div class="logo-empty"></div>
            @endif
        </div>
        <div class="header-right">
            <div class="stmt-doc-title">ESTADO DE CUENTA</div>
            <div class="stmt-client-name">{{ $invoice->client->name }}</div>
            <div class="doc-dates">
                <strong>{{ $invoice->client->document_type }}:</strong> {{ $invoice->client->full_document }}<br/>
                <strong>Generado:</strong> {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
            </div>
        </div>
    </div>

    {{-- Tabla 1: Cuentas de cobro --}}
    <div class="section-title">Cuentas de cobro</div>
    <table class="stmt-table">
        <thead>
            <tr>
                <th style="width:18%">N° Cuenta</th>
                <th style="width:14%">Emision</th>
                <th style="width:14%">Vencimiento</th>
                <th class="r" style="width:18%">Total</th>
                <th class="r" style="width:16%">Abonado</th>
                <th class="r" style="width:16%">Saldo</th>
                <th style="width:10%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clientInvoices as $inv)
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
                <td>{{ $statusMap[$inv->status] ?? $inv->status }}</td>
            </tr>
            @endforeach
            <tr class="stmt-foot">
                <td colspan="3">TOTALES</td>
                <td class="r">$ {{ number_format($totalFacturado, 0, ',', '.') }}</td>
                <td class="r">$ {{ number_format($totalAbonado, 0, ',', '.') }}</td>
                <td class="r">$ {{ number_format($totalSaldo, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{-- Tabla 2: Pagos recibidos --}}
    <div class="section-title stmt-section-gap">Pagos recibidos</div>
    @if($allPayments->count() > 0)
    <table class="stmt-table">
        <thead>
            <tr>
                <th style="width:14%">Fecha</th>
                <th style="width:18%">N° Cuenta</th>
                <th style="width:26%">Metodo de pago</th>
                <th style="width:24%">N° Comprobante</th>
                <th class="r" style="width:18%">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allPayments as $pago)
            <tr>
                <td>{{ $pago->payment_date->format('d/m/Y') }}</td>
                <td>{{ $pago->invoice_number }}</td>
                <td>{{ $payMethodMap[$pago->payment_method] ?? $pago->payment_method }}</td>
                <td>{{ $pago->reference ?: '—' }}</td>
                <td class="r">$ {{ number_format($pago->amount, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="stmt-foot">
                <td colspan="4">TOTAL PAGADO</td>
                <td class="r">$ {{ number_format($totalPagos, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    @else
    <p style="font-size:8pt; color:#888888; margin-top:6pt;">Sin pagos registrados.</p>
    @endif


</div>
</div>

</body>
</html>
