<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Asistencias</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; margin: 20px; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 5px; }
        .subtitle { text-align: center; font-size: 10px; color: #666; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1e40af; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .badge { padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; }
        .presente { background: #d1fae5; color: #065f46; }
        .falta { background: #fee2e2; color: #991b1b; }
        .retardo { background: #ede9fe; color: #4c1d95; }
        .descanso { background: #dbeafe; color: #1e3a8a; }
        .permiso { background: #fef3c7; color: #78350f; }
        .incapacidad { background: #ffedd5; color: #7c2d12; }
        .footer { margin-top: 20px; text-align: right; font-size: 8px; color: #999; }
    </style>
</head>
<body>
    <h1>Reporte de Asistencias</h1>
    <p class="subtitle">
        Periodo: {{ $filters['date_from'] ?? '' }} – {{ $filters['date_to'] ?? '' }}
        &nbsp;|&nbsp; Generado: {{ $generated_at }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>No. Empleado</th>
                <th>Nombre</th>
                <th>Empresa</th>
                <th>Punto de Servicio</th>
                <th>Estado</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Supervisor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $a)
            <tr>
                <td>{{ $a->attendance_date->format('d/m/Y') }}</td>
                <td>{{ $a->employee?->employee_number ?? '-' }}</td>
                <td>{{ $a->employee?->name }} {{ $a->employee?->last_name }}</td>
                <td>{{ $a->client?->name ?? '-' }}</td>
                <td>{{ $a->servicePoint?->name ?? '-' }}</td>
                <td><span class="badge {{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
                <td>{{ $a->entry_time ?? '-' }}</td>
                <td>{{ $a->exit_time ?? '-' }}</td>
                <td>{{ $a->supervisor?->name ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Total de registros: {{ count($attendances) }} &nbsp;|&nbsp; Sistema de Asistencias
    </div>
</body>
</html>
