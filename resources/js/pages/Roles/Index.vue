<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import FlashAlerts from '../../Components/FlashAlerts.vue';
import ServerTable from '../../Components/ServerTable.vue';

// Role list, ported from the former admin/roles/index Blade view. The table
// is fed by the Yajra endpoint on this same URL; cells render the structured
// data returned by RoleDataTable.
const page = usePage();
const permissions = computed(() => page.props.auth.permissions ?? []);
const can = (name) => permissions.value.includes(name);

const searchTerm = ref('');

const columns = [
    { key: 'role_name', label: 'Role Name', name: 'name' },
    { key: 'permissions_count', label: 'Permissions Count', name: 'permissions_count', searchable: false },
    { key: 'users_count', label: 'Active Users', name: 'users_count', searchable: false, align: 'center' },
    { key: 'permissions_preview', label: 'Permissions Preview', orderable: false, searchable: false },
    { key: 'actions', label: 'Actions', orderable: false, searchable: false, align: 'end', width: '180px' },
];

function deleteRole(row) {
    if (window.confirm('Are you sure you want to delete this role? Any assigned users will lose these permissions.')) {
        router.delete(row.actions.delete_url, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Roles" />

    <AdminLayout title="Role Management" :breadcrumb="[{ label: 'Roles' }]">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between pt-4 px-4 gap-3">
                <div>
                    <h5 class="mb-0 fw-bold text-body">Available Roles</h5>
                    <p class="text-muted small mb-0">Define permissions and configure user levels</p>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <!-- Search Form: fluid width, fills the row on small screens
                         and lets the Create button wrap below instead of clipping -->
                    <form class="position-relative" style="flex: 1 1 220px;" @submit.prevent>
                        <input type="text"
                               v-model="searchTerm"
                               class="form-control bg-light bg-opacity-75 border-secondary border-opacity-20 py-2 ps-5 rounded-pill w-100"
                               placeholder="Search roles...">
                        <i class="bi bi-search position-absolute text-muted" style="left: 18px; top: 50%; transform: translateY(-50%);"></i>
                        <a v-if="searchTerm" href="#" class="position-absolute text-muted" style="right: 18px; top: 50%; transform: translateY(-50%);" @click.prevent="searchTerm = ''">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    </form>

                    <Link v-if="can('role-create')" href="/admin/roles/create" class="btn btn-primary d-inline-flex align-items-center gap-2 py-2 px-3 fw-medium" style="border-radius: 8px;">
                        <i class="bi bi-shield-plus fs-5"></i> Create New Role
                    </Link>
                </div>
            </div>

            <div class="card-body px-4 pb-4">
                <!-- Alert banners -->
                <FlashAlerts />

                <ServerTable url="/admin/roles" :columns="columns" :search="searchTerm">
                    <template #cell-role_name="{ row }">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-shield-lock fs-5"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-body d-block">{{ row.role_name.name }}</span>
                                <span class="text-muted small">Guard: {{ row.role_name.guard_name }}</span>
                            </div>
                        </div>
                    </template>

                    <template #cell-permissions_count="{ value }">
                        <span class="badge bg-info bg-opacity-10 text-info fw-medium py-2 px-3 fs-7 border border-info border-opacity-20 rounded-pill">{{ value }} Permissions</span>
                    </template>

                    <template #cell-users_count="{ value }">
                        <div class="text-center">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold py-2 px-3 fs-7 border border-secondary border-opacity-20 rounded-pill">{{ value }} Users</span>
                        </div>
                    </template>

                    <template #cell-permissions_preview="{ row }">
                        <span v-if="row.permissions_preview.bypass" class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 rounded-pill py-1 px-2 text-xs">all-permissions (Bypass)</span>
                        <div v-else class="d-flex flex-wrap gap-1" style="max-width: 320px;">
                            <span v-for="name in row.permissions_preview.preview" :key="name" class="badge bg-light text-secondary border rounded-pill py-1 px-2 text-xs">{{ name }}</span>
                            <span v-if="row.permissions_preview.remaining > 0" class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 rounded-pill py-1 px-2 text-xs fw-bold">+{{ row.permissions_preview.remaining }} more</span>
                            <span v-if="row.permissions_preview.preview.length === 0" class="text-muted small italic">None</span>
                        </div>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="d-flex justify-content-end gap-2">
                            <button v-if="row.actions.locked" type="button" class="btn btn-sm btn-light border" disabled title="SuperAdmin permissions are locked">
                                <i class="bi bi-lock-fill text-muted me-1"></i> Locked
                            </button>
                            <template v-else>
                                <Link v-if="row.actions.edit_url" :href="row.actions.edit_url" class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" title="Edit Role">
                                    <i class="bi bi-pencil-square text-primary"></i> Edit
                                </Link>
                                <button v-if="row.actions.delete_url" type="button" class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" title="Delete Role" @click="deleteRole(row)">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                                <button v-else-if="row.actions.delete_disabled_reason" type="button" class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" disabled :title="row.actions.delete_disabled_reason">
                                    <i class="bi bi-trash-fill text-muted"></i>
                                </button>
                            </template>
                        </div>
                    </template>
                </ServerTable>
            </div>
        </div>
    </AdminLayout>
</template>
