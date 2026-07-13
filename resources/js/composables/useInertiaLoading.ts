import { router } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';

/**
 * Expone un `isLoading` reactivo mientras hay una navegación Inertia en curso
 * (router.get/post/etc.), para deshabilitar filtros y mostrar retroalimentación
 * visual en vez de dejar la pantalla "congelada" sin explicación.
 */
export function useInertiaLoading() {
    const isLoading = ref(false);

    const stopStart = router.on('start', () => {
        isLoading.value = true;
    });
    const stopFinish = router.on('finish', () => {
        isLoading.value = false;
    });

    onUnmounted(() => {
        stopStart();
        stopFinish();
    });

    return { isLoading };
}
