import Swal from 'sweetalert2';
import { toast } from 'vue-sonner';

/**
 * Helper centralizado de notificaciones. Toast (vue-sonner) para acciones normales
 * exitosas o informativas; SweetAlert2 para advertencias y errores que sí necesitan
 * detener al usuario un momento (evidencia faltante, cámara denegada, etc.).
 */

const swalBase = {
    confirmButtonColor: 'var(--primary)',
    cancelButtonColor: 'var(--muted-foreground)',
    background: 'var(--popover)',
    color: 'var(--popover-foreground)',
    customClass: {
        popup: 'rounded-xl',
    },
};

function success(message: string, title?: string) {
    toast.success(title ?? message, title ? { description: message } : undefined);
}

function info(message: string, title?: string) {
    toast.info(title ?? message, title ? { description: message } : undefined);
}

function warning(message: string, title = 'Atención') {
    void Swal.fire({
        ...swalBase,
        icon: 'warning',
        title,
        text: message,
        confirmButtonText: 'Entendido',
    });
}

function error(message: string, title = 'Ocurrió un problema') {
    void Swal.fire({
        ...swalBase,
        icon: 'error',
        title,
        text: message,
        confirmButtonText: 'Entendido',
    });
}

interface ConfirmOptions {
    title?: string;
    text?: string;
    icon?: 'warning' | 'question' | 'info' | 'error' | 'success';
    confirmButtonText?: string;
    cancelButtonText?: string;
}

async function confirm(options: ConfirmOptions = {}): Promise<boolean> {
    const result = await Swal.fire({
        ...swalBase,
        icon: options.icon ?? 'question',
        title: options.title ?? '¿Estás seguro?',
        text: options.text,
        showCancelButton: true,
        confirmButtonText: options.confirmButtonText ?? 'Confirmar',
        cancelButtonText: options.cancelButtonText ?? 'Cancelar',
        reverseButtons: true,
    });

    return result.isConfirmed;
}

export const notify = {
    success,
    info,
    warning,
    error,
    confirm,
};
