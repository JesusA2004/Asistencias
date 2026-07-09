<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { LucideIcon } from '@lucide/vue';

defineProps<{
    title: string;
    value: string | number;
    subtitle?: string;
    icon?: LucideIcon;
    trend?: number;
    color?: 'blue' | 'green' | 'red' | 'yellow' | 'purple' | 'orange';
}>();

const colorMap: Record<string, string> = {
    blue: 'text-blue-600 bg-blue-50 dark:bg-blue-950 dark:text-blue-400',
    green: 'text-green-600 bg-green-50 dark:bg-green-950 dark:text-green-400',
    red: 'text-red-600 bg-red-50 dark:bg-red-950 dark:text-red-400',
    yellow: 'text-yellow-600 bg-yellow-50 dark:bg-yellow-950 dark:text-yellow-400',
    purple: 'text-purple-600 bg-purple-50 dark:bg-purple-950 dark:text-purple-400',
    orange: 'text-orange-600 bg-orange-50 dark:bg-orange-950 dark:text-orange-400',
};
</script>

<template>
    <Card class="border-0 shadow-sm hover:shadow-md transition-shadow duration-200">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">{{ title }}</CardTitle>
            <div v-if="icon" :class="['p-2 rounded-lg', colorMap[color ?? 'blue']]">
                <component :is="icon" class="h-4 w-4" />
            </div>
        </CardHeader>
        <CardContent>
            <div class="text-2xl font-bold">{{ value }}</div>
            <p v-if="subtitle" class="text-xs text-muted-foreground mt-1">{{ subtitle }}</p>
            <div v-if="trend !== undefined" class="flex items-center mt-1">
                <span :class="trend >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium">
                    {{ trend >= 0 ? '+' : '' }}{{ trend }}%
                </span>
                <span class="text-xs text-muted-foreground ml-1">vs ayer</span>
            </div>
        </CardContent>
    </Card>
</template>
