<script setup lang="ts">
import type { ApexOptions } from 'apexcharts';
import { defineAsyncComponent } from 'vue';

const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'));

withDefaults(defineProps<{
    type: 'line' | 'bar' | 'area' | 'donut' | 'pie' | 'radialBar';
    series: ApexOptions['series'];
    options?: Partial<ApexOptions>;
    height?: number;
}>(), {
    height: 300,
});

const baseOptions: Partial<ApexOptions> = {
    chart: {
        toolbar: { show: false },
        background: 'transparent',
        fontFamily: 'inherit',
    },
    grid: {
        borderColor: 'rgba(0,0,0,0.06)',
        strokeDashArray: 4,
    },
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
};
</script>

<template>
    <VueApexCharts
        :type="type"
        :height="height"
        :series="series"
        :options="{ ...baseOptions, ...options }"
    />
</template>
