<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Modal } from 'bootstrap';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import ServerTable from '../../Components/ServerTable.vue';
import ActionGroup from '../../Components/ActionGroup.vue';
import ActionButton from '../../Components/ActionButton.vue';

// Activity log list with the details modal, ported from the former
// admin/activity_logs/index Blade view. The table is fed by the Yajra
// endpoint on this same URL; the modal renders the properties payload the
// endpoint carries in each row's actions data.
const searchTerm = ref('');

const columns = [
    { key: 'id', label: 'ID', name: 'id', width: '80px' },
    { key: 'actor', label: 'Actor', name: 'actor' },
    { key: 'action', label: 'Action', name: 'action' },
    { key: 'subject', label: 'Subject', name: 'subject' },
    { key: 'created_at_formatted', label: 'Occurred At', name: 'created_at' },
    { key: 'actions', label: 'Details', orderable: false, searchable: false, align: 'end' },
];

// --- Details modal state -------------------------------------------------
const modalEl = ref(null);
const selected = ref(null); // the full table row whose Details was clicked

function openDetails(row) {
    selected.value = row;
    new Modal(modalEl.value).show();
}

const detailProps = computed(() => selected.value?.actions.properties ?? {});
const hasChangeSets = computed(() => Boolean(detailProps.value.attributes || detailProps.value.old));
const hasGenericProps = computed(
    () => !hasChangeSets.value && Object.keys(detailProps.value).length > 0,
);
const prettyProps = computed(() => JSON.stringify(detailProps.value, null, 2));

// Rows of the old/new comparison table, mirroring the old modal script:
// password and remember_token values are hidden, changed rows highlighted.
const changeRows = computed(() => {
    if (!hasChangeSets.value) {
        return [];
    }

    const attributes = detailProps.value.attributes ?? {};
    const old = detailProps.value.old ?? {};
    const keys = [...new Set([...Object.keys(attributes), ...Object.keys(old)])];

    return keys.map((key) => {
        const oldVal = old[key] !== undefined ? JSON.stringify(old[key]) : '-';
        const newVal = attributes[key] !== undefined ? JSON.stringify(attributes[key]) : '-';

        return {
            key,
            sensitive: key === 'password' || key === 'remember_token',
            oldVal,
            newVal,
            changed: oldVal !== newVal,
        };
    });
});
</script>

