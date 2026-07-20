<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AttendanceEvidenceFilters from '@/components/AttendanceEvidenceFilters.vue';
import AttendanceEvidenceGallery from '@/components/AttendanceEvidenceGallery.vue';
import AttendanceEvidenceViewer from '@/components/AttendanceEvidenceViewer.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { AttendancePhoto, Client, PaginatedData, ServicePoint } from '@/types/models';

const props = defineProps<{
    photos: PaginatedData<AttendancePhoto>;
    clients: Client[];
    servicePoints: (ServicePoint & { client_id: number })[];
    supervisors: { id: number; name: string }[];
    filters: Record<string, string | undefined>;
    canReview: boolean;
}>();

type SelectModel = string | number | null;

const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const clientId = ref<SelectModel>(props.filters.client_id ?? '');
const servicePointId = ref<SelectModel>(props.filters.service_point_id ?? '');
const supervisorId = ref<SelectModel>(props.filters.supervisor_id ?? '');
const captureType = ref<SelectModel>(props.filters.capture_type ?? '');
const captureOrigin = ref<SelectModel>(props.filters.capture_origin ?? '');
const status = ref<SelectModel>(props.filters.status ?? '');
const search = ref(props.filters.search ?? '');

const viewerIndex = ref<number | null>(null);

const hasActiveFilters = computed(
    () =>
        !!(
            dateFrom.value ||
            dateTo.value ||
            clientId.value ||
            servicePointId.value ||
            supervisorId.value ||
            captureType.value ||
            captureOrigin.value ||
            status.value ||
            search.value
        ),
);

const applyFilters = () => {
    router.get(
        '/evidencias-asistencia',
        {
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            client_id: clientId.value || undefined,
            service_point_id: servicePointId.value || undefined,
            supervisor_id: supervisorId.value || undefined,
            capture_type: captureType.value || undefined,
            capture_origin: captureOrigin.value || undefined,
            status: status.value || undefined,
            search: search.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch([dateFrom, dateTo, clientId, servicePointId, supervisorId, captureType, captureOrigin, status], applyFilters);

let searchDebounce: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(applyFilters, 400);
});

const clearFilters = () => {
    dateFrom.value = '';
    dateTo.value = '';
    clientId.value = '';
    servicePointId.value = '';
    supervisorId.value = '';
    captureType.value = '';
    captureOrigin.value = '';
    status.value = '';
    search.value = '';
    router.get('/evidencias-asistencia', {}, { preserveState: true, replace: true });
};

const onPage = (page: number) => {
    router.get('/evidencias-asistencia', { ...props.filters, page }, { preserveState: true });
};
</script>

<template>
    <div class="p-6">
        <PageHeader title="Evidencias de Asistencia" description="Revisión rápida de fotografías capturadas en el registro de asistencia" />

        <AttendanceEvidenceFilters
            v-model:date-from="dateFrom"
            v-model:date-to="dateTo"
            v-model:client-id="clientId"
            v-model:service-point-id="servicePointId"
            v-model:supervisor-id="supervisorId"
            v-model:capture-type="captureType"
            v-model:capture-origin="captureOrigin"
            v-model:status="status"
            v-model:search="search"
            :clients="clients"
            :service-points="servicePoints"
            :supervisors="supervisors"
            :has-active-filters="hasActiveFilters"
            @clear="clearFilters"
        />

        <AttendanceEvidenceGallery :photos="photos" @open="viewerIndex = $event" @page-change="onPage" />

        <AttendanceEvidenceViewer
            :photos="photos.data"
            :current-index="viewerIndex"
            @update:current-index="viewerIndex = $event"
            @close="viewerIndex = null"
        />

        <div class="mt-6 flex items-center gap-2 text-xs text-muted-foreground">
            <ShieldCheck class="h-3.5 w-3.5" />
            <span>Las evidencias fotográficas solo son visibles para personal autorizado.</span>
        </div>
    </div>
</template>
