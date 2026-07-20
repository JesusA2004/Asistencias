/**
 * Formateadores de fecha/hora para México (es-MX).
 *
 * Laravel entrega fechas "solo fecha" como "YYYY-MM-DD" (sin hora ni zona) y
 * fechas/horas completas como ISO 8601 en UTC ("...Z"). `new Date('YYYY-MM-DD')`
 * interpreta el valor como medianoche UTC, lo que en México (UTC-6) se muestra
 * como el día anterior. Por eso las fechas "solo fecha" siempre se parsean a mano.
 */

const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const MESES = [
    'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
];

type DateInput = string | Date | null | undefined;

/**
 * Convierte cualquier valor recibido del backend a un objeto Date "correcto"
 * en hora local del navegador, sin desfases por zona horaria.
 */
export function parseServerDate(value: DateInput): Date | null {
    if (!value) {
        return null;
    }

    if (value instanceof Date) {
        return isNaN(value.getTime()) ? null : value;
    }

    const raw = value.trim();

    if (!raw) {
        return null;
    }

    // Fecha simple "YYYY-MM-DD" -> construir en hora local, nunca UTC.
    const dateOnly = /^(\d{4})-(\d{2})-(\d{2})$/.exec(raw);

    if (dateOnly) {
        const [, y, m, d] = dateOnly;

        return new Date(Number(y), Number(m) - 1, Number(d));
    }

    // Fecha/hora con zona explícita (Z u offset +/-HH:mm) -> es un instante
    // absoluto, seguro delegarlo al motor JS.
    if (/Z$|[+-]\d{2}:\d{2}$/.test(raw)) {
        const parsed = new Date(raw);

        return isNaN(parsed.getTime()) ? null : parsed;
    }

    // Fecha/hora "naive" tipo "YYYY-MM-DD HH:mm:ss" o con T y sin zona ->
    // se interpreta como hora local componente a componente.
    const dateTime = /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?/.exec(raw);

    if (dateTime) {
        const [, y, m, d, h, mi, s] = dateTime;

        return new Date(Number(y), Number(m) - 1, Number(d), Number(h), Number(mi), Number(s ?? 0));
    }

    const fallback = new Date(raw);

    return isNaN(fallback.getTime()) ? null : fallback;
}

function pad2(value: number): string {
    return String(value).padStart(2, '0');
}

/**
 * "Hoy" en formato "YYYY-MM-DD" usando la hora local del navegador.
 * `new Date().toISOString().split('T')[0]` usa UTC y en México (UTC-6) puede
 * regresar el día anterior cerca de la medianoche — no usar ese patrón.
 */
export function todayLocalYmd(): string {
    const d = new Date();

    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
}

function formatHora12(date: Date): string {
    let hours = date.getHours();
    const minutes = pad2(date.getMinutes());
    const suffix = hours >= 12 ? 'p.m.' : 'a.m.';
    hours = hours % 12;

    if (hours === 0) {
        hours = 12;
    }

    return `${pad2(hours)}:${minutes} ${suffix}`;
}

/** "miércoles 9 de abril de 2026" */
export function formatDateMx(value: DateInput): string {
    const date = parseServerDate(value);

    if (!date) {
        return '—';
    }

    const dia = DIAS[date.getDay()];
    const mes = MESES[date.getMonth()];

    return `${dia} ${date.getDate()} de ${mes} de ${date.getFullYear()}`;
}

/** "miércoles 9 de abril de 2026, 09:41 p.m." */
export function formatDateTimeMx(value: DateInput): string {
    const date = parseServerDate(value);

    if (!date) {
        return '—';
    }

    return `${formatDateMx(value)}, ${formatHora12(date)}`;
}

/** "09/04/2026" */
export function formatShortDateMx(value: DateInput): string {
    const date = parseServerDate(value);

    if (!date) {
        return '—';
    }

    return `${pad2(date.getDate())}/${pad2(date.getMonth() + 1)}/${date.getFullYear()}`;
}

/** "09:41 p.m." — acepta Date, ISO completo o "HH:mm[:ss]" (columnas TIME de MySQL). */
export function formatTimeMx(value: DateInput): string {
    if (!value) {
        return '—';
    }

    if (typeof value === 'string') {
        const timeOnly = /^(\d{2}):(\d{2})(?::(\d{2}))?$/.exec(value.trim());

        if (timeOnly) {
            const [, h, m] = timeOnly;
            const date = new Date(2000, 0, 1, Number(h), Number(m));

            return formatHora12(date);
        }
    }

    const date = parseServerDate(value);

    if (!date) {
        return '—';
    }

    return formatHora12(date);
}

/** "miércoles 9 de abril" — versión corta sin año, útil en encabezados/gráficas. */
export function formatDateShortLabelMx(value: DateInput): string {
    const date = parseServerDate(value);

    if (!date) {
        return '—';
    }

    const dia = DIAS[date.getDay()];
    const mes = MESES[date.getMonth()];

    return `${dia} ${date.getDate()} de ${mes}`;
}
