<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { login } from '@/routes';

// El registro público está deshabilitado.
// Si alguien llega a esta página, se redirige al login.
onMounted(() => {
    window.location.href = login();
});
</script>

<template>
    <Head title="Acceso no permitido — SGA" />
    <div class="flex min-h-screen items-center justify-center">
        <p class="text-sm text-muted-foreground">Redirigiendo al inicio de sesión...</p>
    </div>
</template>
