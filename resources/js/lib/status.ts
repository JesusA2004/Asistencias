/**
 * Fuente única de colores/labels por estado y acción. Antes estaba duplicado
 * de forma independiente en StatusBadge, AttendanceCapture, Reports y Audit,
 * cada uno con tonos ligeramente distintos — este módulo es el que todos
 * deben consumir para que un mismo estado se vea igual en todo el sistema.
 */

import type { AttendanceStatus } from '@/types/models';

export type StatusKey =
    | 'presente' | 'falta' | 'descanso' | 'permiso' | 'incapacidad' | 'retardo'
    | 'activo' | 'inactivo' | 'baja';

export type AuditActionKey = 'creado' | 'actualizado' | 'corregido' | 'eliminado';

interface StatusVisual {
    label: string;
    /** Clases para Badge variant="outline" (fondo suave + texto + borde). */
    badgeClass: string;
    /** Pill inactivo/seleccionable (botones de estado en captura). */
    pillClass: string;
    /** Pill cuando está seleccionado/activo. */
    activePillClass: string;
}

export const ATTENDANCE_STATUS_CONFIG: Record<string, StatusVisual> = {
    presente: {
        label: 'Presente',
        badgeClass: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-400',
        pillClass: 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100 dark:bg-green-950/40 dark:text-green-400 dark:border-green-900',
        activePillClass: 'bg-green-600 text-white border-green-600 hover:bg-green-600',
    },
    falta: {
        label: 'Falta',
        badgeClass: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-400',
        pillClass: 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-400 dark:border-red-900',
        activePillClass: 'bg-red-600 text-white border-red-600 hover:bg-red-600',
    },
    retardo: {
        label: 'Retardo',
        badgeClass: 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-950 dark:text-purple-400',
        pillClass: 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-900',
        activePillClass: 'bg-purple-600 text-white border-purple-600 hover:bg-purple-600',
    },
    descanso: {
        label: 'Descanso',
        badgeClass: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-400',
        pillClass: 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900',
        activePillClass: 'bg-blue-600 text-white border-blue-600 hover:bg-blue-600',
    },
    permiso: {
        label: 'Permiso',
        badgeClass: 'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-950 dark:text-yellow-400',
        pillClass: 'bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100 dark:bg-yellow-950/40 dark:text-yellow-400 dark:border-yellow-900',
        activePillClass: 'bg-yellow-600 text-white border-yellow-600 hover:bg-yellow-600',
    },
    incapacidad: {
        label: 'Incapacidad',
        badgeClass: 'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-950 dark:text-orange-400',
        pillClass: 'bg-orange-50 text-orange-700 border-orange-200 hover:bg-orange-100 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-900',
        activePillClass: 'bg-orange-600 text-white border-orange-600 hover:bg-orange-600',
    },
    activo: {
        label: 'Activo',
        badgeClass: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-400',
        pillClass: '',
        activePillClass: '',
    },
    inactivo: {
        label: 'Inactivo',
        badgeClass: 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400',
        pillClass: '',
        activePillClass: '',
    },
    baja: {
        label: 'Baja',
        badgeClass: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-400',
        pillClass: '',
        activePillClass: '',
    },
};

export const ATTENDANCE_STATUS_ORDER: AttendanceStatus[] = ['presente', 'falta', 'retardo', 'descanso', 'permiso', 'incapacidad'];

/** Mismos tonos que activePillClass (bg-*-600), en hex, para usarse en gráficas ApexCharts. */
export const ATTENDANCE_STATUS_HEX: Record<AttendanceStatus, string> = {
    presente: '#16a34a',
    falta: '#dc2626',
    retardo: '#9333ea',
    descanso: '#2563eb',
    permiso: '#ca8a04',
    incapacidad: '#ea580c',
};

export const ATTENDANCE_STATUS_OPTIONS = ATTENDANCE_STATUS_ORDER.map((value) => ({
    value,
    label: ATTENDANCE_STATUS_CONFIG[value].label,
}));

export function statusVisual(status: string): StatusVisual {
    return ATTENDANCE_STATUS_CONFIG[status] ?? {
        label: status,
        badgeClass: 'bg-gray-100 text-gray-600 border-gray-200',
        pillClass: 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100',
        activePillClass: 'bg-gray-600 text-white border-gray-600 hover:bg-gray-600',
    };
}

export const AUDIT_ACTION_CONFIG: Record<AuditActionKey, { label: string; badgeClass: string }> = {
    creado: { label: 'Creado', badgeClass: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-400' },
    actualizado: { label: 'Actualizado', badgeClass: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-400' },
    corregido: { label: 'Corregido', badgeClass: 'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-950 dark:text-yellow-400' },
    eliminado: { label: 'Eliminado', badgeClass: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-400' },
};

export function auditActionVisual(action: string) {
    return AUDIT_ACTION_CONFIG[action as AuditActionKey] ?? { label: action, badgeClass: 'bg-gray-100 text-gray-600 border-gray-200' };
}
