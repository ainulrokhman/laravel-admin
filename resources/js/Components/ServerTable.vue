<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import axios from 'axios';

// Server-side table speaking the Yajra DataTables protocol: it sends draw,
// start, length, search and order parameters and renders the returned page.
// Cell rendering is delegated to the parent through `cell-<key>` slots, so
// this component only owns fetching, sorting and pagination state.
const props = defineProps({
    url: {
        type: String,
        required: true,
    },
    // [{ key, label, name, orderable, searchable, align ('end'|'center'), width }]
    // `name` is the Yajra column name used for search/order when it differs
    // from the payload key (defaults to `key`).
    columns: {
        type: Array,
        required: true,
    },
    search: {
        type: String,
        default: '',
    },
    pageLength: {
        type: Number,
        default: 10,
    },
    // Bump this value to force a reload (e.g. after an inline create form
    // on the same page added a row).
    refreshKey: {
        type: [Number, String],
        default: 0,
    },
    // Initial ordering as { index, dir }; defaults to the first orderable
    // column ascending, like the DataTables default.
    defaultOrder: {
        type: Object,
        default: null,
    },
});

const lengthOptions = [10, 25, 50, 100];

const rows = ref([]);
const recordsTotal = ref(0);
const recordsFiltered = ref(0);
const page = ref(1);
const perPage = ref(props.pageLength);
const loading = ref(false);

const firstOrderable = props.columns.findIndex((column) => column.orderable !== false);
const initialOrderIndex = props.defaultOrder ? props.defaultOrder.index : firstOrderable;
const sortKey = ref(initialOrderIndex >= 0 ? props.columns[initialOrderIndex].key : null);
const sortDir = ref(props.defaultOrder ? props.defaultOrder.dir : 'asc');

let drawCounter = 0;
let searchTimer = null;

const pageCount = computed(() => Math.max(1, Math.ceil(recordsFiltered.value / perPage.value)));

const showingFrom = computed(() => (recordsFiltered.value === 0 ? 0 : (page.value - 1) * perPage.value + 1));
const showingTo = computed(() => Math.min(recordsFiltered.value, page.value * perPage.value));

// Page numbers with an ellipsis window, like the DataTables pager.
const pageNumbers = computed(() => {
    const total = pageCount.value;
    const current = page.value;
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    const pages = [1];
    if (current > 3) pages.push('…');
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
        pages.push(i);
    }
    if (current < total - 2) pages.push('…');
    pages.push(total);
    return pages;
});

function buildParams() {
    const params = {
        draw: ++drawCounter,
        start: (page.value - 1) * perPage.value,
        length: perPage.value,
        'search[value]': props.search,
        'search[regex]': 'false',
    };

    props.columns.forEach((column, index) => {
        params[`columns[${index}][data]`] = column.key;
        params[`columns[${index}][name]`] = column.name ?? column.key;
        params[`columns[${index}][searchable]`] = column.searchable === false ? 'false' : 'true';
        params[`columns[${index}][orderable]`] = column.orderable === false ? 'false' : 'true';
        params[`columns[${index}][search][value]`] = '';
        params[`columns[${index}][search][regex]`] = 'false';
    });

    const sortIndex = props.columns.findIndex((column) => column.key === sortKey.value);
    if (sortIndex >= 0) {
        params['order[0][column]'] = sortIndex;
        params['order[0][dir]'] = sortDir.value;
    }

    return params;
}

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get(props.url, {
            params: buildParams(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        rows.value = data.data;
        recordsTotal.value = data.recordsTotal;
        recordsFiltered.value = data.recordsFiltered;
        if (page.value > pageCount.value) {
            page.value = pageCount.value;
        }
    } finally {
        loading.value = false;
    }
}

function toggleSort(column) {
    if (column.orderable === false) {
        return;
    }
    if (sortKey.value === column.key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = column.key;
        sortDir.value = 'asc';
    }
    page.value = 1;
    load();
}

function goToPage(target) {
    if (target < 1 || target > pageCount.value || target === page.value) {
        return;
    }
    page.value = target;
    load();
}

watch(
    () => props.search,
    () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            page.value = 1;
            load();
        }, 300);
    },
);

watch(perPage, () => {
    page.value = 1;
    load();
});

watch(
    () => props.refreshKey,
    () => load(),
);

onMounted(load);
</script>

<template>
    <div>
        <!-- Length menu -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
            <label class="d-flex align-items-center gap-2 text-secondary small mb-0">
                Show
                <select v-model.number="perPage" class="form-select form-select-sm" style="width: auto;">
                    <option v-for="length in lengthOptions" :key="length" :value="length">{{ length }}</option>
                </select>
                entries
            </label>
            <span v-if="loading" class="text-primary small">
                <span class="spinner-border spinner-border-sm me-1" role="status"><span class="visually-hidden">Loading...</span></span>
                Loading...
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="width: 100%;">
                <thead class="table-light">
                    <tr>
                        <th v-for="column in columns"
                            :key="column.key"
                            class="text-secondary fw-semibold"
                            :class="{
                                'text-end': column.align === 'end',
                                'text-center': column.align === 'center',
                            }"
                            :style="column.width ? { width: column.width } : null"
                            :role="column.orderable === false ? null : 'button'"
                            @click="toggleSort(column)">
                            {{ column.label }}
                            <i v-if="sortKey === column.key" :class="sortDir === 'asc' ? 'bi bi-caret-up-fill' : 'bi bi-caret-down-fill'" class="small"></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, rowIndex) in rows" :key="row.id ?? rowIndex">
                        <td v-for="column in columns" :key="column.key" :class="{ 'text-end': column.align === 'end', 'text-center': column.align === 'center' }">
                            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                                {{ row[column.key] }}
                            </slot>
                        </td>
                    </tr>
                    <tr v-if="!loading && rows.length === 0">
                        <td :colspan="columns.length" class="text-center text-muted py-4">
                            {{ recordsTotal === 0 ? 'No data available in table' : 'No matching records found' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Info + pagination -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-4 pt-3 border-top">
            <div class="text-secondary small">
                Showing {{ showingFrom }} to {{ showingTo }} of {{ recordsFiltered }} entries
                <span v-if="recordsFiltered !== recordsTotal">(filtered from {{ recordsTotal }} total entries)</span>
            </div>
            <nav aria-label="Table pagination">
                <ul class="pagination mb-0">
                    <li class="page-item" :class="{ disabled: page === 1 }">
                        <a class="page-link" href="#" @click.prevent="goToPage(page - 1)">Previous</a>
                    </li>
                    <li v-for="(number, index) in pageNumbers"
                        :key="index"
                        class="page-item"
                        :class="{ active: number === page, disabled: number === '…' }">
                        <a class="page-link" href="#" @click.prevent="typeof number === 'number' && goToPage(number)">{{ number }}</a>
                    </li>
                    <li class="page-item" :class="{ disabled: page === pageCount }">
                        <a class="page-link" href="#" @click.prevent="goToPage(page + 1)">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>
