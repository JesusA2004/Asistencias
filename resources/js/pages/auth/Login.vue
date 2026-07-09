<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ShieldCheck, UserCheck, ClipboardList, BarChart3, FileSpreadsheet, Lock } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const features = [
    { icon: ClipboardList, text: 'Captura diaria por supervisor' },
    { icon: UserCheck,     text: 'Control por empresa y ubicación' },
    { icon: BarChart3,     text: 'Reportes de asistencia e incidencias' },
    { icon: FileSpreadsheet, text: 'Exportación Excel y PDF' },
    { icon: ShieldCheck,   text: 'Auditoría completa de cambios' },
];
</script>

<template>
    <Head title="Iniciar sesión — Sistema de Gestión de Asistencias" />

    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- ── Panel izquierdo: Marca ─────────────────────────── -->
        <div class="relative hidden lg:flex lg:w-1/2 flex-col justify-between p-12
                    bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white overflow-hidden">

            <!-- Círculos decorativos de fondo -->
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-blue-600/10 blur-3xl"></div>
                <div class="absolute -bottom-32 -right-16 w-80 h-80 rounded-full bg-indigo-500/15 blur-3xl"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-slate-700/20 blur-2xl"></div>
            </div>

            <!-- Contenido superior: Logo + Nombre -->
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-12">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 shadow-lg">
                        <ShieldCheck class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-blue-400">SGA</p>
                        <p class="text-sm font-medium text-slate-300 leading-tight">Seguridad Privada</p>
                    </div>
                </div>

                <h1 class="text-4xl font-bold leading-tight mb-4">
                    Sistema de Gestión<br />de Asistencias
                </h1>
                <p class="text-slate-300 text-lg leading-relaxed mb-10">
                    Control operativo diario para empresas<br />de seguridad privada.
                </p>

                <ul class="space-y-3">
                    <li
                        v-for="f in features"
                        :key="f.text"
                        class="flex items-center gap-3 text-slate-300 text-sm"
                    >
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600/20 text-blue-400 flex-shrink-0">
                            <component :is="f.icon" class="h-4 w-4" />
                        </span>
                        {{ f.text }}
                    </li>
                </ul>
            </div>

            <!-- Contenido inferior: Tagline -->
            <div class="relative z-10 border-t border-slate-700 pt-6">
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <Lock class="h-3.5 w-3.5 text-blue-400" />
                    <span>Acceso exclusivo para personal autorizado</span>
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    Protegido con roles, permisos y auditoría de cambios
                </p>
            </div>
        </div>

        <!-- ── Panel derecho: Formulario ──────────────────────── -->
        <div class="flex flex-1 flex-col items-center justify-center p-6 sm:p-10 bg-background">

            <!-- Logo visible solo en móvil -->
            <div class="flex lg:hidden items-center gap-3 mb-8">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600">
                    <ShieldCheck class="h-5 w-5 text-white" />
                </div>
                <div>
                    <p class="text-sm font-bold leading-tight">Sistema de Gestión</p>
                    <p class="text-xs text-muted-foreground">de Asistencias</p>
                </div>
            </div>

            <div class="w-full max-w-sm">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold">Iniciar sesión</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Ingresa tus credenciales para acceder al sistema
                    </p>
                </div>

                <!-- Estado de Fortify (e.g., "enlace enviado") -->
                <div
                    v-if="status"
                    class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400"
                >
                    {{ status }}
                </div>

                <!-- Passkeys -->
                <PasskeyVerify class="mb-4" />

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="space-y-4"
                >
                    <div class="space-y-1.5">
                        <Label for="email">Correo electrónico</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            placeholder="correo@empresa.com"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="password">Contraseña</Label>
                            <TextLink
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-xs text-muted-foreground hover:text-foreground"
                                :tabindex="5"
                            >
                                ¿Olvidaste tu contraseña?
                            </TextLink>
                        </div>
                        <PasswordInput
                            id="password"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="Contraseña"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <Label for="remember" class="cursor-pointer text-sm font-normal">
                            Recordarme
                        </Label>
                    </div>

                    <Button
                        type="submit"
                        class="w-full"
                        :tabindex="4"
                        :disabled="processing"
                        data-test="login-button"
                    >
                        <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                        Iniciar sesión
                    </Button>
                </Form>

                <div class="mt-8 flex items-center gap-2 text-xs text-muted-foreground">
                    <Lock class="h-3.5 w-3.5 flex-shrink-0 text-blue-500" />
                    <span>Administración de colaboradores, turnos, ubicaciones y asistencias</span>
                </div>
            </div>
        </div>
    </div>
</template>
