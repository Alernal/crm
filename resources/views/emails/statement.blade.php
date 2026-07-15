<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Estado de Cuenta — {{ $client->name }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2f7;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">

@php
  $totalFacturado  = $invoices->sum('total');
  $totalAbonado    = $invoices->sum(fn($i) => $i->paid_amount);
  $saldoPendiente  = $invoices->whereIn('status', ['sent','overdue'])->sum(fn($i) => max(0, $i->balance));
  $countVencidas   = $invoices->where('status', 'overdue')->count();
  $pendientes      = $invoices->whereIn('status', ['sent','overdue'])->filter(fn($i) => $i->balance > 0);
@endphp

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef2f7;">
<tr><td align="center" style="padding:40px 16px;">

  <!-- Wrapper 620px -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;">

    <!-- ══ CARD ══════════════════════════════════════════════════ -->
    <tr><td style="background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(30,64,175,0.10);">

      <!-- ── HEADER ──────────────────────────────────────────── -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="background:linear-gradient(135deg,#1e3a8a 0%,#1e40af 60%,#2563eb 100%);padding:36px 40px 0;text-align:center;">

            @if($sender->logo_path && \Illuminate\Support\Facades\Storage::exists($sender->logo_path))
            <div style="margin-bottom:16px;">
              <img src="{{ url(\Illuminate\Support\Facades\Storage::url($sender->logo_path)) }}"
                   alt="{{ $sender->name }}"
                   style="max-height:60px;max-width:180px;object-fit:contain;border-radius:10px;background:rgba(255,255,255,0.15);padding:8px;">
            </div>
            @endif

            <h1 style="margin:0 0 6px;color:#ffffff;font-size:20px;font-weight:800;letter-spacing:-0.3px;">
              {{ $sender->name }}
            </h1>
            <div>
              @if($sender->nit)
              <span style="color:rgba(255,255,255,0.7);font-size:12px;margin:0 6px;">NIT {{ $sender->nit }}</span>
              @endif
              @if($sender->professional_card_number)
              <span style="color:rgba(255,255,255,0.7);font-size:12px;margin:0 6px;">T.P. No. {{ $sender->professional_card_number }}</span>
              @endif
            </div>

            <!-- Badge estado de cuenta -->
            <div style="margin:22px 0 0;background:rgba(255,255,255,0.12);border-radius:14px;padding:16px 28px;display:inline-block;min-width:280px;">
              <p style="margin:0 0 4px;color:rgba(255,255,255,0.7);font-size:10px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;">
                ESTADO DE CUENTA
              </p>
              <p style="margin:0 0 4px;color:#ffffff;font-size:19px;font-weight:800;">{{ $client->name }}</p>
              <p style="margin:0;color:rgba(255,255,255,0.65);font-size:12px;">
                Corte al {{ now()->format('d \d\e F \d\e Y') }}
              </p>
            </div>

          </td>
        </tr>
        @if($countVencidas > 0)
        <tr>
          <td style="background:#dc2626;padding:10px 40px;text-align:center;">
            <p style="margin:0;color:#ffffff;font-size:12px;font-weight:700;letter-spacing:0.3px;">
              ⚠️ {{ $countVencidas }} cuenta{{ $countVencidas > 1 ? 's' : '' }} vencida{{ $countVencidas > 1 ? 's' : '' }} — requiere{{ $countVencidas > 1 ? 'n' : '' }} atención inmediata
            </p>
          </td>
        </tr>
        @endif
        <tr>
          <td style="background:linear-gradient(135deg,#1e3a8a 0%,#1e40af 60%,#2563eb 100%);padding:0 40px 28px;">
          </td>
        </tr>
      </table>

      <!-- ── BODY ────────────────────────────────────────────── -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td style="padding:36px 40px 0;">

          <p style="margin:0 0 20px;color:#1e293b;font-size:15px;line-height:1.65;">
            Estimado/a <strong>{{ $client->name }}</strong>,
          </p>

          @if($customMessage)
          <div style="margin:0 0 20px;padding:16px 20px;background:#eff6ff;border-radius:12px;border-left:4px solid #3b82f6;">
            <p style="margin:0;color:#1e40af;font-size:14px;line-height:1.7;">{{ $customMessage }}</p>
          </div>
          @else
          <p style="margin:0 0 20px;color:#475569;font-size:14px;line-height:1.75;">
            Le remitimos el estado de cuenta actualizado a la fecha, con el fin de facilitar la
            revisión y regularización de los saldos pendientes. Adjunto encontrará el documento
            detallado en formato PDF.
          </p>
          @endif

        </td></tr>

        <!-- KPIs row -->
        <tr><td style="padding:0 40px 24px;">
          <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
              <td width="32%" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 16px;text-align:center;">
                <p style="margin:0 0 5px;font-size:10px;font-weight:700;color:#3b82f6;text-transform:uppercase;letter-spacing:1px;">Facturado</p>
                <p style="margin:0;font-size:17px;font-weight:900;color:#1e40af;letter-spacing:-0.3px;">
                  $&nbsp;{{ number_format($totalFacturado, 0, ',', '.') }}
                </p>
              </td>
              <td width="4%" style="padding:0 6px;"></td>
              <td width="32%" style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:14px 16px;text-align:center;">
                <p style="margin:0 0 5px;font-size:10px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:1px;">Abonado</p>
                <p style="margin:0;font-size:17px;font-weight:900;color:#15803d;letter-spacing:-0.3px;">
                  $&nbsp;{{ number_format($totalAbonado, 0, ',', '.') }}
                </p>
              </td>
              <td width="4%" style="padding:0 6px;"></td>
              <td width="32%" style="background:{{ $countVencidas > 0 ? '#fef2f2' : '#fff7ed' }};border:1px solid {{ $countVencidas > 0 ? '#fca5a5' : '#fed7aa' }};border-radius:12px;padding:14px 16px;text-align:center;">
                <p style="margin:0 0 5px;font-size:10px;font-weight:700;color:{{ $countVencidas > 0 ? '#dc2626' : '#ea580c' }};text-transform:uppercase;letter-spacing:1px;">Saldo pendiente</p>
                <p style="margin:0;font-size:17px;font-weight:900;color:{{ $countVencidas > 0 ? '#b91c1c' : '#c2410c' }};letter-spacing:-0.3px;">
                  $&nbsp;{{ number_format($saldoPendiente, 0, ',', '.') }}
                </p>
              </td>
            </tr>
          </table>
        </td></tr>

        <!-- Pending invoices table -->
        @if($pendientes->isNotEmpty())
        <tr><td style="padding:0 40px 24px;">
          <table width="100%" cellpadding="0" cellspacing="0" border="0"
                 style="border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
            <tr style="background:#f8fafc;">
              <td style="padding:10px 16px;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid #e2e8f0;">
                Cuenta
              </td>
              <td style="padding:10px 16px;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid #e2e8f0;text-align:center;">
                Estado
              </td>
              <td style="padding:10px 16px;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid #e2e8f0;text-align:right;">
                Vencimiento
              </td>
              <td style="padding:10px 16px;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid #e2e8f0;text-align:right;">
                Saldo
              </td>
            </tr>
            @foreach($pendientes as $inv)
            <tr>
              <td style="padding:11px 16px;font-size:13px;font-family:'Courier New',monospace;font-weight:700;color:#1e40af;border-bottom:1px solid #f1f5f9;">
                {{ $inv->number }}
              </td>
              <td style="padding:11px 16px;text-align:center;border-bottom:1px solid #f1f5f9;">
                <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;
                  {{ $inv->status === 'overdue'
                      ? 'background:#fef2f2;color:#dc2626;'
                      : 'background:#eff6ff;color:#1d4ed8;' }}">
                  {{ $inv->status === 'overdue' ? 'Vencida' : 'Emitida' }}
                </span>
              </td>
              <td style="padding:11px 16px;text-align:right;font-size:13px;border-bottom:1px solid #f1f5f9;
                {{ $inv->status === 'overdue' ? 'color:#dc2626;font-weight:600;' : 'color:#475569;' }}">
                {{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '—' }}
              </td>
              <td style="padding:11px 16px;text-align:right;font-size:13px;font-weight:700;color:#111827;border-bottom:1px solid #f1f5f9;">
                $ {{ number_format(max(0, $inv->balance), 0, ',', '.') }}
              </td>
            </tr>
            @endforeach
            <!-- Total row -->
            <tr style="background:#f8fafc;">
              <td colspan="3" style="padding:12px 16px;font-size:13px;font-weight:700;color:#334155;text-align:right;">
                Total saldo pendiente
              </td>
              <td style="padding:12px 16px;text-align:right;font-size:16px;font-weight:900;color:#1e40af;">
                $ {{ number_format($saldoPendiente, 0, ',', '.') }}
              </td>
            </tr>
          </table>
        </td></tr>
        @endif

        <!-- Payment methods -->
        @if($sender->bank_name || $sender->payment_link)
        <tr><td style="padding:0 40px 20px;">
          <table width="100%" cellpadding="0" cellspacing="0" border="0"
                 style="border:1px solid #bfdbfe;border-radius:14px;overflow:hidden;background:#eff6ff;">
            <tr>
              <td style="padding:12px 20px;border-bottom:1px solid #bfdbfe;">
                <p style="margin:0;font-size:11px;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:1px;">
                  Medios de pago disponibles
                </p>
              </td>
            </tr>
            <tr><td style="padding:18px 20px;">
              @if($sender->bank_name)
              <p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#1e40af;text-transform:uppercase;letter-spacing:0.8px;">
                Transferencia bancaria
              </p>
              <p style="margin:0 0 3px;font-size:13px;color:#334155;">
                {{ $sender->bank_name }}@if($sender->account_type) · {{ $sender->account_type }}@endif
              </p>
              @if($sender->account_number)
              <p style="margin:0 0 3px;font-size:15px;font-weight:700;color:#111827;font-family:'Courier New',monospace;letter-spacing:1px;">
                {{ $sender->account_number }}
              </p>
              @endif
              @if($sender->account_holder_name)
              <p style="margin:0 0 {{ $sender->payment_link ? '14px' : '0' }};font-size:12px;color:#64748b;">
                A nombre de: {{ $sender->account_holder_name }}
              </p>
              @endif
              @endif

              @if($sender->payment_link)
              @if($sender->bank_name)
              <div style="border-top:1px solid #bfdbfe;padding-top:14px;margin-top:2px;">
              @endif
                <p style="margin:0 0 8px;font-size:12px;font-weight:700;color:#1e40af;text-transform:uppercase;letter-spacing:0.8px;">
                  Pago en línea
                </p>
                <a href="{{ $sender->payment_link }}"
                   style="display:inline-block;background:linear-gradient(135deg,#1e40af,#2563eb);color:#ffffff;text-decoration:none;padding:10px 22px;border-radius:10px;font-size:13px;font-weight:700;">
                  Pagar en línea &rarr;
                </a>
              @if($sender->bank_name)
              </div>
              @endif
              @endif
            </td></tr>
          </table>
        </td></tr>
        @endif

        <!-- Contact channels -->
        <tr><td style="padding:0 40px 24px;">
          <table width="100%" cellpadding="0" cellspacing="0" border="0"
                 style="border:1px solid #fde68a;border-radius:14px;overflow:hidden;background:#fffbeb;">
            <tr><td style="padding:18px 20px;">
              <p style="margin:0 0 10px;font-size:13px;font-weight:700;color:#92400e;">
                📞 Canales de contacto para regularizar su situación
              </p>
              <p style="margin:0 0 6px;color:#78350f;font-size:13px;line-height:1.6;">
                Si desea coordinar una fecha de pago, establecer un acuerdo de pago o tiene alguna
                consulta sobre este estado de cuenta, no dude en comunicarse con nosotros:
              </p>
              @if($sender->phone)
              <p style="margin:4px 0 0;font-size:13px;color:#78350f;">
                • Teléfono / WhatsApp: <strong>{{ $sender->phone }}</strong>
              </p>
              @endif
              <p style="margin:4px 0 0;font-size:13px;color:#78350f;">
                • Correo electrónico: <strong>{{ $sender->email }}</strong>
              </p>
              @if($sender->city)
              <p style="margin:4px 0 0;font-size:13px;color:#78350f;">
                • Ciudad: {{ $sender->city }}
              </p>
              @endif
            </td></tr>
          </table>
        </td></tr>

        <!-- Closing -->
        <tr><td style="padding:0 40px 36px;">
          <p style="margin:0 0 16px;color:#475569;font-size:14px;line-height:1.75;">
            Agradecemos su atención a esta comunicación y confiamos en poder regularizar
            la situación a la brevedad. Quedamos a su entera disposición.
          </p>
          <p style="margin:0 0 4px;color:#64748b;font-size:14px;">Atentamente,</p>
          <p style="margin:0 0 2px;color:#0f172a;font-size:16px;font-weight:800;">{{ $sender->name }}</p>
          @if($sender->professional_card_number)
          <p style="margin:0;color:#64748b;font-size:13px;">Contador Público · T.P. No. {{ $sender->professional_card_number }}</p>
          @endif
        </td></tr>
      </table>

      <!-- ── FOOTER ──────────────────────────────────────────── -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="background:#f1f5f9;border-top:1px solid #e2e8f0;padding:20px 40px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="vertical-align:top;">
                  @if($sender->phone)
                  <p style="margin:0 0 4px;font-size:12px;color:#64748b;">📞 {{ $sender->phone }}</p>
                  @endif
                  <p style="margin:0 0 4px;font-size:12px;color:#64748b;">✉ {{ $sender->email }}</p>
                  @if($sender->city)
                  <p style="margin:0;font-size:12px;color:#64748b;">📍 {{ $sender->city }}</p>
                  @endif
                </td>
                <td style="text-align:right;vertical-align:top;">
                  <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.6;">
                    Correo generado automáticamente.<br>
                    Por favor no responda a este mensaje.
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

    </td></tr>
    <!-- ══ /CARD ═════════════════════════════════════════════════ -->

    <tr><td style="padding:20px 0;text-align:center;">
      <p style="margin:0;font-size:11px;color:#94a3b8;">
        &copy; {{ now()->year }} {{ $sender->name }} · CRM Profesional
      </p>
    </td></tr>

  </table>
</td></tr>
</table>

</body>
</html>
