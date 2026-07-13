<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
}>();

const emit = defineEmits<{
    'page-change': [page: number];
}>();
</script>

<template>
    <div v-if="pagination.last_page > 1" class="flex items-center justify-between text-sm">
        <span class="text-muted-foreground">
            Mostrando {{ pagination.from ?? 0 }}–{{ pagination.to ?? 0 }} de {{ pagination.total }} registros
        </span>
        <div class="flex items-center gap-1">
            <Button
                variant="outline"
                size="sm"
                :disabled="pagination.current_page <= 1"
                @click="emit('page-change', pagination.current_page - 1)"
            >
                <ChevronLeft class="h-4 w-4" />
            </Button>
            <span class="px-3 py-1.5 text-sm border rounded-md bg-muted/30">
                {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="pagination.current_page >= pagination.last_page"
                @click="emit('page-change', pagination.current_page + 1)"
            >
                <ChevronRight class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
