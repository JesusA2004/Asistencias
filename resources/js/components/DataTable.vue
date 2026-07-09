<script setup lang="ts" generic="TData, TValue">
import { ref } from 'vue';
import {
    type ColumnDef,
    FlexRender,
    getCoreRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ChevronLeft, ChevronRight, Search } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import { Skeleton } from '@/components/ui/skeleton';

const props = withDefaults(defineProps<{
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    loading?: boolean;
    searchable?: boolean;
    searchPlaceholder?: string;
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
});

const emit = defineEmits<{
    'page-change': [page: number];
    search: [query: string];
}>();

const searchQuery = ref('');
let searchTimeout: ReturnType<typeof setTimeout>;

const table = useVueTable({
    get data() { return props.data; },
    get columns() { return props.columns; },
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualFiltering: true,
});

const onSearch = (value: string) => {
    searchQuery.value = value;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => emit('search', value), 400);
};
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

        <div class="rounded-lg border bg-card overflow-hidden">
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
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="loading">
                        <TableRow v-for="i in 8" :key="i">
                            <TableCell v-for="(col, ci) in columns" :key="ci" class="py-3">
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
                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                            </TableCell>
                        </TableRow>
                    </template>
                    <TableRow v-else>
                        <TableCell :colspan="columns.length" class="h-48 p-0">
                            <EmptyState />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between text-sm">
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
    </div>
</template>
