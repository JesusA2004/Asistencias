<script setup lang="ts" generic="TData, TValue">
import type { LucideIcon } from '@lucide/vue';
import { Search } from '@lucide/vue';
import {

    FlexRender,
    getCoreRowModel,
    useVueTable
} from '@tanstack/vue-table';
import type {ColumnDef} from '@tanstack/vue-table';
import { ref, useSlots } from 'vue';
import AppPagination from '@/components/AppPagination.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const props = withDefaults(defineProps<{
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    loading?: boolean;
    searchable?: boolean;
    searchPlaceholder?: string;
    emptyTitle?: string;
    emptyDescription?: string;
    emptyIcon?: LucideIcon;
    actionsLabel?: string;
    pagination?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
}>(), {
    loading: false,
    searchable: true,
    searchPlaceholder: 'Buscar...',
    emptyTitle: undefined,
    emptyDescription: undefined,
    emptyIcon: undefined,
    actionsLabel: '',
});

const emit = defineEmits<{
    'page-change': [page: number];
    search: [query: string];
}>();

const slots = useSlots();

const searchQuery = ref('');
let searchTimeout: ReturnType<typeof setTimeout>;

const table = useVueTable({
    get data() {
 return props.data; 
},
    get columns() {
 return props.columns; 
},
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualFiltering: true,
});

const onSearch = (value: string) => {
    searchQuery.value = value;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => emit('search', value), 400);
};

const totalColumns = () => props.columns.length + (slots.actions ? 1 : 0);
</script>

<template>
    <div class="space-y-4">
        <div v-if="searchable || $slots.filters" class="flex items-center gap-3 flex-wrap">
            <div v-if="searchable" class="relative flex-1 min-w-[200px] max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                    :model-value="searchQuery"
                    @update:model-value="onSearch(String($event))"
                    :placeholder="searchPlaceholder"
                    class="pl-9"
                />
            </div>
            <slot name="filters" />
        </div>

        <!-- Desktop ancho real: tabla. Bajo xl siempre son cards — nunca overflow-x-auto. -->
        <div class="hidden xl:block rounded-lg border bg-card">
            <div class="min-w-0">
                <Table>
                    <TableHeader>
                        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id" class="bg-muted/30">
                            <TableHead
                                v-for="header in headerGroup.headers"
                                :key="header.id"
                                class="font-semibold text-xs uppercase tracking-wider text-muted-foreground"
                            >
                                <FlexRender
                                    v-if="!header.isPlaceholder"
                                    :render="header.column.columnDef.header"
                                    :props="header.getContext()"
                                />
                            </TableHead>
                            <TableHead v-if="$slots.actions" class="w-px text-right text-xs uppercase tracking-wider text-muted-foreground font-semibold">
                                {{ actionsLabel }}
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="loading">
                            <TableRow v-for="i in 8" :key="i">
                                <TableCell v-for="ci in totalColumns()" :key="ci" class="py-3">
                                    <Skeleton class="h-4 w-full" />
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else-if="table.getRowModel().rows.length">
                            <TableRow
                                v-for="row in table.getRowModel().rows"
                                :key="row.id"
                                class="hover:bg-muted/40 transition-colors"
                            >
                                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" class="py-3">
                                    <slot v-if="$slots[`cell-${cell.column.id}`]" :name="`cell-${cell.column.id}`" :item="row.original" :value="cell.getValue()" />
                                    <FlexRender v-else :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                </TableCell>
                                <TableCell v-if="$slots.actions" class="py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <slot name="actions" :item="row.original" />
                                    </div>
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableRow v-else>
                            <TableCell :colspan="totalColumns()" class="h-48 p-0">
                                <EmptyState :title="emptyTitle" :description="emptyDescription" :icon="emptyIcon" />
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Mobile/tablet: cards -->
        <div class="xl:hidden space-y-3">
            <template v-if="loading">
                <div v-for="i in 4" :key="i" class="rounded-lg border bg-card p-4 space-y-2">
                    <Skeleton class="h-4 w-2/3" />
                    <Skeleton class="h-3 w-1/2" />
                </div>
            </template>
            <template v-else-if="table.getRowModel().rows.length">
                <template v-for="row in table.getRowModel().rows" :key="row.id">
                    <slot v-if="$slots['mobile-card']" name="mobile-card" :item="row.original" />
                    <div
                        v-else
                        class="rounded-lg border bg-card p-4 shadow-sm hover:shadow-md transition-all space-y-2"
                    >
                        <div
                            v-for="(cell, ci) in row.getVisibleCells()"
                            :key="cell.id"
                            :class="ci === 0 ? 'font-medium text-sm' : 'flex items-center justify-between gap-2 text-sm'"
                        >
                            <span v-if="ci !== 0" class="text-xs text-muted-foreground uppercase tracking-wide shrink-0">
                                {{ typeof cell.column.columnDef.header === 'string' ? cell.column.columnDef.header : cell.column.id }}
                            </span>
                            <span class="min-w-0 text-right" :class="{ 'text-left w-full': ci === 0 }">
                                <slot v-if="$slots[`cell-${cell.column.id}`]" :name="`cell-${cell.column.id}`" :item="row.original" :value="cell.getValue()" />
                                <FlexRender v-else :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                            </span>
                        </div>
                        <div v-if="$slots.actions" class="flex items-center justify-end gap-1 pt-2 border-t mt-2">
                            <slot name="actions" :item="row.original" />
                        </div>
                    </div>
                </template>
            </template>
            <EmptyState v-else :title="emptyTitle" :description="emptyDescription" :icon="emptyIcon" />
        </div>

        <AppPagination v-if="pagination" :pagination="pagination" @page-change="emit('page-change', $event)" />
    </div>
</template>
