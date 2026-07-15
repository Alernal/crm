<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        .header { background: #1e3a5f; color: white; padding: 16px 20px; margin-bottom: 16px; }
        .header h1 { font-size: 14px; font-weight: bold; }
        .header p { font-size: 9px; opacity: 0.8; margin-top: 2px; }
        .meta { display: flex; gap: 20px; margin-bottom: 14px; padding: 0 4px; }
        .meta-item { font-size: 9px; color: #64748b; }
        .meta-item strong { color: #1e293b; display: block; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        thead tr { background: #f8fafc; }
        th { text-align: left; padding: 6px 8px; font-weight: 600; color: #64748b; text-transform: uppercase; font-size: 8px; border-bottom: 1px solid #e2e8f0; }
        td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        tr:hover td { background: #f8fafc; }
        .badge { padding: 2px 5px; border-radius: 3px; font-size: 8px; font-weight: 600; }
        .badge-pending  { background: #f1f5f9; color: #64748b; }
        .badge-upcoming { background: #fef3c7; color: #92400e; }
        .badge-overdue  { background: #fee2e2; color: #dc2626; }
        .footer { margin-top: 16px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #94a3b8; text-align: center; }
        .code { font-family: monospace; font-size: 8px; color: #1e40af; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Calendario Tributario {{ $year }}</h1>
        <p>{{ $client->name }} — NIT {{ $client->document_number }}-{{ $client->dv }}</p>
    </div>

    <div class="meta">
        <div class="meta-item"><strong>Régimen</strong>{{ $client->tax_regime === 'regimen_simple' ? 'SIMPLE' : 'Ordinario' }}</div>
        <div class="meta-item"><strong>Total obligaciones</strong>{{ count($events) }}</div>
        <div class="meta-item"><strong>Generado</strong>{{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Obligación</th>
                <th>Código</th>
                <th>Período</th>
                <th>Vencimiento</th>
                <th>Días</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($events as $event)
                @php
                    $days   = $event['days_left'];
                    $badge  = $event['status'] === 'vencido' ? 'badge-overdue' : ($event['status'] === 'proximo' ? 'badge-upcoming' : 'badge-pending');
                    $label  = ['pendiente'=>'Pendiente','proximo'=>'Próxima','vencido'=>'Vencida'][$event['status']] ?? $event['status'];
                @endphp
                <tr>
                    <td>{{ $event['name'] }}</td>
                    <td class="code">{{ $event['code'] }}</td>
                    <td>{{ $event['period_label'] }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($event['due_date'])->format('d/m/Y') }}</td>
                    <td>{{ $days < 0 ? abs($days).'d vencida' : ($days === 0 ? 'Hoy' : $days.'d') }}</td>
                    <td><span class="badge {{ $badge }}">{{ $label }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Documento generado automáticamente · Verifique fechas con el decreto oficial DIAN
    </div>
</body>
</html>
