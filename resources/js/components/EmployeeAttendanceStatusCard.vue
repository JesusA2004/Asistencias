<script setup lang="ts">
import { Building2, Calendar, Clock, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { captureStateVisual, deriveCaptureState } from '@/lib/attendance';
import { formatDateMx, formatTimeMx, todayLocalYmd } from '@/lib/formatters';
import type { Attendance, Employee } from '@/types/models';

const props = defineProps<{
    employee: Employee & {
        client?: { name: string } | null;
        service_point?: { name: string } | null;
        shift?: { name: string; start_time: string; end_time: string } | null;
    };
    attendance: Attendance | null;
}>();

const today = todayLocalYmd();
const state = computed(() => deriveCaptureState(props.attendance));
const visual = computed(() => captureStateVisual(state.value));
</script>

<template>
    <Card class="border-0 bg-gradient-to-r from-blue-50 to-indigo-50 shadow-sm dark:from-blue-950 dark:to-indigo-950">
        <CardContent class="p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="flex items-start gap-2">
                        <Calendar class="mt-0.5 h-4 w-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">Fecha</p>
                            <p class="text-sm font-medium">{{ formatDateMx(today) }}</p>
                        </div>
                    </div>
                    <div v-if="employee.shift" class="flex items-start gap-2">
                        <Clock class="mt-0.5 h-4 w-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">Turno</p>
                            <p class="text-sm font-medium">
                                {{ employee.shift.name }}
                                <span class="block font-mono text-xs text-muted-foreground">
                                    {{ formatTimeMx(employee.shift.start_time) }}–{{ formatTimeMx(employee.shift.end_time) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div v-if="employee.client" class="flex items-start gap-2">
                        <Building2 class="mt-0.5 h-4 w-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">Empresa</p>
                            <p class="text-sm font-medium">{{ employee.client.name }}</p>
                        </div>
                    </div>
                    <div v-if="employee.service_point" class="flex items-start gap-2">
                        <MapPin class="mt-0.5 h-4 w-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">Punto de servicio</p>
                            <p class="text-sm font-medium">{{ employee.service_point.name }}</p>
                        </div>
                    </div>
                </div>

                <Badge variant="outline" :class="['text-sm px-3 py-1', visual.badgeClass]">{{ visual.label }}</Badge>
            </div>
        </CardContent>
    </Card>
</template>
