<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { statusVisual } from '@/lib/status';
import type { AttendanceStatus, ClientStatus, EmployeeStatus } from '@/types/models';

const props = defineProps<{
    status: AttendanceStatus | ClientStatus | EmployeeStatus | string;
}>();

const config = computed(() => statusVisual(props.status));
</script>

<template>
    <Badge variant="outline" :class="['text-xs font-medium', config.badgeClass]">
        {{ config.label }}
    </Badge>
</template>
