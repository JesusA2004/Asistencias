<script setup lang="ts">
import { ImageOff } from '@lucide/vue';
import AppPagination from '@/components/AppPagination.vue';
import AttendanceEvidenceCard from '@/components/AttendanceEvidenceCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import type { AttendancePhoto, PaginatedData } from '@/types/models';

defineProps<{
    photos: PaginatedData<AttendancePhoto>;
}>();

const emit = defineEmits<{
    open: [index: number];
    'page-change': [page: number];
}>();
</script>

<template>
    <div>
        <EmptyState
            v-if="!photos.data.length"
            title="Sin evidencias"
            description="No se encontraron fotografías con los filtros seleccionados."
            :icon="ImageOff"
        />

        <template v-else>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                <AttendanceEvidenceCard
                    v-for="(photo, index) in photos.data"
                    :key="photo.id"
                    :photo="photo"
                    @click="emit('open', index)"
                />
            </div>

            <div class="mt-6">
                <AppPagination :pagination="photos" @page-change="emit('page-change', $event)" />
            </div>
        </template>
    </div>
</template>
