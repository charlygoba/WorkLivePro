<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 18px 28px; }
        body { font-family: Helvetica, Arial, sans-serif; color: #172033; font-size: 7px; }
        h1 { margin: 0 0 4px; color: #312e81; font-size: 16px; }
        .muted { margin: 0 0 8px; color: #64748b; font-size: 7px; }
        .summary { margin-bottom: 9px; padding: 6px; border: 1px solid #dbe3ef; background: #f8fafc; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        th { padding: 4px 3px; color: #fff; background: #4f46e5; text-align: left; font-size: 6px; }
        td { padding: 3px; border-bottom: 1px solid #e2e8f0; vertical-align: top; word-wrap: break-word; }
        .date { color: #3730a3; font-weight: bold; }
        .event { color: #4338ca; font-weight: bold; }
        .right { text-align: right; }
        .footer { position: fixed; bottom: -16px; color: #64748b; font-size: 6px; }
    </style>
</head>
<body>
    <h1>WorkLive Pro · Timeline diario de eventos</h1>
    <p class="muted">Periodo: {{ $from }} a {{ $to }} · Zona horaria: {{ $corporateTimezone }} · Generado: {{ now($corporateTimezone)->format('d/m/Y H:i') }}</p>
    <div class="summary">Usuarios: {{ $selectedEmployees->isEmpty() ? 'Todos los usuarios' : $selectedEmployees->pluck('name')->join(', ') }} · Eventos: {{ number_format($events->count()) }} · Duración total: {{ gmdate('H:i:s', min(86399, (int) $events->sum('duration'))) }}</div>
    @if($events->isNotEmpty())
        <table>
            <thead><tr><th style="width:10%">Fecha / hora</th><th style="width:14%">Colaborador</th><th style="width:10%">Evento</th><th style="width:12%">Aplicación</th><th style="width:14%">Dominio</th><th style="width:30%">Actividad</th><th style="width:10%" class="right">Duración</th></tr></thead>
            <tbody>
            @foreach($events as $event)
                <tr><td class="date">{{ $event->display_timestamp->format('d/m/Y H:i:s') }}</td><td>{{ $event->employee_name }}</td><td class="event">{{ $event->event_type }}</td><td>{{ $event->app ?: '—' }}</td><td>{{ $event->domain ?: '—' }}</td><td>{{ $event->title ?: 'Sin título reportado' }}</td><td class="right">{{ gmdate('H:i:s', min(86399, (int) $event->duration)) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p>No hay eventos con los filtros seleccionados.</p>
    @endif
    <div class="footer">WorkLive Pro · Reporte confidencial</div>
</body>
</html>
