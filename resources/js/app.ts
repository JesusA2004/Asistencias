import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .component('VueApexCharts', VueApexCharts)
            .mount(el as Element);
    },
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name === 'auth/Login':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
initializeFlashToast();
