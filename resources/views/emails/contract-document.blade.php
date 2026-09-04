<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>{{ $document->documentType->label }} {{ $document->full_number }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2f7;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef2f7;">
<tr><td align="center" style="padding:40px 16px;">

  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;">

    <!-- ══ CARD ══════════════════════════════════════════════════ -->
    <tr><td style="background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(30,64,175,0.10);">

      <!-- ── HEADER ──────────────────────────────────────────── -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="background:linear-gradient(135deg,#1e3a8a 0%,#1e40af 60%,#2563eb 100%);padding:36px 40px 28px;text-align:center;">

            @if($sender->logo_path && \Illuminate\Support\Facades\Storage::exists($sender->logo_path))
            <div style="margin-bottom:16px;">
              <img src="{{ url(\Illuminate\Support\Facades\Storage::url($sender->logo_path)) }}"
                   alt="{{ $sender->name }}"
                   style="max-height:64px;max-width:200px;object-fit:contain;border-radius:10px;background:rgba(255,255,255,0.15);padding:8px;">
            </div>
            @endif

            <h1 style="margin:0 0 6px;color:#ffffff;font-size:21px;font-weight:800;letter-spacing:-0.3px;line-height:1.2;">
              {{ $sender->name }}
            </h1>

            <div style="display:inline-block;">
              @if($sender->nit)
              <span style="display:inline-block;color:rgba(255,255,255,0.75);font-size:12px;margin:0 8px;">NIT {{ $sender->nit }}</span>
              @endif
              @if($sender->professional_card_number)
              <span style="display:inline-block;color:rgba(255,255,255,0.75);font-size:12px;margin:0 8px;">T.P. No. {{ $sender->professional_card_number }}</span>
              @endif
            </div>

            <div style="margin:24px 0 0;background:rgba(255,255,255,0.12);border-radius:14px;padding:18px 24px;display:inline-block;min-width:240px;">
              <p style="margin:0 0 4px;color:rgba(255,255,255,0.7);font-size:10px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;">
                {{ mb_strtoupper($document->documentType->label) }}
              </p>
              <p style="margin:0;color:#ffffff;font-size:28px;font-weight:900;letter-spacing:-0.5px;font-family:'Courier New',monospace;">
                No. {{ $document->full_number }}
              </p>
            </div>

          </td>
        </tr>
      </table>

      <!-- ── BODY ────────────────────────────────────────────── -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td style="padding:36px 40px 0;">

          <p style="margin:0 0 20px;color:#1e293b;font-size:15px;line-height:1.65;">
            Estimado/a <strong>{{ $document->client->name }}</strong>,
          </p>

          @if($customMessage)
          <div style="margin:0 0 20px;padding:16px 20px;background:#eff6ff;border-radius:12px;border-left:4px solid #3b82f6;">
            <p style="margin:0;color:#1e40af;font-size:14px;line-height:1.7;">{{ $customMessage }}</p>
          </div>
          @else
          <p style="margin:0 0 20px;color:#475569;font-size:14px;line-height:1.75;">
            Me permito remitirle el {{ mb_strtolower($document->documentType->label) }} correspondiente.
            Adjunto encontrará el documento en formato PDF para su revisión.
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
                  Detalle del documento
                </p>
              </td>
            </tr>
            <tr><td style="padding:20px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="padding:5px 0;color:#64748b;font-size:13px;">Tipo</td>
                  <td style="padding:5px 0;text-align:right;color:#334155;font-size:13px;">{{ $document->documentType->label }}</td>
                </tr>
                <tr>
                  <td style="padding:5px 0;color:#64748b;font-size:13px;">Número</td>
                  <td style="padding:5px 0;text-align:right;color:#334155;font-size:13px;">{{ $document->full_number }}</td>
                </tr>
                <tr>
                  <td style="padding:5px 0;color:#64748b;font-size:13px;">Estado</td>
                  <td style="padding:5px 0;text-align:right;color:#334155;font-size:13px;">{{ $document->statusLabel() }}</td>
                </tr>
              </table>
            </td></tr>
          </table>
        </td></tr>

        <!-- Closing -->
        <tr><td style="padding:0 40px 36px;">
          <p style="margin:0 0 16px;color:#475569;font-size:14px;line-height:1.75;">
            Para cualquier consulta o aclaración sobre este documento, no dude en contactarme.
            Quedo a su disposición.
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
