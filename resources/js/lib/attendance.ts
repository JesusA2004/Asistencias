/**
 * Única fuente de verdad para el estado operativo de captura de un colaborador
 * (usado por el picker, los badges y los paneles de Capturar Asistencia) y para
 * la sugerencia de Presente/Retardo según la tolerancia del turno.
 */

import { formatTimeMx } from '@/lib/formatters';
import { statusVisual } from '@/lib/status';
import type { Attendance, AttendanceStatus } from '@/types/models';

export type CaptureState = 'sin_registro' | 'pendiente_salida' | 'completo' | AttendanceStatus;

/**
 * Deriva el estado operativo de un colaborador a partir de su asistencia del día
 * (o su ausencia). Un registro con entrada y sin salida está "pendiente_salida";
 * con ambas, "completo"; sin entrada, es un estado terminal (falta/descanso/
 * permiso/incapacidad, o un retardo manual sin hora).
 */
export function deriveCaptureState(attendance?: Attendance | null): CaptureState {
    if (!attendance) {
        return 'sin_registro';
    }

    if (attendance.entry_time && attendance.exit_time) {
        return 'completo';
    }

    if (attendance.entry_time && !attendance.exit_time) {
        return 'pendiente_salida';
    }

    return attendance.status;
}

export function captureStateVisual(state: CaptureState): { label: string; badgeClass: string } {
    switch (state) {
        case 'sin_registro':
            return { label: 'Sin registro', badgeClass: 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400' };
        case 'pendiente_salida':
            return { label: 'Pendiente salida', badgeClass: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950 dark:text-amber-400' };
        case 'completo':
            return { label: 'Completo', badgeClass: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-400' };
        default:
            return statusVisual(state);
    }
}

/** Etiqueta contextual con hora cuando aplica: "Entrada: 08:02", "Salida: 18:01", "Sin registro", "Falta"... */
export function captureStateLabel(attendance?: Attendance | null): string {
    const state = deriveCaptureState(attendance);

    if (state === 'pendiente_salida') {
        return `Entrada: ${formatTimeMx(attendance?.entry_time)}`;
    }

    if (state === 'completo') {
        return `Salida: ${formatTimeMx(attendance?.exit_time)}`;
    }

    return captureStateVisual(state).label;
}

export interface ShiftTolerance {
    start_time: string;
    tolerance_minutes: number;
}

function toMinutes(time: string): number {
    const [h, m] = time.split(':').map(Number);

    return h * 60 + (m ?? 0);
}

/** Presente si la entrada está dentro de start_time + tolerancia; Retardo si la excede. */
export function suggestEntryStatus(entryTime: string, shift?: ShiftTolerance | null): 'presente' | 'retardo' {
    if (!shift || !entryTime) {
        return 'presente';
    }

    const entryMinutes = toMinutes(entryTime);
    const limitMinutes = toMinutes(shift.start_time) + (shift.tolerance_minutes ?? 0);

    return entryMinutes > limitMinutes ? 'retardo' : 'presente';
}

/** "HH:mm" en hora local del navegador, para prellenar los campos de hora con "ahora". */
export function nowTime(): string {
    const d = new Date();

    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}
