<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ShieldCheck,
    ClipboardList,
    MapPin,
    BarChart3,
    History,
    Users,
    CalendarCheck,
    KeyRound,
    FileSpreadsheet,
    ArrowRight,
    CheckCircle2,
} from '@lucide/vue';
import { dashboard, login } from '@/routes';

const features = [
    {
        icon: ClipboardList,
        title: 'Captura por supervisor',
        desc: 'Los supervisores registran la asistencia diaria de su equipo sin requerir lectores biométricos.',
        color: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
    },
    {
        icon: MapPin,
        title: 'Control por ubicación',
        desc: 'Organiza las empresas y sus puntos de servicio. Cada asistencia queda vinculada a una ubicación.',
        color: 'bg-green-500/10 text-green-600 dark:text-green-400',
    },
    {
        icon: BarChart3,
        title: 'Reportes y exportaciones',
        desc: 'Genera reportes filtrados por empresa, ubicación, turno o colaborador. Exporta a Excel y PDF.',
        color: 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
    },
    {
        icon: History,
        title: 'Auditoría administrativa',
        desc: 'Cada corrección o eliminación queda registrada con el motivo, fecha y usuario responsable.',
        color: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    },
];

const kpis = [
    { icon: Users,          value: '3,000+',    label: 'Colaboradores' },
    { icon: CalendarCheck,  value: 'Diaria',    label: 'Captura de asistencia' },
    { icon: KeyRound,       value: '4 Roles',   label: 'Control de acceso' },
    { icon: FileSpreadsheet,value: 'Excel/PDF', label: 'Exportación de reportes' },
];

const benefits = [
    'Roles diferenciados: administrador, supervisor, colaborador y RH',
    'Correcciones administrativas con motivo obligatorio y auditoría',
    'Paginación del lado del servidor — ideal para grandes plantillas',
    'Sin registro público — el administrador controla el acceso',
];
</script>

<template>
    <Head title="Sistema de Gestión de Asistencias" />

    <div class="min-h-screen bg-background text-foreground">

        <!-- ─── HEADER ───────────────────────────────────────────── -->
        <header class="sticky top-0 z-50 border-b bg-background/80 backdrop-blur-sm">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600">
                        <ShieldCheck class="h-5 w-5 text-white" />
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-bold leading-tight">Sistema de Gestión</p>
                        <p class="text-xs text-muted-foreground leading-tight">de Asistencias</p>
                    </div>
                </div>
                <nav>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
                    >
                        Ir al panel
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
                    >
                        Iniciar sesión
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </nav>
            </div>
        </header>

        <!-- ─── HERO ──────────────────────────────────────────────── -->
        <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white">
            <!-- Decorative circles -->
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute -top-40 -right-40 h-[500px] w-[500px] rounded-full bg-blue-600/10 blur-3xl"></div>
                <div class="absolute -bottom-40 -left-20 h-96 w-96 rounded-full bg-indigo-500/15 blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-6xl px-6 py-24 sm:py-32">
                <div class="max-w-2xl">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-blue-500/30 bg-blue-500/10 px-3 py-1 text-xs font-medium text-blue-300">
                        <ShieldCheck class="h-3.5 w-3.5" />
                        Seguridad Privada — Plataforma Operativa
                    </div>
                    <h1 class="text-4xl sm:text-5xl font-bold leading-tight mb-6">
                        Sistema de Gestión<br />de Asistencias
                    </h1>
                    <p class="text-slate-300 text-lg leading-relaxed mb-4">
                        Control diario de asistencia para empresas de seguridad privada.
                    </p>
                    <p class="text-slate-400 text-base leading-relaxed mb-10">
                        Plataforma administrativa para supervisores, colaboradores y administradores.
                        Registra asistencias por ubicación, controla incidencias, consulta reportes
                        y conserva auditoría de cambios.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition-colors shadow-lg shadow-blue-900/30"
                        >
                            Ir al panel principal
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                        <Link
                            v-else
                            :href="login()"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition-colors shadow-lg shadow-blue-900/30"
                        >
                            Iniciar sesión
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── KPIs ──────────────────────────────────────────────── -->
        <section class="border-b bg-muted/30">
            <div class="mx-auto max-w-6xl px-6 py-12">
                <div class="grid grid-cols-2 gap-6 md:grid-cols-4">
                    <div
                        v-for="kpi in kpis"
                        :key="kpi.label"
                        class="flex flex-col items-center gap-2 text-center"
                    >
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600/10 text-blue-600 dark:text-blue-400">
                            <component :is="kpi.icon" class="h-6 w-6" />
                        </div>
                        <p class="text-2xl font-bold">{{ kpi.value }}</p>
                        <p class="text-xs text-muted-foreground">{{ kpi.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── FEATURES ──────────────────────────────────────────── -->
        <section class="mx-auto max-w-6xl px-6 py-20">
            <div class="mb-12 text-center">
                <h2 class="text-2xl sm:text-3xl font-bold mb-3">
                    Todo lo que necesitas en un solo lugar
                </h2>
                <p class="text-muted-foreground max-w-xl mx-auto">
                    Diseñado específicamente para operaciones de seguridad privada con múltiples
                    empresas, ubicaciones y equipos.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div
                    v-for="f in features"
                    :key="f.title"
                    class="rounded-xl border bg-card p-6 shadow-sm hover:shadow-md transition-shadow"
                >
                    <div :class="['mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg', f.color]">
                        <component :is="f.icon" class="h-5 w-5" />
                    </div>
                    <h3 class="font-semibold mb-2">{{ f.title }}</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">{{ f.desc }}</p>
                </div>
            </div>
        </section>

        <!-- ─── BENEFICIOS ────────────────────────────────────────── -->
        <section class="bg-muted/30 border-y">
            <div class="mx-auto max-w-6xl px-6 py-16">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 class="text-2xl font-bold mb-4">Control total con acceso seguro</h2>
                        <p class="text-muted-foreground mb-8">
                            El acceso al sistema es exclusivo para personal autorizado.
                            No existe registro público — el administrador gestiona todos los usuarios
                            desde el panel de control.
                        </p>
                        <ul class="space-y-3">
                            <li
                                v-for="b in benefits"
                                :key="b"
                                class="flex items-start gap-3 text-sm"
                            >
                                <CheckCircle2 class="h-4 w-4 text-green-500 flex-shrink-0 mt-0.5" />
                                <span>{{ b }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="flex justify-center">
                        <div class="relative">
                            <div class="rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-10 shadow-2xl text-white text-center">
                                <ShieldCheck class="h-16 w-16 mx-auto mb-4 text-blue-400" />
                                <p class="text-lg font-semibold mb-1">Acceso exclusivo</p>
                                <p class="text-sm text-slate-400">para personal autorizado</p>
                                <div class="mt-6 flex justify-center">
                                    <Link
                                        :href="$page.props.auth.user ? dashboard() : login()"
                                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
                                    >
                                        {{ $page.props.auth.user ? 'Ir al panel' : 'Iniciar sesión' }}
                                        <ArrowRight class="h-4 w-4" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── FOOTER ────────────────────────────────────────────── -->
        <footer class="border-t py-8">
            <div class="mx-auto max-w-6xl px-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-muted-foreground">
                    <div class="flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-600">
                            <ShieldCheck class="h-3.5 w-3.5 text-white" />
                        </div>
                        <span class="font-medium text-foreground">Sistema de Gestión de Asistencias</span>
                    </div>
                    <p>Plataforma operativa para seguridad privada &mdash; Uso interno</p>
                </div>
            </div>
        </footer>
    </div>
</template>
