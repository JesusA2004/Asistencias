import { router } from '@inertiajs/vue3';
import { notify } from '@/lib/notify';

/**
 * Router-wide flash handler. Antes escuchaba router.on('flash', ...), un evento que
 * no existe en Inertia (los eventos válidos son before/start/progress/success/error/
 * invalid/exception/finish/navigate) — por eso nunca se disparaba. 'success' sí es un
 * evento real y trae la página ya renderizada (event.detail.page) con los props
 * compartidos, incluido `flash`.
 */
export function initializeFlashToast(): void {
    router.on('success', (event) => {
        const flash = event.detail.page.props.flash as { success?: string | null; error?: string | null } | undefined;

        if (flash?.success) {
            notify.success(flash.success);
        }

        if (flash?.error) {
            notify.error(flash.error);
        }
    });
}
