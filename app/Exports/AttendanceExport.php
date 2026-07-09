<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly array $filters) {}

    public function query()
    {
        return Attendance::with([
            'employee:id,employee_number,name,last_name',
            'client:id,name',
            'servicePoint:id,name',
            'supervisor:id,name',
        ])
            ->whereBetween('attendance_date', [$this->filters['date_from'], $this->filters['date_to']])
            ->when($this->filters['client_id'] ?? null, fn ($q, $c) => $q->where('client_id', $c))
            ->when($this->filters['service_point_id'] ?? null, fn ($q, $sp) => $q->where('service_point_id', $sp))
            ->when($this->filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('attendance_date')
            ->orderBy('employee_id');
    }

    public function headings(): array
    {
        return [
            'ID', 'Fecha', 'No. Empleado', 'Nombre', 'Apellidos',
            'Empresa', 'Punto de Servicio', 'Estado', 'Entrada', 'Salida',
            'Notas', 'Supervisor',
        ];
    }

    public function map($attendance): array
    {
        return [
            $attendance->id,
            $attendance->attendance_date->format('d/m/Y'),
            $attendance->employee?->employee_number ?? '',
            $attendance->employee?->name ?? '',
            trim(($attendance->employee?->last_name ?? '') . ' ' . ($attendance->employee?->second_last_name ?? '')),
            $attendance->client?->name ?? '',
            $attendance->servicePoint?->name ?? '',
            ucfirst($attendance->status),
            $attendance->entry_time ?? '',
            $attendance->exit_time ?? '',
            $attendance->notes ?? '',
            $attendance->supervisor?->name ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
