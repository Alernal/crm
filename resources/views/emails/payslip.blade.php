<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Desprendible de pago {{ $payroll->payrollPeriod->number }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2f7;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef2f7;">
<tr><td align="center" style="padding:40px 16px;">

  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;">

    <tr><td style="background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(30,64,175,0.10);">

      <!-- HEADER -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="background:linear-gradient(135deg,#1e3a8a 0%,#1e40af 60%,#2563eb 100%);padding:36px 40px 28px;text-align:center;">
            <h1 style="margin:0 0 6px;color:#ffffff;font-size:21px;font-weight:800;letter-spacing:-0.3px;">
              {{ $sender->name }}
            </h1>
            <p style="margin:0 0 20px;color:rgba(255,255,255,0.75);font-size:12px;">{{ $payroll->client->name }}</p>

            <div style="background:rgba(255,255,255,0.12);border-radius:14px;padding:18px 24px;display:inline-block;min-width:240px;">
              <p style="margin:0 0 4px;color:rgba(255,255,255,0.7);font-size:10px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;">
                DESPRENDIBLE DE NÓMINA
              </p>
              <p style="margin:0;color:#ffffff;font-size:24px;font-weight:900;letter-spacing:-0.5px;font-family:'Courier New',monospace;">
                {{ $payroll->payrollPeriod->number }}
              </p>
            </div>
          </td>
        </tr>
      </table>

      <!-- BODY -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td style="padding:36px 40px 0;">
          <p style="margin:0 0 20px;color:#1e293b;font-size:15px;line-height:1.65;">
            Hola <strong>{{ $payroll->employee->full_name }}</strong>,
          </p>

          @if($customMessage)
          <div style="margin:0 0 20px;padding:16px 20px;background:#eff6ff;border-radius:12px;border-left:4px solid #3b82f6;">
            <p style="margin:0;color:#1e40af;font-size:14px;line-height:1.7;">{{ $customMessage }}</p>
          </div>
          @else
          <p style="margin:0 0 20px;color:#475569;font-size:14px;line-height:1.75;">
            Adjunto encontrarás el desprendible de pago correspondiente al período
            {{ $payroll->payrollPeriod->start_date->format('d/m/Y') }} – {{ $payroll->payrollPeriod->end_date->format('d/m/Y') }}.
          </p>
          @endif
        </td></tr>

        <!-- Resumen -->
        <tr><td style="padding:0 40px 24px;">
          <table width="100%" cellpadding="0" cellspacing="0" border="0"
                 style="border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
            <tr>
              <td style="background:#f8fafc;padding:12px 20px;border-bottom:1px solid #e2e8f0;">
                <p style="margin:0;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1.2px;">
                  Resumen de pago
                </p>
              </td>
            </tr>
            <tr><td style="padding:20px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="padding:5px 0;color:#64748b;font-size:13px;">Total devengado</td>
                  <td style="padding:5px 0;text-align:right;color:#334155;font-size:13px;">
                    $ {{ number_format($payroll->total_earned, 0, ',', '.') }} COP
                  </td>
                </tr>
                <tr>
                  <td style="padding:5px 0;color:#64748b;font-size:13px;">Total deducciones</td>
                  <td style="padding:5px 0;text-align:right;color:#dc2626;font-size:13px;">
                    − $ {{ number_format($payroll->total_deductions, 0, ',', '.') }} COP
                  </td>
                </tr>
                <tr><td colspan="2" style="padding:10px 0 0;"><div style="border-top:1px solid #e2e8f0;"></div></td></tr>
                <tr>
                  <td style="padding:12px 0 0;color:#1e40af;font-size:14px;font-weight:700;">NETO A PAGAR</td>
                  <td style="padding:12px 0 0;text-align:right;">
                    <span style="color:#1e40af;font-size:26px;font-weight:900;letter-spacing:-0.5px;">
                      $ {{ number_format($payroll->net_pay, 0, ',', '.') }}
                    </span>
                    <span style="color:#94a3b8;font-size:12px;font-weight:500;"> COP</span>
                  </td>
                </tr>
              </table>
            </td></tr>
          </table>
        </td></tr>

        <tr><td style="padding:0 40px 36px;">
          <p style="margin:0 0 16px;color:#475569;font-size:14px;line-height:1.75;">
            Si tienes alguna pregunta sobre este pago, no dudes en escribirnos.
          </p>
          <p style="margin:0 0 4px;color:#64748b;font-size:14px;">Atentamente,</p>
          <p style="margin:0;color:#0f172a;font-size:16px;font-weight:800;">{{ $sender->name }}</p>
        </td></tr>
      </table>

      <!-- FOOTER -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="background:#f1f5f9;border-top:1px solid #e2e8f0;padding:20px 40px;">
            <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.6;">
              Correo generado automáticamente. Por favor no respondas a este mensaje.
            </p>
          </td>
        </tr>
      </table>

    </td></tr>

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