<template>
    <Head title="Activity Logs" />

    <AdminLayout title="Audit Activity Logs" :breadcrumb="[{ label: 'Activity Logs' }]">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between pt-4 px-4 gap-3">
                <div>
                    <h5 class="mb-0 fw-bold text-body">System Activities</h5>
                    <p class="text-muted small mb-0">Track all model modifications, creations, deletions, and actor details</p>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <!-- Search Form: fluid width instead of a fixed size -->
                    <form class="position-relative" style="flex: 1 1 220px;" @submit.prevent>
                        <input type="text"
                               v-model="searchTerm"
                               class="form-control bg-light bg-opacity-75 border-secondary border-opacity-20 py-2 ps-5 rounded-pill w-100"
                               placeholder="Search activities...">
                        <i class="bi bi-search position-absolute text-muted" style="left: 18px; top: 50%; transform: translateY(-50%);"></i>
                        <a v-if="searchTerm" href="#" class="position-absolute text-muted" style="right: 18px; top: 50%; transform: translateY(-50%);" @click.prevent="searchTerm = ''">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    </form>
                </div>
            </div>

            <div class="card-body px-4 pb-4">
                <ServerTable url="/admin/activity-logs" :columns="columns" :search="searchTerm" :default-order="{ index: 0, dir: 'desc' }">
                    <template #cell-id="{ value }">
                        {{ value }}
                    </template>

                    <template #cell-actor="{ row }">
                        <div v-if="row.actor">
                            <span class="fw-bold text-body d-block">{{ row.actor.name }}</span>
                            <span class="text-muted small">{{ row.actor.email }}</span>
                        </div>
                        <span v-else class="text-muted italic">System / Anonymous</span>
                    </template>

                    <template #cell-action="{ row }">
                        <span :class="`badge bg-${row.action.color} bg-opacity-10 text-${row.action.color} border border-${row.action.color} border-opacity-20 px-2.5 py-1.5 small fw-semibold text-uppercase`">{{ row.action.event }}</span>
                    </template>

                    <template #cell-subject="{ row }">
                        <template v-if="row.subject">
                            <span class="fw-medium text-body">{{ row.subject.class }}</span>
                            <span v-if="row.subject.name" class="text-muted small"> ({{ row.subject.name }})</span>
                            <span class="badge bg-light text-secondary border ms-1 small">ID: {{ row.subject.id }}</span>
                        </template>
                        <span v-else class="text-muted small">-</span>
                    </template>

                    <template #cell-created_at_formatted="{ row }">
                        <span class="text-secondary small" :title="row.created_at_formatted.full">{{ row.created_at_formatted.human }}</span>
                    </template>

                    <template #cell-actions="{ row }">
                        <ActionGroup>
                            <ActionButton icon="bi-eye" color="primary" label="Details" @click="openDetails(row)" />
                        </ActionGroup>
                    </template>
                </ServerTable>
            </div>
        </div>

        <!-- Details Modal -->
        <div ref="modalEl" class="modal fade" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-bottom px-4">
                        <h5 class="modal-title fw-bold text-body" id="detailsModalLabel">Activity Log Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-sm-3">
                                <span class="text-muted d-block small text-uppercase fw-semibold">Actor</span>
                                <span class="fw-medium text-body">{{ selected?.actions.causer_name ?? 'N/A' }}</span>
                            </div>
                            <div class="col-6 col-sm-3">
                                <span class="text-muted d-block small text-uppercase fw-semibold">Action</span>
                                <span v-if="selected" :class="`badge bg-${selected.action.color} bg-opacity-10 text-${selected.action.color} border border-${selected.action.color} border-opacity-20 px-2.5 py-1.5 small fw-semibold text-uppercase`">{{ selected.action.event }}</span>
                            </div>
                            <div class="col-6 col-sm-3">
                                <span class="text-muted d-block small text-uppercase fw-semibold">Subject Type</span>
                                <span class="fw-medium text-body">{{ selected?.actions.subject_class ?? 'N/A' }}</span>
                            </div>
                            <div class="col-6 col-sm-3">
                                <span class="text-muted d-block small text-uppercase fw-semibold">Timestamp</span>
                                <span class="fw-medium text-body">{{ selected?.actions.time ?? 'N/A' }}</span>
                            </div>
                        </div>

                        <h6 class="fw-bold text-body mb-3">Changes & Parameters</h6>

                        <div v-if="hasChangeSets" class="table-responsive">
                            <table class="table table-bordered table-striped align-middle small mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Attribute</th>
                                        <th v-if="detailProps.old">Old Value</th>
                                        <th v-if="detailProps.attributes">New Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in changeRows" :key="row.key" :class="{ 'table-warning table-opacity-10': row.changed && !row.sensitive }">
                                        <td class="fw-bold">{{ row.key }}</td>
                                        <td v-if="detailProps.old" class="text-secondary">
                                            <span v-if="row.sensitive" class="text-muted">[Hidden / Encrypted]</span>
                                            <template v-else>{{ row.oldVal }}</template>
                                        </td>
                                        <td v-if="detailProps.attributes" class="text-body fw-medium">
                                            <span v-if="row.sensitive" class="text-muted">[Hidden / Encrypted]</span>
                                            <template v-else>{{ row.newVal }}</template>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <pre v-else-if="hasGenericProps" class="bg-light p-3 rounded text-body border" style="max-height: 300px; overflow-y: auto;"><code>{{ prettyProps }}</code></pre>
                        <div v-else class="text-center text-muted py-4">
                            <i class="bi bi-info-circle me-1"></i> No attributes parameters changed.
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4">
                        <button type="button" class="btn btn-secondary py-2 px-4 fw-medium" style="border-radius: 8px;" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
